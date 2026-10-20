import { initNav } from './ui/nav.js';
import { initHeader } from './ui/header.js';
import { initModals } from './ui/modal.js';
import { initShopFilters } from './ui/shop-filters.js';
import { initRails } from './ui/rails.js';
import { initSubscribe } from './ui/subscribe.js';
import { initNewsletter } from './ui/newsletter.js';

import '@css/main.scss';

document.addEventListener( 'DOMContentLoaded', () => {
  const modules: { name: string, fn: () => any }[] = [
    { name: 'nav', fn: initNav },
    { name: 'header', fn: initHeader },
    { name: 'modals', fn: initModals },
    { name: 'shop-filters', fn: initShopFilters },
    { name: 'rails', fn: initRails },
    { name: 'subscribe', fn: initSubscribe },
    { name: 'newsletter', fn: initNewsletter },
  ];

  modules.forEach( ( { name, fn } ) => {
    try {
      fn();
    } catch ( error ) {
      console.error( `Failed to initialize ${name}:`, error );
    }
  } );
} );
