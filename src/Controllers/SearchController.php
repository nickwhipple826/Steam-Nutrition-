<?php

namespace MMM\Controllers;

use MMM\Shopify\ShopifySearch;

/**
 * /search?q=… — full product results from Shopify.
 *
 * Paged with Shopify's cursor in ?after=, the same "load more" contract
 * the collection pages use, so there is no page-number arithmetic to
 * get wrong against a cursor API.
 */
class SearchController extends BaseController
{
  private const PER_PAGE = 24;

  public function render(): void
  {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $raw = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
    $after = isset( $_GET['after'] ) ? sanitize_text_field( wp_unslash( $_GET['after'] ) ) : null;
    // phpcs:enable

    $results = ShopifySearch::results( $raw, self::PER_PAGE, $after ?: null );

    status_header( 200 );

    $title = $results['query']
      ? sprintf( 'Search: %s | %s', $results['query'], get_bloginfo( 'name' ) )
      : 'Search | ' . get_bloginfo( 'name' );

    $this->renderView( 'pages/search.twig', [
      'search' => $results,
      'raw_query' => $raw,
      'too_short' => $raw !== '' && ! $results['query'],
      'min_length' => ShopifySearch::MIN_LENGTH,
      'next_url' => $results['has_next']
        ? add_query_arg( [ 'q' => rawurlencode( $results['query'] ), 'after' => rawurlencode( (string) $results['cursor'] ) ], home_url( '/search/' ) )
        : '',
      'is_paged' => (bool) $after,
      'page_title' => $title,
    ] );
  }
}
