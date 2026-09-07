import { qsa } from "../core/dom.js";

/**
 *
 */
const FOCUSABLE_SELECTORS: string = [
  'a[href]',
  'area[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  'iframe',
  'object',
  'embed',
  '[contenteditable]',
  '[tabindex]:not([tabindex="-1"])'
].join( ',' );

/**
 * Returns all focusable elements contained in the given node.
 * @param container The node to search, default `document`.
 */
export function getFocusableElements(
  container: ParentNode = document,
): HTMLElement[] {
  return qsa<HTMLElement>( FOCUSABLE_SELECTORS,container )
    .filter( ( el ) => !el.hasAttribute( 'disabled' ) );
}

/**
 * Focuses the first focusable element in the container.
 * @param container The parent node
 */
export function focusFirst( container: ParentNode ): void {
  const [first] = getFocusableElements( container );
  first?.focus( { preventScroll: true } );
}

/**
 * Traps event's focus within the parent container
 * @param container The parent container
 * @param event A keyboard event
 */
export function trapFocus(
  container: HTMLElement,
  event: KeyboardEvent,
): void {
  if ( event.key !== 'Tab' ) return;

  const focusable = getFocusableElements( container );
  if ( focusable.length === 0 ) return;

  const first = focusable[0] as HTMLElement;
  const last = focusable[focusable.length - 1] as HTMLElement;
  const active = document.activeElement as HTMLElement | null;

  if ( event.shiftKey && active === first ) {
    event.preventDefault();
    last.focus( { preventScroll: true } );
  } else if ( !event.shiftKey && active === last ) {
    event.preventDefault();
    first.focus( { preventScroll: true } );
  }
}

/**
 * Stores the currently focused element for later.
 */
export function storeFocus(): HTMLElement | null {
  return document.activeElement as HTMLElement | null;
}

/**
 * Restores focus to the given element.
 * @param el
 */
export function restoreFocus( el: HTMLElement | null ): void {
  el?.focus( { preventScroll: true } );
}