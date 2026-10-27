import { qs, qsa } from '../core/dom.js';
import { trapFocus, storeFocus, restoreFocus } from '../utils/focus.js';
import { lockScroll, unlockScroll } from '../utils/scroll-lock.js';

interface CartLine {
  id: string;
  quantity: number;
  title: string;
  variant_title: string;
  url: string;
  image: { url: string; alt: string } | null;
  available: boolean;
  price: string | null;
  line_total: string | null;
  currency: string;
}

interface Cart {
  id: string;
  checkout_url: string;
  count: number;
  subtotal: string | null;
  currency: string;
  lines: CartLine[];
  max_quantity: number;
}

interface CartResponse {
  cart: Cart;
  error: string;
}

const COUNT_COOKIE = 'steam_cart_count';

/**
 * The bag: header count, slide-out drawer, and Add to bag.
 *
 * Talks to /wp-json/steam/v1/cart… (CartApi.php). Everything here is an
 * upgrade — the bag icon links to /cart and the product form POSTs
 * back to the product page, so with this script missing the shopper
 * still gets a working bag, one page load per step.
 */
export function initCart(): void {
  updateBadges( readCountCookie() );

  const drawer = qs<HTMLElement>( '[data-cart-drawer]' );
  if ( !drawer ) return;

  const api = drawer.dataset.cartApi ?? '';
  const panel = qs<HTMLElement>( '.cart-drawer__panel', drawer );
  const linesEl = qs<HTMLUListElement>( '[data-cart-lines]', drawer );
  const template = qs<HTMLTemplateElement>( '[data-cart-line-template]', drawer );
  const emptyEl = qs<HTMLElement>( '[data-cart-empty]', drawer );
  const footEl = qs<HTMLElement>( '[data-cart-foot]', drawer );
  const bodyEl = qs<HTMLElement>( '[data-cart-body]', drawer );
  const errorEl = qs<HTMLElement>( '[data-cart-error]', drawer );
  const subtotalEl = qs<HTMLElement>( '[data-cart-subtotal]', drawer );
  const checkoutEl = qs<HTMLAnchorElement>( '[data-cart-checkout]', drawer );
  const countLabel = qs<HTMLElement>( '[data-cart-count-label]', drawer );

  if ( !api || !panel || !linesEl || !template ) return;

  let returnFocus: HTMLElement | null = null;
  let isOpen = false;
  let busy = false;

  // ---------------------------------------------------------------
  // Talking to the API
  // ---------------------------------------------------------------
  const request = async ( path: string, body: Record<string, unknown> = {} ): Promise<CartResponse> => {
    setBusy( true );

    try {
      const response = await fetch( api + path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify( body ),
      } );

      const data = await response.json() as CartResponse;
      render( data.cart );
      showError( data.error );

      return data;
    } catch {
      const message = 'Something went wrong. Please try again.';
      showError( message );
      throw new Error( message );
    } finally {
      setBusy( false );
    }
  };

  const setBusy = ( state: boolean ) => {
    busy = state;
    bodyEl?.setAttribute( 'aria-busy', String( state ) );
    drawer.classList.toggle( 'is-busy', state );
  };

  const showError = ( message: string ) => {
    if ( !errorEl ) return;
    errorEl.textContent = message;
    errorEl.hidden = !message;
  };

  // ---------------------------------------------------------------
  // Rendering
  // ---------------------------------------------------------------
  const render = ( cart: Cart ) => {
    updateBadges( cart.count );

    if ( countLabel ) countLabel.textContent = cart.count ? `(${ cart.count })` : '';

    linesEl.replaceChildren( ...cart.lines.map( ( line ) => renderLine( line, cart.max_quantity ) ) );

    const empty = cart.lines.length === 0;
    if ( emptyEl ) emptyEl.hidden = !empty;
    if ( footEl ) footEl.hidden = empty;

    if ( subtotalEl ) subtotalEl.textContent = money( cart.subtotal, cart.currency );
    if ( checkoutEl ) checkoutEl.href = cart.checkout_url || '#';
  };

  const renderLine = ( line: CartLine, max: number ): HTMLElement => {
    const fragment = template.content.cloneNode( true ) as DocumentFragment;
    const li = fragment.firstElementChild as HTMLElement;

    qsa<HTMLAnchorElement>( '[data-line-link]', li ).forEach( ( a ) => { a.href = line.url; } );

    const img = qs<HTMLImageElement>( '[data-line-image]', li );
    if ( img ) {
      if ( line.image ) {
        img.src = line.image.url;
      } else {
        img.remove();
      }
    }

    setText( li, '[data-line-title]', line.title );
    setText( li, '[data-line-variant]', line.variant_title );
    setText( li, '[data-line-qty]', String( line.quantity ) );
    setText( li, '[data-line-total]', money( line.line_total, line.currency ) );

    const group = qs<HTMLElement>( '[data-line-qty-group]', li );
    group?.setAttribute( 'aria-label', `Quantity for ${ line.title }` );

    const dec = qs<HTMLButtonElement>( '[data-line-dec]', li );
    const inc = qs<HTMLButtonElement>( '[data-line-inc]', li );
    const remove = qs<HTMLButtonElement>( '[data-line-remove]', li );

    if ( inc ) inc.disabled = line.quantity >= max;

    dec?.addEventListener( 'click', () => changeQuantity( line.id, line.quantity - 1 ) );
    inc?.addEventListener( 'click', () => changeQuantity( line.id, line.quantity + 1 ) );
    remove?.addEventListener( 'click', () => {
      if ( busy ) return;
      request( '/remove', { line: line.id } ).then( focusPanel ).catch( () => {} );
    } );
    remove?.setAttribute( 'aria-label', `Remove ${ line.title }` );

    if ( !line.available ) li.classList.add( 'is-unavailable' );

    return li;
  };

  const changeQuantity = ( lineId: string, quantity: number ) => {
    if ( busy ) return;
    // Zero removes the line; CartApi treats it the same as Remove.
    request( '/update', { line: lineId, quantity } ).catch( () => {} );
  };

  // ---------------------------------------------------------------
  // Open / close
  // ---------------------------------------------------------------
  const focusPanel = () => panel.focus( { preventScroll: true } );

  const onKeydown = ( event: KeyboardEvent ) => {
    if ( event.key === 'Escape' ) {
      close();
      return;
    }
    trapFocus( panel, event );
  };

  const open = () => {
    if ( isOpen ) return;
    isOpen = true;

    returnFocus = storeFocus();
    drawer.hidden = false;
    lockScroll();
    document.addEventListener( 'keydown', onKeydown );

    qsa<HTMLElement>( '[data-cart-open]' ).forEach( ( el ) => el.setAttribute( 'aria-expanded', 'true' ) );

    // Next frame, so the transition runs from the closed position.
    requestAnimationFrame( () => {
      drawer.classList.add( 'is-open' );
      focusPanel();
    } );
  };

  const close = () => {
    if ( !isOpen ) return;
    isOpen = false;

    drawer.classList.remove( 'is-open' );
    document.removeEventListener( 'keydown', onKeydown );
    qsa<HTMLElement>( '[data-cart-open]' ).forEach( ( el ) => el.setAttribute( 'aria-expanded', 'false' ) );

    const finish = () => {
      drawer.hidden = true;
      unlockScroll();
      restoreFocus( returnFocus );
    };

    if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
      finish();
    } else {
      panel.addEventListener( 'transitionend', finish, { once: true } );
      // In case the transition never fires (display change, tab hidden).
      window.setTimeout( () => { if ( !drawer.hidden && !isOpen ) finish(); }, 400 );
    }
  };

  qsa<HTMLElement>( '[data-cart-open]' ).forEach( ( trigger ) => {
    trigger.addEventListener( 'click', ( event ) => {
      // Opening the drawer on the bag page itself would be redundant.
      if ( document.body.classList.contains( 'cart-page' ) ) return;

      event.preventDefault();
      open();
      request( '' ).catch( () => {} );
    } );
  } );

  qsa<HTMLElement>( '[data-cart-close]', drawer ).forEach( ( el ) => el.addEventListener( 'click', close ) );

  // ---------------------------------------------------------------
  // Add to bag — the product form, and any [data-cart-add] button
  // ---------------------------------------------------------------
  /**
   * 'added' and 'refused' both open the drawer (a refusal shows its
   * reason there). 'failed' means the API never answered.
   */
  const add = async ( variant: string, quantity: number, button: HTMLButtonElement | null ): Promise<'added' | 'refused' | 'failed'> => {
    const label = button?.textContent ?? '';

    if ( button ) {
      button.disabled = true;
      button.textContent = 'Adding…';
    }

    try {
      const { error } = await request( '/add', { variant, quantity } );
      open();
      return error ? 'refused' : 'added';
    } catch {
      return 'failed';
    } finally {
      if ( button ) {
        button.disabled = false;
        button.textContent = label;
      }
    }
  };

  qsa<HTMLFormElement>( '[data-cart-add-form]' ).forEach( ( form ) => {
    form.addEventListener( 'submit', ( event ) => {
      const variant = qs<HTMLInputElement>( 'input[name="variant"]', form )?.value ?? '';
      const quantity = parseInt( qs<HTMLInputElement>( 'input[name="quantity"]', form )?.value ?? '1', 10 ) || 1;
      const button = qs<HTMLButtonElement>( 'button[type="submit"]', form );

      if ( !variant ) return; // let the server explain

      event.preventDefault();

      add( variant, quantity, button ).then( ( result ) => {
        // The API never answered: fall back to the plain POST, which
        // adds server-side and lands on /cart.
        if ( result === 'failed' ) form.submit();
      } );
    } );
  } );

  qsa<HTMLButtonElement>( '[data-cart-add]' ).forEach( ( button ) => {
    button.addEventListener( 'click', () => {
      add( button.dataset.variant ?? '', 1, button );
    } );
  } );
}

// ---------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------

function readCountCookie(): number {
  const match = document.cookie.match( new RegExp( `(?:^|; )${ COUNT_COOKIE }=(\\d+)` ) );
  return match?.[1] ? parseInt( match[1], 10 ) : 0;
}

function updateBadges( count: number ): void {
  qsa<HTMLElement>( '[data-cart-count]' ).forEach( ( badge ) => {
    badge.textContent = String( count );
    badge.hidden = count === 0;
  } );

  qsa<HTMLElement>( '[data-cart-open]' ).forEach( ( link ) => {
    link.setAttribute( 'aria-label', count ? `Bag, ${ count } ${ count === 1 ? 'item' : 'items' }` : 'Bag' );
  } );
}

function setText( scope: ParentNode, selector: string, text: string ): void {
  const el = qs<HTMLElement>( selector, scope );
  if ( !el ) return;

  if ( text ) {
    el.textContent = text;
  } else {
    el.remove();
  }
}

function money( amount: string | null, currency: string ): string {
  if ( amount === null ) return '';
  return new Intl.NumberFormat( 'en-US', { style: 'currency', currency } ).format( parseFloat( amount ) );
}
