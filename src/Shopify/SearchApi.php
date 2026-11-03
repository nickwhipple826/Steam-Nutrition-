<?php

namespace MMM\Shopify;

use MMM\Traits\Singleton;
use WP_REST_Request;
use WP_REST_Response;

/**
 * GET /wp-json/steam/v1/search?q=hood — what ui/search.ts asks as the
 * shopper types in the header.
 *
 * Unlike the cart routes this is GET and cacheable: the answer is the
 * same for everyone, so letting WP Engine's cache hold it for a minute
 * is a feature, not a leak.
 */
class SearchApi
{
  use Singleton;

  public function routes(): void
  {
    register_rest_route( CartApi::NAMESPACE, '/search', [
      'methods' => 'GET',
      'callback' => [ $this, 'search' ],
      'permission_callback' => '__return_true',
      'args' => [
        'q' => [ 'type' => 'string', 'required' => true ],
      ],
    ] );
  }

  public function search( WP_REST_Request $request ): WP_REST_Response
  {
    $query = ShopifySearch::normalise( (string) $request->get_param( 'q' ) );
    $found = ShopifySearch::predictive( $query );

    $response = new WP_REST_Response( [
      'query' => $query,
      'products' => array_map( fn( array $p ) => [
        'title' => $p['title'],
        'url' => home_url( '/products/' . $p['handle'] . '/' ),
        'image' => $p['image'],
        'alt' => $p['alt'],
        'type' => $p['type'],
        'price' => $p['price'],
        'compare_at' => $p['compare_at'],
        'currency' => $p['currency'],
        'available' => $p['available'],
      ], $found['products'] ),
      'suggestions' => $found['suggestions'],
      'all_url' => add_query_arg( 'q', rawurlencode( $query ), self::pageUrl() ),
    ] );

    $response->header( 'Cache-Control', 'public, max-age=60' );

    return $response;
  }

  /**
   * The full results page — the Site Settings override if there is one.
   */
  public static function pageUrl(): string
  {
    $custom = function_exists( 'get_field' ) ? get_field( 'search_url', 'option' ) : '';

    return $custom ?: home_url( '/search/' );
  }

  private function init(): void
  {
    add_action( 'rest_api_init', [ $this, 'routes' ] );
  }
}
