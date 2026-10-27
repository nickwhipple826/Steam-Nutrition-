<?php

namespace MMM\Shopify;

use MMM\Traits\Singleton;
use WP_REST_Request;
use WP_REST_Response;

/**
 * /wp-json/steam/v1/cart… — what ui/cart.ts talks to.
 *
 * Every route is POST, reads included. WP Engine's page cache never
 * stores a POST, so one shopper's bag can never be served to another.
 *
 * No nonce: a forged request could at worst put something in a
 * stranger's bag, and a nonce would go stale behind the page cache.
 * The same reasoning the product page's Add to bag form uses.
 */
class CartApi
{
  use Singleton;

  public const NAMESPACE = 'steam/v1';

  public function routes(): void
  {
    $routes = [
      '/cart' => 'read',
      '/cart/add' => 'add',
      '/cart/update' => 'update',
      '/cart/remove' => 'remove',
    ];

    foreach ( $routes as $path => $method ) {
      register_rest_route( self::NAMESPACE, $path, [
        'methods' => 'POST',
        'callback' => [ $this, $method ],
        'permission_callback' => '__return_true',
      ] );
    }
  }

  public function read(): WP_REST_Response
  {
    return $this->respond( [ 'cart' => ShopifyCart::current(), 'error' => '' ] );
  }

  public function add( WP_REST_Request $request ): WP_REST_Response
  {
    return $this->respond( ShopifyCart::add(
      sanitize_text_field( (string) $request->get_param( 'variant' ) ),
      (int) ( $request->get_param( 'quantity' ) ?? 1 )
    ) );
  }

  public function update( WP_REST_Request $request ): WP_REST_Response
  {
    return $this->respond( ShopifyCart::update(
      sanitize_text_field( (string) $request->get_param( 'line' ) ),
      (int) $request->get_param( 'quantity' )
    ) );
  }

  public function remove( WP_REST_Request $request ): WP_REST_Response
  {
    return $this->respond( ShopifyCart::remove(
      sanitize_text_field( (string) $request->get_param( 'line' ) )
    ) );
  }

  /**
   * @param array{cart: ?array, error: string} $result
   */
  private function respond( array $result ): WP_REST_Response
  {
    $response = new WP_REST_Response( [
      'cart' => $result['cart'] ?? ShopifyCart::empty(),
      'error' => $result['error'],
    ], $result['error'] && ! $result['cart'] ? 422 : 200 );

    $response->header( 'Cache-Control', 'no-store' );

    return $response;
  }

  private function init(): void
  {
    add_action( 'rest_api_init', [ $this, 'routes' ] );
  }
}
