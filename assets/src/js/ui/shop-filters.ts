import { qs, qsa } from '../core/dom.js';

export function initShopFilters() {
  qsa<HTMLElement>( '.shop-collection' ).forEach( ( root ) => {
    const sortSelect = qs<HTMLSelectElement>( '[data-shop-sort]', root );
    const grid = qs<HTMLElement>( '[data-product-grid]', root );
    const form = qs<HTMLFormElement>( '[data-filter-form]', root );
    const saveBtn = qs<HTMLButtonElement>( '[data-filter-save]', root );
    const resetBtn = qs<HTMLButtonElement>( '[data-filter-reset]', root );

    const buildParams = (): URLSearchParams => {
      const params = new URLSearchParams( window.location.search );

      if ( sortSelect ) {
        params.set( 'sort', sortSelect.value );
      }

      Array.from( params.keys() )
        .filter( ( key ) => key.startsWith( 'filter[' ) )
        .forEach( ( key ) => params.delete( key ) );

      if ( form ) {
        qsa<HTMLInputElement>( 'input:checked', form ).forEach( ( input ) => {
          params.append( input.name, input.value );
        } );
      }

      return params;
    };

    const refresh = async () => {
      const params = buildParams();
      const url = `${window.location.pathname}?${params.toString()}`;

      history.pushState( {}, '', url );

      const response = await fetch( url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } } );
      const html = await response.text();
      const doc = new DOMParser().parseFromString( html, 'text/html' );
      const newGrid = doc.querySelector( '[data-product-grid]' );

      if ( grid && newGrid ) {
        grid.innerHTML = newGrid.innerHTML;
      }
    };

    sortSelect?.addEventListener( 'change', refresh );
    saveBtn?.addEventListener( 'click', refresh );

    resetBtn?.addEventListener( 'click', () => {
      if ( !form ) return;
      qsa<HTMLInputElement>( 'input', form ).forEach( ( input ) => {
        input.checked = false;
      } );
    } );
  } );
}