import Swiper from "swiper";
import { A11y,Autoplay,Keyboard,Navigation,Pagination } from "swiper/modules";

import { qs,qsa } from '../core/dom.js';
import { dataBool,dataInt,dataStr } from "../core/data.js";

// Mirrors this theme's CSS breakpoint exactly: `@media (max-width: 1000px)
// or (orientation: portrait)`. Swiper's own `breakpoints` option only
// checks window width, so a tall-but-technically-wide portrait window
// could pass this theme's CSS mobile styles while Swiper still thought
// it was desktop — this keeps both systems in agreement.
const MOBILE_QUERY = '(max-width: 1000px), (orientation: portrait)';

/**
 * Initializes all Swiper instances on the page.
 *
 * @example
 * ```html
 * <div class='swiper'
 *   data-slider-navigation="true"
 *   data-slider-pagination="true"
 *   data-slider-pagination-type="bullets"
 *   data-slider-autoplay="true"
 *   data-slider-autoplay-delay="5000"
 *   data-slider-pause-on-hover="true"
 *   data-slider-loop="true"
 *   data-slider-keyboard="true"
 *   data-slider-slides-per-view="3"
 *   data-slider-slides-per-view-mobile="1"
 *   data-slider-disable-mobile="true"
 *   data-slider-space-between="20"
 *   data-slider-centered="false"
 *   data-slider-speed="400"
 *   data-slider-grab-cursor="true"
 * >
 *   <div class='swiper-wrapper'>
 *     <div class='swiper-slide'>Slide 1</div>
 *     <div class='swiper-slide'>Slide 2</div>
 *     <div class='swiper-slide'>Slide 3</div>
 *     <div class='swiper-slide'>Slide 4</div>
 *   </div>
 *   <button class="swiper-button-prev"></button>
 *   <button class="swiper-button-next"></button>
 *   <div class="swiper-pagination"></div>
 * </div>
 * ```
 */
export function initSliders(): Swiper[] {
  const instances: Swiper[] = [];
  const mobileQueryList = window.matchMedia( MOBILE_QUERY );

  qsa<HTMLElement>( '.swiper' ).forEach( ( element ) => {
    if ( element.dataset.swiperHandled === 'true' ) return;
    element.dataset.swiperHandled = 'true';

    const wrapper = qs( '.swiper-wrapper',element );
    const slides = qsa( '.swiper-slide',element );

    if ( !wrapper || slides.length === 0 ) {
      console.warn( 'Swiper: Invalid structure',element );
      return;
    }

    // When true, no Swiper instance runs at all on mobile — the
    // slides fall back to plain CSS stacking instead of just showing
    // fewer slides per view. The instance is created/destroyed live
    // as the viewport crosses the breakpoint.
    const disableOnMobile = dataBool( element,'sliderDisableMobile' );
    const shouldRun = () => !( disableOnMobile && mobileQueryList.matches );

    let swiper: Swiper | null = null;

    const create = () => {
      if ( swiper ) return;

      try {
        // Navigation
        const navigationEnabled = dataBool( element,'sliderNavigation' );
        const prev = qs<HTMLButtonElement>( 'button.swiper-button-prev',element );
        const next = qs<HTMLButtonElement>( 'button.swiper-button-next',element );
        const hasValidNavigation = navigationEnabled && prev && next;

        // Pagination
        const paginationEnabled = dataBool( element,'sliderPagination' );
        const paginationEl = qs<HTMLElement>( '.swiper-pagination',element );
        const paginationTypeRaw = dataStr( element,'sliderPaginationType','bullets' );
        const paginationType: 'bullets' | 'fraction' | 'progressbar' | 'custom' =
          ['bullets','fraction','progressbar','custom'].includes( paginationTypeRaw )
            ? paginationTypeRaw as 'bullets' | 'fraction' | 'progressbar' | 'custom'
            : 'bullets';

        // Autoplay
        const autoplayEnabled = dataBool( element,'sliderAutoplay' );
        const autoplayDelay = dataInt( element,'sliderAutoplayDelay',3000 );
        const pauseOnHover = dataBool( element,'sliderPauseOnHover',true );

        // Slides configuration
        const desktopSlidesPerView = dataInt( element,'sliderSlidesPerView',1 );
        // Optional — defaults to matching desktop so existing sliders
        // that don't set this attribute behave exactly as before.
        const mobileSlidesPerView = dataInt( element,'sliderSlidesPerViewMobile',desktopSlidesPerView );
        const spaceBetween = dataInt( element,'sliderSpaceBetween',0 );
        const centeredSlides = dataBool( element,'sliderCentered' );

        const currentSlidesPerView = () =>
          mobileQueryList.matches ? mobileSlidesPerView : desktopSlidesPerView;

        // Behavior
        const loop = dataBool( element,'sliderLoop' );
        const speed = dataInt( element,'sliderSpeed',300 );
        const keyboardEnabled = dataBool( element,'sliderKeyboard' );
        const grabCursor = dataBool( element,'sliderGrabCursor',true );

        // Only activate needed modules
        const modules = [A11y];
        if ( hasValidNavigation ) modules.push( Navigation );
        if ( paginationEnabled && paginationEl ) modules.push( Pagination );
        if ( autoplayEnabled ) modules.push( Autoplay );
        if ( keyboardEnabled ) modules.push( Keyboard );

        swiper = new Swiper( element,{
          modules,

          // Layout — computed ourselves via MOBILE_QUERY rather than
          // Swiper's own `breakpoints` option, which only checks window
          // width and would miss the "or portrait" half of our condition.
          slidesPerView: currentSlidesPerView(),
          spaceBetween,
          centeredSlides,

          // Behavior
          loop,
          speed,
          grabCursor,
          watchOverflow: true,

          navigation: hasValidNavigation ? {
            prevEl: prev,
            nextEl: next,
          } : false,

          pagination: paginationEnabled && paginationEl ? {
            el: paginationEl,
            type: paginationType,
            clickable: true,
          } : false,

          autoplay: autoplayEnabled ? {
            delay: autoplayDelay,
            disableOnInteraction: false,
            pauseOnMouseEnter: pauseOnHover,
          } : false,

          keyboard: keyboardEnabled ? {
            enabled: true,
            onlyInViewport: true,
          } : false,

          a11y: {
            enabled: true,
            prevSlideMessage: 'Previous slide',
            nextSlideMessage: 'Next slide',
            firstSlideMessage: 'This is the first slide',
            lastSlideMessage: 'This is the last slide',
            paginationBulletMessage: 'Go to slide {{index}}',
          },

          on: {
            init: function () {
              element.dataset.swiperInitialized = 'true';
            },
            beforeDestroy: function () {
              delete element.dataset.swiperInitialized;
            }
          }
        } );

        // Only wire up live-updating if this slider actually has a
        // distinct mobile count — otherwise there's nothing to react to.
        if ( mobileSlidesPerView !== desktopSlidesPerView ) {
          mobileQueryList.addEventListener( 'change',() => {
            if ( swiper ) {
              swiper.params.slidesPerView = currentSlidesPerView();
              swiper.update();
            }
          } );
        }

        instances.push( swiper );

      } catch ( error ) {
        console.error( 'Swiper: Failed to initialize',element,error );
      }
    };

    const destroy = () => {
      if ( !swiper ) return;
      swiper.destroy( true,true );
      swiper = null;
    };

    if ( shouldRun() ) create();

    if ( disableOnMobile ) {
      mobileQueryList.addEventListener( 'change',() => {
        if ( shouldRun() ) {
          create();
        } else {
          destroy();
        }
      } );
    }
  } );

  return instances;
}