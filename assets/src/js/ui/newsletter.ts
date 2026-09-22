import { qs, qsa } from '../core/dom.js';

/**
 * Newsletter band.
 *
 * With an action attribute the form posts normally and this only guards
 * against an obviously malformed address, so a typo does not cost a
 * round trip. Without one there is nowhere to post, so it reports
 * success locally — fine on staging, silently lossy in production,
 * which is why the field carries that warning in the editor.
 */
export function initNewsletter(): void {
  qsa<HTMLFormElement>( '[data-newsletter]' ).forEach( ( form ) => {
    const status = qs<HTMLElement>( '[data-newsletter-status]', form.parentElement ?? document.body );
    const input = qs<HTMLInputElement>( 'input[type="email"]', form );

    if ( !input ) return;

    const setStatus = ( message: string, state: 'ok' | 'error' ) => {
      if ( !status ) return;
      status.textContent = message;
      status.dataset.state = state;
    };

    form.addEventListener( 'submit', ( event ) => {
      const value = input.value.trim();

      // Deliberately loose. Anything stricter starts rejecting valid
      // addresses, and the provider validates properly anyway.
      if ( !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( value ) ) {
        event.preventDefault();
        setStatus( 'That address looks incomplete — check it and try again.', 'error' );
        input.focus();
        return;
      }

      if ( !form.getAttribute( 'action' ) ) {
        event.preventDefault();
        setStatus( form.dataset.success ?? 'Thanks — you are on the list.', 'ok' );
        form.reset();
      }
    } );
  } );
}
