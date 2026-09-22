import { qs, qsa } from '../core/dom.js';

/**
 * Price readout for the Subscribe & Save panel.
 *
 * The figure is server-rendered from the first plan, so this only takes
 * over once someone changes the selection. If the script never runs the
 * panel still shows a correct price for the checked radio.
 */
export function initSubscribe(): void {
  qsa<HTMLElement>( '[data-subscribe]' ).forEach( ( panel ) => {
    const basePrice = parseFloat( panel.dataset.basePrice ?? '' );
    const priceEl = qs<HTMLElement>( '[data-subscribe-price]', panel );
    const perEl = qs<HTMLElement>( '[data-subscribe-per]', panel );
    const savingEl = qs<HTMLElement>( '[data-subscribe-saving]', panel );
    const inputs = qsa<HTMLInputElement>( 'input[type="radio"]', panel );

    if ( Number.isNaN( basePrice ) || inputs.length === 0 ) return;

    const currency = new Intl.NumberFormat( 'en-US', {
      style: 'currency',
      currency: 'USD',
    } );

    const update = ( input: HTMLInputElement ) => {
      const days = Number( input.value );
      const discount = Number( input.dataset.discount ?? 0 );
      const price = basePrice * ( 1 - discount / 100 );

      if ( priceEl ) priceEl.textContent = currency.format( price );
      if ( perEl ) perEl.textContent = `per tub, billed every ${ days } days`;
      if ( savingEl ) savingEl.textContent = `Save ${ discount }%`;
    };

    inputs.forEach( ( input ) => {
      input.addEventListener( 'change', () => update( input ) );
    } );
  } );
}
