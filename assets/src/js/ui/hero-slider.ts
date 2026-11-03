import { qs, qsa } from '../core/dom.js';

/**
 * Crossfade slider for the home hero (sections/hero-lineup.twig).
 *
 * No library: every slide's copy is stacked in one grid cell and every
 * background in one absolute layer, so the only thing that changes is
 * which one carries is-active. CSS does the fading.
 *
 * Autoplay is timed by the progress tick itself. The active tick's fill
 * runs a CSS animation for --hero-interval and the slider advances on
 * its animationend, so pausing is just animation-play-state — the bar
 * and the clock can never drift apart, and a resumed slide picks up
 * where it stopped instead of starting over.
 *
 * Holds (hover, keyboard focus, hidden tab) pause it without touching
 * the visible play/pause state. The toggle is the visitor's own choice
 * and wins over everything. Reduced motion: no autoplay at all.
 *
 * @example
 * ```html
 * <section data-hero-slider data-autoplay="7000" style="--hero-interval: 7000ms">
 *   <div data-hero-bg class="is-active">…</div>  <div data-hero-bg>…</div>
 *   <div data-hero-slide class="is-active">…</div>  <div data-hero-slide inert>…</div>
 *   <dl data-hero-stats class="is-active">…</dl>  <dl data-hero-stats aria-hidden="true">…</dl>
 *   <button data-hero-dir="-1"></button> <button data-hero-dir="1"></button>
 *   <button data-hero-goto="0"></button> <button data-hero-goto="1"></button>
 *   <button data-hero-toggle hidden></button> <span data-hero-current></span>
 * </section>
 * ```
 */
export function initHeroSlider(): void {
  qsa<HTMLElement>( '[data-hero-slider]' ).forEach( setup );
}

function setup( root: HTMLElement ): void {
  const slides = qsa<HTMLElement>( '[data-hero-slide]', root );
  const bgs = qsa<HTMLElement>( '[data-hero-bg]', root );
  const stats = qsa<HTMLElement>( '[data-hero-stats]', root );
  const ticks = qsa<HTMLButtonElement>( '[data-hero-goto]', root );
  const current = qs<HTMLElement>( '[data-hero-current]', root );
  const toggle = qs<HTMLButtonElement>( '[data-hero-toggle]', root );

  if ( slides.length < 2 ) return;

  const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
  const interval = Number( root.dataset.autoplay ?? 0 );
  let index = 0;

  const go = ( next: number ): void => {
    const target = ( next + slides.length ) % slides.length;
    if ( target === index ) return;

    // Focus must not be left on a slide that is about to go inert, or
    // it falls back to <body>. Only reachable by keyboard-paging while
    // focused inside the copy, which is rare, but cheap to handle.
    const focusWasInSlide = slides[ index ]?.contains( document.activeElement ) ?? false;

    [ slides, bgs, stats, ticks ].forEach( ( set ) => {
      set[ index ]?.classList.remove( 'is-active' );
      set[ target ]?.classList.add( 'is-active' );
    } );

    slides[ index ]!.inert = true;
    slides[ target ]!.inert = false;

    stats[ index ]?.setAttribute( 'aria-hidden', 'true' );
    stats[ target ]?.removeAttribute( 'aria-hidden' );

    ticks[ index ]?.removeAttribute( 'aria-current' );
    ticks[ target ]?.setAttribute( 'aria-current', 'true' );

    if ( current ) current.textContent = String( target + 1 ).padStart( 2, '0' );

    index = target;

    if ( focusWasInSlide ) {
      qs<HTMLElement>( 'a, button', slides[ index ]! )?.focus( { preventScroll: true } );
    }
  };

  // -------------------------------------------------------------------
  // Controls
  // -------------------------------------------------------------------
  qsa<HTMLButtonElement>( '[data-hero-dir]', root ).forEach( ( button ) => {
    button.addEventListener( 'click', () => go( index + Number( button.dataset.heroDir ?? 1 ) ) );
  } );

  ticks.forEach( ( tick ) => {
    tick.addEventListener( 'click', () => go( Number( tick.dataset.heroGoto ) ) );
  } );

  root.addEventListener( 'keydown', ( event ) => {
    const target = event.target as HTMLElement;
    if ( target.closest( 'input, textarea, select, [contenteditable]' ) ) return;

    if ( event.key === 'ArrowLeft' ) go( index - 1 );
    else if ( event.key === 'ArrowRight' ) go( index + 1 );
    else return;

    event.preventDefault();
  } );

  // Horizontal swipe. Ignores mostly-vertical drags so the page still
  // scrolls normally from a thumb that starts on the hero.
  let startX = 0;
  let startY = 0;

  root.addEventListener( 'touchstart', ( event ) => {
    const touch = event.touches[ 0 ];
    if ( !touch ) return;
    startX = touch.clientX;
    startY = touch.clientY;
  }, { passive: true } );

  root.addEventListener( 'touchend', ( event ) => {
    const touch = event.changedTouches[ 0 ];
    if ( !touch ) return;
    const dx = touch.clientX - startX;
    const dy = touch.clientY - startY;
    if ( Math.abs( dx ) > 48 && Math.abs( dx ) > Math.abs( dy ) * 1.5 ) {
      go( index + ( dx < 0 ? 1 : -1 ) );
    }
  }, { passive: true } );

  // -------------------------------------------------------------------
  // Autoplay
  // -------------------------------------------------------------------
  if ( interval <= 0 || reducedMotion.matches ) return;

  root.classList.add( 'is-playing' );

  const holds = new Set<string>();
  const hold = ( reason: string, on: boolean ): void => {
    if ( on ) holds.add( reason );
    else holds.delete( reason );
    root.classList.toggle( 'is-held', holds.size > 0 );
  };

  root.addEventListener( 'animationend', ( event ) => {
    const fill = event.target as HTMLElement;
    if ( fill.classList.contains( 'hero-lineup__tick-fill' ) && fill.parentElement === ticks[ index ] ) {
      go( index + 1 );
    }
  } );

  root.addEventListener( 'pointerenter', ( event ) => {
    if ( event.pointerType === 'mouse' ) hold( 'hover', true );
  } );
  root.addEventListener( 'pointerleave', () => hold( 'hover', false ) );

  // Keyboard focus only. A mouse click on an arrow also focuses it, and
  // holding on that would stop autoplay until the visitor clicked
  // somewhere else on the page.
  root.addEventListener( 'focusin', ( event ) => {
    if ( ( event.target as HTMLElement ).matches( ':focus-visible' ) ) hold( 'focus', true );
  } );
  root.addEventListener( 'focusout', ( event ) => {
    if ( !root.contains( event.relatedTarget as Node | null ) ) hold( 'focus', false );
  } );

  document.addEventListener( 'visibilitychange', () => hold( 'hidden', document.hidden ) );

  if ( toggle ) {
    toggle.hidden = false;
    toggle.addEventListener( 'click', () => {
      const paused = root.classList.toggle( 'is-paused' );
      toggle.setAttribute( 'aria-label', paused ? 'Play slideshow' : 'Pause slideshow' );
    } );
  }

  // Someone switching reduced motion on mid-visit gets it immediately.
  reducedMotion.addEventListener( 'change', ( event ) => {
    if ( !event.matches ) return;
    root.classList.remove( 'is-playing' );
    if ( toggle ) toggle.hidden = true;
  } );
}
