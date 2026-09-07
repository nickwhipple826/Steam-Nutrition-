import { qsa } from "../core/dom.js";

/**
 * Initializes active tab components with keyboard navigation.
 *
 * Supports arrow key navigation and manages ARIA attributes automatically.
 * Each tab component must have matching tabs & panels.
 *
 * @example
 * ``` html
 * <div data-tabs>
 *   <div role="tablist">
 *     <button role="tab" aria-selected="true">Tab 1</button>
 *     <button role="tab">Tab 2</button>
 *   </div>
 *
 *   <div role="tabpanel">Panel 1</div>
 *   <div role="tabpanel" hidden>Panel 2</div>
 * </div>
 * ```
 */
export function initTabs() {
  qsa<HTMLElement>( '[data-tabs]' ).forEach( ( root ) => {
    const tabs = qsa<HTMLButtonElement>( '[role="tab"]',root );
    const panels = qsa<HTMLElement>( '[role="tabpanel"]',root );

    /**
     * Handler to activate a given tab
     * @param index
     */
    const activate = ( index: number ) => {
      tabs.forEach( ( tab,i ) => {
        tab.setAttribute( 'aria-selected',String( i === index ) );
        tab.tabIndex = i === index ? 0 : -1;

        const panel = panels[i];
        if ( panel ) {
          panel.hidden = i !== index;
        }
      } );
    }

    tabs.forEach( ( tab,index ) => {
      tab.addEventListener( 'click',() => activate( index ) );
      tab.addEventListener( 'keydown',( e ) => {
        if ( e.key === 'ArrowRight' ) activate( (index + 1) % tabs.length );
        if ( e.key === 'ArrowLeft' ) activate( (index - 1 + tabs.length) % tabs.length );
      } );
    } )
  } );
}