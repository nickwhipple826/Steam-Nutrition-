<?php

namespace MMM\Controllers;

use MMM\Shopify\{ShopifyCart, ShopifyCollectionQuery};

/**
 * /cart — the full bag page.
 *
 * Works with JavaScript off: the quantity and remove controls are plain
 * forms that POST here, and every POST redirects back (303) so a reload
 * never re-submits. The drawer (ui/cart.ts) is the in-place version.
 */
class CartController extends BaseController
{
  public function render(): void
  {
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
      $this->handlePost();
    }

    $cart = ShopifyCart::current() ?? ShopifyCart::empty();

    status_header( 200 );

    $this->renderView( 'pages/cart.twig', [
      'cart' => $cart,
      'configured' => ShopifyCollectionQuery::isConfigured(),
      'error' => isset( $_GET['cart_error'] ) ? sanitize_text_field( wp_unslash( $_GET['cart_error'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      'page_title' => 'Your bag | ' . get_bloginfo( 'name' ),
    ] );
  }

  private function handlePost(): void
  {
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // No nonce, for the reason given in CartApi.
    $action = sanitize_key( wp_unslash( $_POST['cart_action'] ?? '' ) );
    $line = sanitize_text_field( wp_unslash( $_POST['line'] ?? '' ) );
    $quantity = (int) ( $_POST['quantity'] ?? 0 );
    // phpcs:enable

    $result = match ( $action ) {
      'update' => ShopifyCart::update( $line, $quantity ),
      'remove' => ShopifyCart::remove( $line ),
      default => [ 'cart' => null, 'error' => '' ],
    };

    $url = home_url( '/cart/' );

    if ( $result['error'] ) {
      $url = add_query_arg( 'cart_error', rawurlencode( $result['error'] ), $url );
    }

    wp_safe_redirect( $url, 303 );
    exit;
  }
}
