<?php
/**
 * /products/{handle} — routed here by MMM\Shopify\ProductRoute.
 */

use MMM\Controllers\ProductController;

$controller = new ProductController();
$controller->render();
