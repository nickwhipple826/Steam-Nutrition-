import { qs } from '../core/dom.js';

/**
 * Header scroll state.
 *
 * Adds .is-solid to the header wrapper once the page leaves the top.
 * The CSS does the rest: the bar goes opaque and the ticker slides up
 * out of view, leaving only the 78px bar pinned.
 *
 * Reads scrollY in a rAF-throttled handler rather than an
 * IntersectionObserver on a sentinel, because the threshold is a pixel
 * offset from the top rather than an element crossing the viewport.
 */
export function initHeader(): void {
  const header = qs<HTMLElement>( '[data-header]' );
  if ( !header ) return;

  const THRESHOLD = 24;
  let ticking = false;

  const update = () => {
    header.classList.toggle( 'is-solid', window.scrollY > THRESHOLD );
    ticking = false;
  };

  update();

  window.addEventListener( 'scroll', () => {
    if ( ticking ) return;
    ticking = true;
    requestAnimationFrame( update );
  }, { passive: true } );
}
