<?php
/**
 * /cart and /account/… — routed here by MMM\Shopify\StoreRoutes.
 */

use MMM\Controllers\{AccountController, CartController};
use MMM\Shopify\StoreRoutes;

$route = StoreRoutes::route();

$controller = 'cart' === $route ? new CartController() : new AccountController( $route );
$controller->render();
