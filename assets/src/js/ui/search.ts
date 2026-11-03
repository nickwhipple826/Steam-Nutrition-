import { qs, qsa } from '../core/dom.js';
import { lockScroll, unlockScroll } from '../utils/scroll-lock.js';
import { restoreFocus, storeFocus, trapFocus } from '../utils/focus.js';

/**
 * Header search: an in-place dialog with live Shopify results.
 *
 * The search icon is a real link to /search, and the form inside the
 * dialog is a plain GET to the same page, so all of this is an upgrade
 * — with JavaScript off the shopper still gets full results.
 *
 * As they type, /wp-json/steam/v1/search (SearchApi.php) returns a few
 * products and Shopify's own query suggestions. Requests are debounced,
 * and a response that arrives after a newer one has been sent is
 * dropped, so fast typing never flashes stale results.
 *
 * Keys: / opens it from anywhere that is not a text field. Esc closes.
 * Arrow Down from the field walks the results; Up from the first one
 * returns to the field.
 *
 * @example
 * ```html
 * <a href="/search/" data-search-open>…</a>
 * <div data-search data-search-api="/wp-json/steam/v1/search" hidden>
 *   <div data-search-close></div>
 *   <form data-search-form><input data-search-input></form>
 *   <p data-search-status></p>
 *   <div data-search-results></div>
 * </div>
 * ```
 */

interface SearchProduct {
  title: string;
  url: string;
  image: string;
  alt: string;
  type: string;
  price: string | null;
  compare_at: string | null;
  currency: string;
  available: boolean;
}

interface SearchResponse {
  query: string;
  products: SearchProduct[];
  suggestions: string[];
  all_url: string;
}

const DEBOUNCE_MS = 220;
const MIN_LENGTH = 2;
const TRANSITION_MS = 260;

