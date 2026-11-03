<?php
/**
 * /cart, /account/… and /search — routed here by MMM\Shopify\StoreRoutes.
 */

use MMM\Controllers\{AccountController, CartController, SearchController};
use MMM\Shopify\StoreRoutes;

$route = StoreRoutes::route();

$controller = match ( $route ) {
  'cart' => new CartController(),
  'search' => new SearchController(),
  default => new AccountController( $route ),
};
$controller->render();
