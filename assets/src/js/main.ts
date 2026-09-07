import { initNav } from './ui/nav.js';
import { initTabs } from './ui/tabs.js';
import { initModals } from './ui/modal.js';
import { initSliders } from './ui/slider.js';
import { initAccordions } from './ui/accordion.js';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import '@css/main.scss';

document.addEventListener( 'DOMContentLoaded', () => {
  const modules: { name: string, fn: () => any }[] = [
    { name: 'nav', fn: initNav },
    { name: 'tabs', fn: initTabs },
    { name: 'modals', fn: initModals },
    { name: 'sliders', fn: initSliders },
    { name: 'accordions', fn: initAccordions },
  ];

  modules.forEach( ( { name, fn } ) => {
    try {
      fn();
    } catch ( error ) {
      console.error( `Failed to initialize ${name}:`, error );
    }
  } );
} );