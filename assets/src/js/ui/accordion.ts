import { qsa } from "../core/dom.js";

/**
 * Initializes all accordions. Ensures only one `<details>` element can be open at a time.
 *
 * @example
 * ```html
 * <div data-accordion>
 *   <details>
 *     <summary>Question 1</summary>
 *     <div>Answer</div>
 *   </details>
 *     <summary>Question 2</summary>
 *     <div>Answer</div>
 *   </details>
 * </div>
 * ```
 */
export function initAccordions() {
  qsa<HTMLElement>( '[data-accordion]' ).forEach( ( accordion ) => {
    const items = qsa<HTMLDetailsElement>( 'details',accordion );

    items.forEach( ( item ) => {
      item.addEventListener( 'toggle', () => {
        if ( !item.open ) return;

        items.forEach( ( other ) => {
          if ( other !== item ) other.open = false;
        } )
      } )
    } )
  } )
}