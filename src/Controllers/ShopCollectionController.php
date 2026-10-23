<?php

namespace MMM\Controllers;

use MMM\Shopify\ShopifyCollectionQuery;
use Timber\Timber;

/**
 * One collection page: Supplements, Apparel, Merch.
 *
 * Filters and sort live in the query string rather than in a POST, so
 * a filtered view can be linked, bookmarked, reloaded and crawled, and
 * the back button behaves. ui/shop-filters.ts enhances that with a
 * fetch-and-swap; without JavaScript the same URLs still render the
 * same pages server-side.
 *
 *   ?sort=PRICE_LOW_HIGH&f[]={"available":true}&f[]={"variantOption":…}
 */
class ShopCollectionController extends BaseController
{
  private const PER_PAGE = 24;

  public function render(): void
  {
    $post = Timber::get_post( get_the_ID() );

    $handle = (string) ( get_field( 'shopify_collection_handle' ) ?: '' );
    $defaultSort = (string) ( get_field( 'shop_default_sort' ) ?: 'BEST_SELLING' );

    $sort = $this->requestedSort( $defaultSort );
    $rawFilters = isset( $_GET['f'] ) && is_array( $_GET['f'] ) ? wp_unslash( $_GET['f'] ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $after = isset( $_GET['after'] ) ? sanitize_text_field( wp_unslash( $_GET['after'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    $collection = ShopifyCollectionQuery::getCollection( $handle, [
      'sort' => $sort,
      'filters' => ShopifyCollectionQuery::decodeFilters( $rawFilters ),
      'first' => self::PER_PAGE,
      'after' => $after,
    ] );

    // The raw JSON strings, so the panel can mark the checkboxes that
    // are already on without re-deriving them from Shopify's facets.
    $active = array_values( array_filter( $rawFilters, 'is_string' ) );

    $this->renderView( 'pages/shop-collection.twig', [
      'post' => $post,
      'collection' => $collection,
      'handle' => $handle,
      'current_sort' => $sort,
      'sort_options' => ShopifyCollectionQuery::SORT_MAP,
      'active_filters' => $active,
      'has_filters' => $active !== [],
      // Everything except `after`, so "load more" can append to the
      // current view and "clear" can drop back to an unfiltered one.
      'base_query' => $this->baseQuery( $sort, $active ),
    ] );
  }

  /**
   * The sort asked for, if it is one we actually offer. An unknown key
   * from the query string falls back to the page's default rather than
   * reaching the API.
   */
  private function requestedSort( string $default ): string
  {
    $requested = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( isset( ShopifyCollectionQuery::SORT_MAP[ $requested ] ) ) {
      return $requested;
    }

    return isset( ShopifyCollectionQuery::SORT_MAP[ $default ] ) ? $default : 'BEST_SELLING';
  }

  /**
   * Rebuild the query string for links off this page.
   * @param string[] $filters
   */
  private function baseQuery( string $sort, array $filters ): string
  {
    $parts = [ 'sort=' . rawurlencode( $sort ) ];

    foreach ( $filters as $filter ) {
      $parts[] = 'f%5B%5D=' . rawurlencode( $filter );
    }

    return implode( '&', $parts );
  }
}