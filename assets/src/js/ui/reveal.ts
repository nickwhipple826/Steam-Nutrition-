/**
 * Site-wide scroll reveal.
 *
 * Every top-level block in <main> — plus the footer — fades in as it
 * scrolls into view. Nothing has to opt in: a section added next year
 * animates without anyone touching this file.
 *
 * A section's Motion tab can override the effect, delay and duration,
 * or switch it off. Those land as data-animate / data-delay /
 * data-duration on the [data-block] wrapper that page.twig puts around
 * each flexible-content component.
 *
 * Content is only hidden while <html> carries .js-reveal, which an
 * inline script in base.twig adds before first paint. If this file
 * never runs, that script's own timeout removes the class, so a broken
 * bundle degrades to a plain page rather than a blank one.
 */

/**
 * Blocks come from one of three places: a direct child of <main>, an
 * explicit wrapper anywhere (the flexible-content components on the
 * shop pages sit deep inside a section), or the footer.
 */
const BLOCK_SELECTOR = 'main > *, [data-block], .site-footer';

/**
 * An element that is a plate in its own right, so it is revealed as
 * one piece rather than being descended into.
 */
const SELF_REVEALING = 'section, .section, [data-scheme], [data-block], .site-footer';

const STAGGER_CHILDREN = 'ul > li, ol > li';

/** Past this, extra children all share the last step's delay. */
const STAGGER_CAP = 12;

/**
 * Which element(s) actually get revealed for a given candidate.
 *
 * The shop templates wrap their whole page in a plain <div> holding two
 * sections and the filter drawer. Revealing that wrapper would fade the
 * page as one slab, and — because transform makes an element the
 * containing block for its fixed descendants — would also pull the
 * fixed filter drawer out of the viewport for as long as the animation
 * ran. So an unmarked wrapper is descended into and its sections are
 * revealed instead, which leaves the drawer alone.
 */
function resolveTargets( candidate: HTMLElement ): HTMLElement[] {
  if ( candidate.matches( SELF_REVEALING ) ) {
    return [ candidate ];
  }

  const sections = Array.from(
    candidate.querySelectorAll<HTMLElement>( ':scope > section, :scope > .section' ),
  );

  return sections.length ? sections : [ candidate ];
}

export function initReveal(): void {
  const html = document.documentElement;

  // base.twig watches for this. Set it before the early returns below,
  // so a page with nothing to reveal does not sit under the timeout.
  ( window as unknown as { __revealReady?: boolean } ).__revealReady = true;

  if ( !( 'IntersectionObserver' in window ) ) {
    html.classList.remove( 'js-reveal' );
    return;
  }

  const candidates = Array.from( document.querySelectorAll<HTMLElement>( BLOCK_SELECTOR ) )
    .filter( ( el ) => !el.hasAttribute( 'data-no-reveal' ) );

  const targets = new Set<HTMLElement>();
  candidates.forEach( ( candidate ) => {
    resolveTargets( candidate ).forEach( ( target ) => targets.add( target ) );
  } );

  // A wrapper inside an already-revealed block would fade on its own
  // curve inside its parent's, double-dipping the opacity. Keep the
  // outermost of any nested pair.
  const blocks = Array.from( targets ).filter( ( el ) => {
    for ( let parent = el.parentElement; parent; parent = parent.parentElement ) {
      if ( targets.has( parent ) ) return false;
    }
    return true;
  } );

  if ( !blocks.length ) {
    html.classList.remove( 'js-reveal' );
    return;
  }

  // data-reveal first, .reveal-ready second, and both inside this one
  // synchronous task — see the pre-hide note in base/_reveal.scss. If
  // the order slipped, or a paint landed between them, the blocks would
  // flash visible for a frame before dropping back to opacity 0.
  blocks.forEach( ( block ) => {
    const type = block.dataset.animate || 'fade-up';
    block.dataset.reveal = type;

    const delay = Number.parseInt( block.dataset.delay ?? '', 10 );
    const duration = Number.parseInt( block.dataset.duration ?? '', 10 );
    if ( delay > 0 ) block.style.setProperty( '--reveal-delay', `${delay}ms` );
    if ( duration > 0 ) block.style.setProperty( '--reveal-duration', `${duration}ms` );

    // Stagger: the plate stays put and its cards arrive one after another.
    if ( type === 'stagger' ) {
      block.querySelectorAll<HTMLElement>( STAGGER_CHILDREN ).forEach( ( child, i ) => {
        child.dataset.revealChild = '';
        child.style.setProperty( '--i', String( Math.min( i, STAGGER_CAP ) ) );
      } );
    }
  } );

  html.classList.add( 'reveal-ready' );

  const observer = new IntersectionObserver( ( entries ) => {
    entries.forEach( ( entry ) => {
      if ( !entry.isIntersecting ) return;
      entry.target.classList.add( 'is-revealed' );
      observer.unobserve( entry.target );
    } );
  }, {
    // Holds the reveal until the block is properly on screen rather
    // than firing on its first pixel.
    rootMargin: '0px 0px -8% 0px',
    threshold: 0,
  } );

  blocks.forEach( ( block ) => observer.observe( block ) );
}