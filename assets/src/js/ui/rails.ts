import { qsa } from '../core/dom.js';

/**
 * Arrow buttons for the horizontal product rails.
 *
 * The rail itself is a plain scroll container with scroll-snap, so it
 * already works with a trackpad, a touch drag and the keyboard. These
 * buttons are for mouse users who have neither, and they disable
 * themselves at each end rather than sitting there doing nothing.
 *
 * @example
 * ```html
 * <button data-rail-target="rail-the-lineup" data-rail-dir="1">…</button>
 * <ul class="rail" id="rail-the-lineup" tabindex="0">…</ul>
 * ```
 */
export function initRails(): void {
  const buttons = qsa<HTMLButtonElement>( '[data-rail-target]' );
  if ( buttons.length === 0 ) return;

  // Group the buttons by the rail they drive, so one scroll event
  // updates both arrows rather than only the one that was clicked.
  const rails = new Map<HTMLElement, HTMLButtonElement[]>();

  buttons.forEach( ( button ) => {
    const id = button.dataset.railTarget;
    if ( !id ) return;

    const rail = document.getElementById( id );
    if ( !rail ) {
      console.warn( `Rails: no element with id "${ id }"`, button );
      return;
    }

    rails.set( rail, [ ...( rails.get( rail ) ?? [] ), button ] );

    button.addEventListener( 'click', () => {
      const direction = Number( button.dataset.railDir ?? 1 );
      const item = rail.querySelector<HTMLElement>( '.rail__item' );
      const gap = parseFloat( getComputedStyle( rail ).columnGap ) || 16;
      const step = item ? item.getBoundingClientRect().width + gap : 300;

      rail.scrollBy( {
        left: step * direction * 1.5,
        behavior: 'smooth',
      } );
    } );
  } );

  rails.forEach( ( railButtons, rail ) => {
    const sync = () => {
      // A one-pixel tolerance: fractional scroll widths mean
      // scrollLeft rarely lands exactly on the maximum.
      const atStart = rail.scrollLeft <= 1;
      const atEnd = rail.scrollLeft >= rail.scrollWidth - rail.clientWidth - 1;

      railButtons.forEach( ( button ) => {
        const direction = Number( button.dataset.railDir ?? 1 );
        button.disabled = direction < 0 ? atStart : atEnd;
      } );
    };

    sync();
    rail.addEventListener( 'scroll', sync, { passive: true } );
    window.addEventListener( 'resize', sync, { passive: true } );
  } );
}
