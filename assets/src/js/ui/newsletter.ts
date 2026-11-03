import { qs, qsa } from '../core/dom.js';

/**
 * Newsletter band.
 *
 * Three modes, decided by the markup newsletter-band.twig writes:
 *
 * - data-newsletter-api: the welcome offer. The address goes to
 *   /wp-json/steam/v1/newsletter (NewsletterApi.php), which subscribes
 *   it in Shopify and returns a one-time code; the form swaps for the
 *   code and a Copy button.
 * - action attribute: posts to that URL (Klaviyo, Mailchimp) normally;
 *   this only catches an obviously malformed address first.
 * - neither: nowhere to send it, so it reports success locally — fine
 *   on staging, silently lossy in production, which is why the editor
 *   field says so.
 */

interface SignupResponse {
  status: 'issued' | 'existing' | 'ineligible' | 'subscribed' | 'error';
  code: string;
  message: string;
}

// Deliberately loose. Anything stricter starts rejecting valid
// addresses, and the server validates properly anyway.
const EMAIL = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

export function initNewsletter(): void {
  qsa<HTMLFormElement>( '[data-newsletter]' ).forEach( ( form ) => {
    const scope = form.parentElement ?? document.body;
    const status = qs<HTMLElement>( '[data-newsletter-status]', scope );
    const input = qs<HTMLInputElement>( 'input[type="email"]', form );
    const api = form.dataset.newsletterApi;

    if ( !input ) return;

    const setStatus = ( message: string, state: 'ok' | 'error' ) => {
      if ( !status ) return;
      status.textContent = message;
      status.dataset.state = state;
    };

    form.addEventListener( 'submit', ( event ) => {
      const value = input.value.trim();

      if ( !EMAIL.test( value ) ) {
        event.preventDefault();
        setStatus( 'That address looks incomplete — check it and try again.', 'error' );
        input.focus();
        return;
      }

      if ( api ) {
        event.preventDefault();
        void submitToApi( api, value );
        return;
      }

      if ( !form.getAttribute( 'action' ) ) {
        event.preventDefault();
        setStatus( form.dataset.success ?? 'Thanks — you are on the list.', 'ok' );
        form.reset();
      }
    } );

    // -----------------------------------------------------------------
    // Welcome offer
    // -----------------------------------------------------------------
    const reward = qs<HTMLElement>( '[data-newsletter-reward]', scope );
    const codeEl = qs<HTMLElement>( '[data-newsletter-code]', scope );
    const copyBtn = qs<HTMLButtonElement>( '[data-newsletter-copy]', scope );
    const submitBtn = qs<HTMLButtonElement>( 'button[type="submit"]', form );
    let busy = false;

    const submitToApi = async ( url: string, email: string ): Promise<void> => {
      if ( busy ) return;
      busy = true;

      const label = submitBtn?.textContent ?? '';
      if ( submitBtn ) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing up…';
      }
      form.setAttribute( 'aria-busy', 'true' );
      setStatus( '', 'ok' );

      try {
        const company = qs<HTMLInputElement>( 'input[name="company"]', form )?.value ?? '';
        const response = await fetch( url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          body: JSON.stringify( { email, company } ),
        } );

        const data = ( await response.json().catch( () => null ) ) as SignupResponse | null;

        if ( !data || data.status === 'error' ) {
          setStatus( data?.message ?? 'Something went wrong. Please try again in a minute.', 'error' );
          return;
        }

        setStatus( data.message, 'ok' );

        if ( data.code && reward && codeEl ) {
          codeEl.textContent = data.code;
          reward.hidden = false;
          form.hidden = true;
          // Focus the code's Copy button so a keyboard or screen-reader
          // user lands on the thing they just earned.
          copyBtn?.focus();
        } else {
          form.reset();
        }
      } catch ( error ) {
        console.error( 'Newsletter signup failed:', error );
        setStatus( 'Could not reach the server. Check your connection and try again.', 'error' );
      } finally {
        busy = false;
        form.removeAttribute( 'aria-busy' );
        if ( submitBtn ) {
          submitBtn.disabled = false;
          submitBtn.textContent = label;
        }
      }
    };

    copyBtn?.addEventListener( 'click', async () => {
      const code = codeEl?.textContent?.trim();
      if ( !code ) return;

      try {
        await navigator.clipboard.writeText( code );
      } catch {
        // Clipboard blocked (http, old browser): select the text so a
        // manual copy is one keystroke away.
        const range = document.createRange();
        if ( codeEl ) range.selectNodeContents( codeEl );
        const selection = window.getSelection();
        selection?.removeAllRanges();
        selection?.addRange( range );
        return;
      }

      copyBtn.textContent = 'Copied';
      copyBtn.classList.add( 'is-copied' );
      window.setTimeout( () => {
        copyBtn.textContent = 'Copy';
        copyBtn.classList.remove( 'is-copied' );
      }, 2000 );
    } );
  } );
}
