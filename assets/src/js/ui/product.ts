import { qs, qsa } from '../core/dom.js';

interface VariantImage {
  url: string;
  alt: string;
}

interface Variant {
  id: string;
  available: boolean;
  price: string | null;
  compare_at: string | null;
  currency: string;
  options: Record<string, string>;
  image: VariantImage | null;
}

/**
 * Product page: keeps price, image, flag and the Buy button in step
 * with the option radios, and lets the thumbnails swap the main image.
 *
 * The page is already correct for the variant the server picked, and
 * the form already posts the radios, so if this never runs nothing is
 * broken — the shopper just sees the new price on the next page.
 */
export function initProduct(): void {
  const root = qs<HTMLElement>( '[data-product]' );
  if ( !root ) return;

  const stage = qs<HTMLImageElement>( '[data-product-stage]', root );
  const thumbs = qsa<HTMLButtonElement>( '[data-product-thumb]', root );
  const layout = qs<HTMLElement>( '[data-product-layout]', root );

  // Wide photo → wide gallery column. Read from the loaded image, so it
  // is right whatever shape the next variant's photo turns out to be.
  const fitLayout = () => {
    if ( !stage || !layout || !stage.naturalWidth ) return;
    layout.classList.toggle( 'product__layout--wide', stage.naturalWidth > stage.naturalHeight );
  };

  stage?.addEventListener( 'load', fitLayout );
  if ( stage?.complete ) fitLayout();

  const showImage = ( url: string, alt: string ) => {
    if ( !stage || !url ) return;

    stage.src = url;
    stage.alt = alt;
    // The width/height belonged to the old image; letting CSS size it
    // avoids a stretched frame while the new one loads.
    stage.removeAttribute( 'width' );
    stage.removeAttribute( 'height' );

    thumbs.forEach( ( thumb ) => {
      const current = thumb.dataset.src === url;
      thumb.classList.toggle( 'is-current', current );
      if ( current ) {
        thumb.setAttribute( 'aria-current', 'true' );
      } else {
        thumb.removeAttribute( 'aria-current' );
      }
    } );
  };

  thumbs.forEach( ( thumb ) => {
    thumb.addEventListener( 'click', () => {
      showImage( thumb.dataset.src ?? '', thumb.dataset.alt ?? '' );
    } );
  } );

  // ---------------------------------------------------------------
  // Variant selection
  // ---------------------------------------------------------------
  const json = qs<HTMLScriptElement>( '[data-product-json]', root );
  const form = qs<HTMLFormElement>( '[data-product-form]', root );
  const radios = qsa<HTMLInputElement>( '[data-product-option]', root );

  if ( !json || !form || radios.length === 0 ) return;

  let variants: Variant[] = [];
  try {
    variants = JSON.parse( json.textContent ?? '[]' ) as Variant[];
  } catch {
    return;
  }

  const idInput = qs<HTMLInputElement>( '[data-product-variant]', form );
  const priceEl = qs<HTMLElement>( '[data-product-price]', root );
  const compareEl = qs<HTMLElement>( '[data-product-compare]', root );
  const flagEl = qs<HTMLElement>( '[data-product-flag]', root );
  const submit = qs<HTMLButtonElement>( '[data-product-submit]', form );

  const money = ( amount: string | null, currency: string ): string => {
    if ( amount === null ) return '';
    return new Intl.NumberFormat( 'en-US', { style: 'currency', currency } )
      .format( parseFloat( amount ) );
  };

  const selected = (): Record<string, string> => {
    const picked: Record<string, string> = {};
    radios.forEach( ( radio ) => {
      if ( radio.checked ) picked[ radio.dataset.productOption ?? '' ] = radio.value;
    } );
    return picked;
  };

  const find = ( picked: Record<string, string> ): Variant | undefined =>
    variants.find( ( v ) =>
      Object.entries( v.options ).every( ( [ name, value ] ) => picked[ name ] === value ),
    );

  const update = () => {
    const picked = selected();

    Object.entries( picked ).forEach( ( [ name, value ] ) => {
      const label = qsa<HTMLElement>( '[data-product-option-label]', root )
        .find( ( el ) => el.dataset.productOptionLabel === name );
      if ( label ) label.textContent = value;
    } );

    const variant = find( picked );

    if ( submit ) {
      submit.disabled = !variant || !variant.available;
      submit.textContent = !variant ? 'Unavailable' : variant.available ? 'Buy now' : 'Sold out';
    }

    if ( !variant ) return;

    if ( idInput ) idInput.value = variant.id;
    if ( priceEl ) priceEl.textContent = money( variant.price, variant.currency );

    if ( compareEl ) {
      compareEl.textContent = money( variant.compare_at, variant.currency );
      compareEl.hidden = !variant.compare_at;
    }

    if ( flagEl ) flagEl.hidden = !variant.compare_at;

    if ( variant.image ) showImage( variant.image.url, variant.image.alt );

    // Keep the URL shareable: a link to the chocolate tub opens on it.
    const url = new URL( window.location.href );
    url.searchParams.set( 'variant', variant.id.split( '/' ).pop() ?? '' );
    window.history.replaceState( null, '', url );
  };

  radios.forEach( ( radio ) => radio.addEventListener( 'change', update ) );
}
