import { qs, qsa } from "../core/dom.js";
import { lockScroll, unlockScroll } from "../utils/scroll-lock.js";
import { focusFirst, restoreFocus, storeFocus, trapFocus } from "../utils/focus.js";

/**
 * Mobile navigation drawer.
 *
 * The panel sits off-canvas (CSS transform) and slides in when
 * `.is-open` is added. The `hidden` attribute takes it out of the
 * accessibility tree and tab order once it has finished sliding out.
 *
 * Closes on: the close button, the backdrop, Esc, following any link
 * inside it, opening the bag from it, and the viewport growing into the
 * desktop layout (where the drawer has no toggle to close it with).
 *
 * The panel and backdrop must NOT live inside .header-wrapper — see the
 * note at the top of header.twig.
 *
 * @example
 * ```html
 * <button data-nav-toggle aria-expanded="false" aria-controls="site-nav">Menu</button>
 * <div data-nav-backdrop hidden></div>
 * <div id="site-nav" data-nav-panel hidden>
 *   <button data-nav-close>Close</button>
 *   … nav items
 * </div>
 * ```
 */

/** Must match $desktop in layout/_header.scss. */
const DESKTOP_QUERY = '(min-width: 1001px) and (orientation: landscape)';

/** Matches the transform transition on .mobile-nav-panel. */
const TRANSITION_MS = 320;

export function initNav( root: HTMLElement = document.body ) {
  const toggle = qs<HTMLButtonElement>( '[data-nav-toggle]', root );
  const panel = qs<HTMLElement>( '[data-nav-panel]', root );
  const backdrop = qs<HTMLElement>( '[data-nav-backdrop]', root );
  const closeBtn = qs<HTMLButtonElement>( '[data-nav-close]', root );

  if ( !toggle || !panel ) return;

  let isOpen = false;
  let lastFocused: HTMLElement | null = null;
  let closeTimeout: ReturnType<typeof setTimeout> | null = null;

  const open = () => {
    if ( isOpen ) return;
    isOpen = true;

    if ( closeTimeout ) {
      clearTimeout( closeTimeout );
      closeTimeout = null;
    }

    lastFocused = storeFocus();

    panel.hidden = false;
    if ( backdrop ) backdrop.hidden = false;

    // Force layout so the browser registers the off-canvas position
    // before the class flips, otherwise the transition gets skipped.
    void panel.offsetHeight;

    panel.classList.add( 'is-open' );
    backdrop?.classList.add( 'is-open' );
    document.documentElement.classList.add( 'nav-open' );

    toggle.setAttribute( 'aria-expanded', 'true' );
    lockScroll();

    // The close button, not the search field: focusing an input would
    // throw the keyboard up over the menu the shopper just asked for.
    if ( closeBtn ) closeBtn.focus( { preventScroll: true } );
    else focusFirst( panel );

    document.addEventListener( 'keydown', onKeydown );
  };

  /**
   * @param returnFocus false when something else is about to take focus
   *                    (the bag drawer), so the two don't fight over it.
   */
  const close = ( returnFocus = true ) => {
    if ( !isOpen ) return;
    isOpen = false;

    panel.classList.remove( 'is-open' );
    backdrop?.classList.remove( 'is-open' );
    document.documentElement.classList.remove( 'nav-open' );

    toggle.setAttribute( 'aria-expanded', 'false' );

    unlockScroll();
    if ( returnFocus ) restoreFocus( lastFocused );

    document.removeEventListener( 'keydown', onKeydown );

    // Wait for the slide-out to finish before pulling the panel out of
    // the accessibility tree / tab order.
    closeTimeout = setTimeout( () => {
      panel.hidden = true;
      if ( backdrop ) backdrop.hidden = true;
      closeTimeout = null;
    }, TRANSITION_MS );
  };

  const onKeydown = ( event: KeyboardEvent ) => {
    if ( event.key === 'Escape' ) {
      close();
      return;
    }

    trapFocus( panel, event );
  };

  toggle.addEventListener( 'click', () => ( isOpen ? close() : open() ) );
  backdrop?.addEventListener( 'click', () => close() );
  closeBtn?.addEventListener( 'click', () => close() );

  // Capture phase, so this runs before the bag link's own listener in
  // ui/cart.ts: the menu is gone and focus is back on the toggle by the
  // time the cart drawer records where to return focus to.
  panel.addEventListener( 'click', ( event ) => {
    const target = event.target as HTMLElement;

    if ( target.closest( '[data-cart-open]' ) ) {
      close();
      return;
    }

    const link = target.closest<HTMLAnchorElement>( 'a[href]' );
    if ( !link ) return;

    // Same-page anchors and new-tab links leave the page in place, so
    // the menu has to get out of the way itself. Ordinary navigation
    // closes it too, so Back doesn't return to a half-open drawer from
    // the bfcache.
    close( false );
  }, true );

  // Grown into the desktop layout: the toggle is gone, so the drawer
  // would be stuck open with the page scroll-locked behind it.
  const desktop = window.matchMedia( DESKTOP_QUERY );
  desktop.addEventListener( 'change', ( event ) => {
    if ( event.matches ) close( false );
  } );

  // Restored from the bfcache with the drawer open (iOS Safari does
  // this after Back): reset to closed.
  window.addEventListener( 'pageshow', ( event ) => {
    if ( event.persisted && isOpen ) close( false );
  } );

  // One group open at a time keeps the drawer short enough to scan.
  const groups = qsa<HTMLDetailsElement>( '.mobile-nav__group', panel );
  groups.forEach( ( group ) => {
    group.addEventListener( 'toggle', () => {
      if ( !group.open ) return;
      groups.forEach( ( other ) => {
        if ( other !== group ) other.open = false;
      } );
    } );
  } );
}
