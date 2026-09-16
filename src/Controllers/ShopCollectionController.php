<?php

namespace MMM\Controllers;

use MMM\Shopify\ShopifyCollectionQuery;
use Timber\Timber;

class ShopCollectionController extends BaseController
{
  private const SORT_MAP = [
    'BEST_SELLING' => [ 'sort_key' => 'BEST_SELLING', 'reverse' => false ],
    'PRICE_LOW_HIGH' => [ 'sort_key' => 'PRICE', 'reverse' => false ],
    'PRICE_HIGH_LOW' => [ 'sort_key' => 'PRICE', 'reverse' => true ],
    'TITLE' => [ 'sort_key' => 'TITLE', 'reverse' => false ],
    'CREATED' => [ 'sort_key' => 'CREATED', 'reverse' => true ],
  ];

  public function render(): void
  {
    $post = Timber::get_post( get_the_ID() );

    $handle = get_field( 'shopify_collection_handle' );
    $filterGroup = get_field( 'shop_filter_group' ) ?: 'supplements';
    $defaultSort = get_field( 'shop_default_sort' ) ?: 'BEST_SELLING';

    $requestedSort = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : $defaultSort;
    $sortArgs = self::SORT_MAP[ $requestedSort ] ?? self::SORT_MAP['BEST_SELLING'];

    $collection = $handle
      ? ShopifyCollectionQuery::getCollection( $handle, $sortArgs )
      : [ 'title' => '', 'products' => [], 'filters' => [] ];

    $this->renderView(
      'pages/shop-collection.twig',
      [
        'post' => $post,
        'collection' => $collection,
        'filter_group' => $filterGroup,
        'current_sort' => $requestedSort,
      ]
    );
  }
}