export function initSearch(): void {
  const root = qs<HTMLElement>( '[data-search]' );
  const openers = qsa<HTMLAnchorElement>( '[data-search-open]' );
  if ( !root || openers.length === 0 ) return;

  const api = root.dataset.searchApi;
  const form = qs<HTMLFormElement>( '[data-search-form]', root );
  const input = qs<HTMLInputElement>( '[data-search-input]', root );
  const status = qs<HTMLElement>( '[data-search-status]', root );
  const results = qs<HTMLElement>( '[data-search-results]', root );
  const browse = qs<HTMLElement>( '[data-search-browse]', root );
  const panel = qs<HTMLElement>( '.site-search__panel', root );

  if ( !api || !form || !input || !status || !results || !panel ) return;

  let isOpen = false;
  let returnFocus: HTMLElement | null = null;
  let closeTimer: ReturnType<typeof setTimeout> | null = null;
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;
  let controller: AbortController | null = null;
  let latest = 0;
  const cache = new Map<string, SearchResponse>();

  // -------------------------------------------------------------------
  // Open / close
  // -------------------------------------------------------------------
  const open = (): void => {
    if ( isOpen ) return;
    isOpen = true;

    if ( closeTimer ) {
      clearTimeout( closeTimer );
      closeTimer = null;
    }

    returnFocus = storeFocus();
    root.hidden = false;
    void root.offsetHeight;
    root.classList.add( 'is-open' );

    openers.forEach( ( el ) => el.setAttribute( 'aria-expanded', 'true' ) );
    lockScroll();
    document.addEventListener( 'keydown', onKeydown );

    // iOS only raises the keyboard for a focus inside the tap's own
    // call stack, so focus now rather than after the transition.
    input.focus( { preventScroll: true } );
    input.select();
  };

  const close = (): void => {
    if ( !isOpen ) return;
    isOpen = false;

    root.classList.remove( 'is-open' );
    openers.forEach( ( el ) => el.setAttribute( 'aria-expanded', 'false' ) );
    document.removeEventListener( 'keydown', onKeydown );
    unlockScroll();
    restoreFocus( returnFocus );

    closeTimer = setTimeout( () => {
      root.hidden = true;
      closeTimer = null;
    }, TRANSITION_MS );
  };

  openers.forEach( ( opener ) => {
    opener.setAttribute( 'aria-expanded', 'false' );
    opener.addEventListener( 'click', ( event ) => {
      // Let modified clicks open /search in a new tab as normal.
      if ( event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0 ) return;
      event.preventDefault();
      open();
    } );
  } );

  qsa<HTMLElement>( '[data-search-close]', root ).forEach( ( el ) => el.addEventListener( 'click', close ) );

  // "/" to search, the convention shoppers know from most big stores.
  document.addEventListener( 'keydown', ( event ) => {
    if ( event.key !== '/' || isOpen || event.metaKey || event.ctrlKey || event.altKey ) return;
    const target = event.target as HTMLElement;
    if ( target.closest( 'input, textarea, select, [contenteditable]' ) ) return;
    event.preventDefault();
    open();
  } );

  // -------------------------------------------------------------------
  // Keyboard inside the dialog
  // -------------------------------------------------------------------
  const resultLinks = (): HTMLAnchorElement[] => qsa<HTMLAnchorElement>( 'a', results );

  const onKeydown = ( event: KeyboardEvent ): void => {
    if ( event.key === 'Escape' ) {
      // First Esc clears a typed query, the second closes — the same
      // two-step a native search field has.
      if ( document.activeElement === input && input.value ) {
        input.value = '';
        render( null );
        return;
      }
      close();
      return;
    }

    if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
      const links = resultLinks();
      if ( links.length === 0 ) return;

      const index = links.indexOf( document.activeElement as HTMLAnchorElement );
      const down = event.key === 'ArrowDown';

      if ( document.activeElement === input && down ) {
        links[ 0 ]?.focus();
      } else if ( index === 0 && !down ) {
        input.focus();
      } else if ( index > -1 ) {
        links[ Math.min( links.length - 1, Math.max( 0, index + ( down ? 1 : -1 ) ) ) ]?.focus();
      } else {
        return;
      }

      event.preventDefault();
      return;
    }

    trapFocus( panel, event );
  };

  // -------------------------------------------------------------------
  // Fetching
  // -------------------------------------------------------------------
  const fetchResults = async ( query: string ): Promise<void> => {
    const key = query.toLowerCase();
    const ticket = ++latest;

    const hit = cache.get( key );
    if ( hit ) {
      render( hit );
      return;
    }

    controller?.abort();
    controller = new AbortController();
    root.classList.add( 'is-loading' );

    try {
      const url = new URL( api, window.location.origin );
      url.searchParams.set( 'q', query );

      const response = await fetch( url, { signal: controller.signal, headers: { Accept: 'application/json' } } );
      if ( !response.ok ) throw new Error( `HTTP ${ response.status }` );

      const data = ( await response.json() ) as SearchResponse;
      cache.set( key, data );

      if ( ticket === latest ) render( data );
    } catch ( error ) {
      if ( ( error as Error ).name === 'AbortError' ) return;
      console.error( 'Search failed:', error );
      if ( ticket === latest ) {
        const message = 'Search is unavailable right now. Press Enter to try the full results page.';
        results.innerHTML = '';
        results.append( el( 'p', 'site-search__empty', message ) );
        status.textContent = message;
      }
    } finally {
      if ( ticket === latest ) root.classList.remove( 'is-loading' );
    }
  };

  input.addEventListener( 'input', () => {
    const query = input.value.trim();

    if ( debounceTimer ) clearTimeout( debounceTimer );

    if ( query.length < MIN_LENGTH ) {
      latest++;
      controller?.abort();
      root.classList.remove( 'is-loading' );
      render( null );
      return;
    }

    debounceTimer = setTimeout( () => fetchResults( query ), DEBOUNCE_MS );
  } );

  form.addEventListener( 'submit', ( event ) => {
    if ( input.value.trim().length < MIN_LENGTH ) {
      event.preventDefault();
      input.focus();
    }
  } );

  // -------------------------------------------------------------------
  // Rendering
  // -------------------------------------------------------------------
  const money = ( amount: string | null, currency: string ): string => {
    if ( amount === null ) return '';
    try {
      return new Intl.NumberFormat( undefined, { style: 'currency', currency } ).format( Number( amount ) );
    } catch {
      return `$${ Number( amount ).toFixed( 2 ) }`;
    }
  };

  const el = <K extends keyof HTMLElementTagNameMap>( tag: K, className?: string, text?: string ): HTMLElementTagNameMap[K] => {
    const node = document.createElement( tag );
    if ( className ) node.className = className;
    if ( text !== undefined ) node.textContent = text;
    return node;
  };

  const searchUrl = ( query: string ): string => {
    const url = new URL( form.action, window.location.origin );
    url.searchParams.set( 'q', query );
    return url.toString();
  };

  /**
   * Built with DOM calls, not innerHTML: titles and suggestions come
   * from Shopify, and nothing from another system goes in as markup.
   */
  const render = ( data: SearchResponse | null ): void => {
    results.innerHTML = '';
    root.classList.toggle( 'has-query', data !== null );
    if ( browse ) browse.hidden = data !== null;

    if ( !data ) {
      status.textContent = '';
      return;
    }

    if ( data.suggestions.length ) {
      const wrap = el( 'div', 'site-search__suggest' );
      wrap.append( el( 'span', 'site-search__label', 'Try' ) );
      const list = el( 'ul' );
      data.suggestions.slice( 0, 4 ).forEach( ( text ) => {
        const li = el( 'li' );
        const a = el( 'a', 'site-search__chip', text );
        a.href = searchUrl( text );
        li.append( a );
        list.append( li );
      } );
      wrap.append( list );
      results.append( wrap );
    }

    if ( data.products.length ) {
      const list = el( 'ul', 'site-search__products' );

      data.products.forEach( ( product ) => {
        const li = el( 'li' );
        const a = el( 'a', 'search-hit' + ( product.available ? '' : ' search-hit--sold-out' ) );
        a.href = product.url;

        const shot = el( 'span', 'search-hit__shot' );
        if ( product.image ) {
          const img = el( 'img' );
          const src = new URL( product.image );
          // Shopify's CDN resizes on request; a 160px thumb, not the master.
          src.searchParams.set( 'width', '160' );
          img.src = src.toString();
          img.alt = '';
          img.loading = 'lazy';
          img.width = 80;
          img.height = 80;
          shot.append( img );
        }

        const info = el( 'span', 'search-hit__info' );
        info.append( el( 'span', 'search-hit__title', product.title ) );
        if ( product.type ) info.append( el( 'span', 'search-hit__type', product.type ) );

        const price = el( 'span', 'search-hit__price' );
        if ( !product.available ) {
          price.append( el( 'span', 'search-hit__flag', 'Sold out' ) );
        } else {
          price.append( document.createTextNode( money( product.price, product.currency ) ) );
          if ( product.compare_at ) price.append( el( 's', undefined, money( product.compare_at, product.currency ) ) );
        }

        a.append( shot, info, price );
        li.append( a );
        list.append( li );
      } );

      results.append( list );

      const all = el( 'a', 'site-search__all' );
      all.href = data.all_url;
      all.append( document.createTextNode( 'View all results for ' ), el( 'strong', undefined, `“${ data.query }”` ) );
      results.append( all );

      status.textContent = `${ data.products.length } ${ data.products.length === 1 ? 'product' : 'products' } found. Use the arrow keys to move through them.`;
    } else {
      results.append( el( 'p', 'site-search__empty', `Nothing matches “${ data.query }”. Try a broader word.` ) );
      status.textContent = 'No products found.';
    }
  };
}
