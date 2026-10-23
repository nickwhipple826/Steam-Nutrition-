import { qs, qsa } from '../core/dom.js';

/**
 * Collection page: sort, and Load more.
 *
 * Progressive enhancement, not a client-side app. Every control is a
 * real form or link that works server-side; this only removes the
 * round-trips a visitor would otherwise see.
 *
 * - Sort submits on change, so the Apply button beside it is redundant
 *   and gets hidden.
 * - Load more fetches the next page and appends its cards, instead of
 *   navigating and losing the visitor's place in the grid.
 *
 * The filter drawer is deliberately NOT intercepted: it is a GET form,
 * and letting it navigate is what puts the filters in the URL where
 * they can be linked, bookmarked and shared.
 */
export function initShopFilters(): void {
  const shop = qs<HTMLElement>( '[data-shop]' );
  if ( !shop ) return;

  // ---- sort ----------------------------------------------------------
  const sortForm = qs<HTMLFormElement>( '[data-shop-sort-form]', shop );
  const sortSelect = qs<HTMLSelectElement>( '[data-shop-sort]', shop );
  const sortGo = qs<HTMLButtonElement>( '.shop__sort-go', shop );

  if ( sortForm && sortSelect ) {
    if ( sortGo ) sortGo.hidden = true;
    sortSelect.addEventListener( 'change', () => sortForm.submit() );
  }

  // ---- load more -----------------------------------------------------
  const grid = qs<HTMLElement>( '[data-product-grid]', shop );
  const count = qs<HTMLElement>( '[data-shop-count]', shop );
  let more = qs<HTMLAnchorElement>( '[data-shop-more]', shop );

  if ( !grid || !more ) return;

  let loading = false;

  const load = async ( event: Event ) => {
    event.preventDefault();

    const button = more;
    if ( !button || loading ) return;

    loading = true;
    const label = button.textContent;
    button.textContent = 'Loading…';
    button.setAttribute( 'aria-busy', 'true' );

    try {
      const response = await fetch( button.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } } );
      if ( !response.ok ) throw new Error( `HTTP ${ response.status }` );

      const doc = new DOMParser().parseFromString( await response.text(), 'text/html' );

      // Move the next page's cards across, then adopt that page's own
      // Load more button — it carries the following cursor.
      const added = Array.from( doc.querySelectorAll( '[data-product-grid] > li' ) );
      added.forEach( ( item ) => grid.appendChild( item ) );

      if ( count ) {
        const total = grid.children.length;
        count.textContent = `Showing ${ total } ${ total === 1 ? 'product' : 'products' }`;
      }

      const next = doc.querySelector<HTMLAnchorElement>( '[data-shop-more]' );

      if ( next ) {
        button.href = next.href;
        button.textContent = label;
        button.removeAttribute( 'aria-busy' );
      } else {
        // Last page: the button has nothing left to ask for.
        button.closest( '.shop__more' )?.remove();
        more = null;
      }
    } catch ( error ) {
      console.error( 'Load more failed:', error );
      // Put the link back the way it was so the visitor can retry, or
      // follow it as a plain link to the next page.
      button.textContent = label;
      button.removeAttribute( 'aria-busy' );
    } finally {
      loading = false;
    }
  };

  more.addEventListener( 'click', load );
}