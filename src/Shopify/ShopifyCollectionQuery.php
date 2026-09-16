<?php

namespace MMM\Shopify;

class ShopifyCollectionQuery
{
  private const CACHE_TTL = HOUR_IN_SECONDS;

  private const SORT_KEY_MAP = [
    'BEST_SELLING' => 'BEST_SELLING',
    'PRICE' => 'PRICE',
    'TITLE' => 'TITLE',
    'CREATED' => 'CREATED',
  ];

  /**
   * Fetch a Shopify collection's products with sort/filter args.
   * @param string $handle Shopify collection handle
   * @param array $args [ 'sort_key' => string, 'reverse' => bool, 'filters' => array, 'first' => int ]
   */
  public static function getCollection( string $handle, array $args = [] ): array
  {
    $sortKey = self::SORT_KEY_MAP[ $args['sort_key'] ?? 'BEST_SELLING' ] ?? 'BEST_SELLING';
    $reverse = $args['reverse'] ?? false;
    $filters = $args['filters'] ?? [];
    $first = $args['first'] ?? 24;

    $cacheKey = 'shopify_collection_' . md5( $handle . wp_json_encode( $args ) );
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $query = <<<'GRAPHQL'
    query GetCollection($handle: String!, $first: Int!, $sortKey: ProductCollectionSortKeys!, $reverse: Boolean!, $filters: [ProductFilter!]) {
      collectionByHandle(handle: $handle) {
        title
        handle
        products(first: $first, sortKey: $sortKey, reverse: $reverse, filters: $filters) {
          filters {
            id
            label
            type
            values { id label count input }
          }
          edges {
            node {
              id
              title
              handle
              availableForSale
              featuredImage { url altText }
              priceRange { minVariantPrice { amount currencyCode } }
            }
          }
        }
      }
    }
    GRAPHQL;

    $response = self::execute( $query, [
      'handle' => $handle,
      'first' => $first,
      'sortKey' => $sortKey,
      'reverse' => $reverse,
      'filters' => $filters,
    ] );

    if ( empty( $response['data']['collectionByHandle'] ) ) {
      return [ 'title' => '', 'products' => [], 'filters' => [] ];
    }

    $collection = $response['data']['collectionByHandle'];

    $result = [
      'title' => $collection['title'],
      'products' => array_map( function ( $edge ) {
        $node = $edge['node'];
        return [
          'id' => $node['id'],
          'title' => $node['title'],
          'handle' => $node['handle'],
          'available' => $node['availableForSale'],
          'image' => $node['featuredImage']['url'] ?? '',
          'alt' => $node['featuredImage']['altText'] ?? '',
          'price' => $node['priceRange']['minVariantPrice']['amount'] ?? null,
          'currency' => $node['priceRange']['minVariantPrice']['currencyCode'] ?? 'USD',
        ];
      }, $collection['products']['edges'] ),
      'filters' => $collection['products']['filters'] ?? [],
    ];

    set_transient( $cacheKey, $result, self::CACHE_TTL );

    return $result;
  }

  private static function execute( string $query, array $variables ): array
  {
    $domain = defined( 'SHOPIFY_STORE_DOMAIN' ) ? SHOPIFY_STORE_DOMAIN : '';
    $token = defined( 'SHOPIFY_STOREFRONT_TOKEN' ) ? SHOPIFY_STOREFRONT_TOKEN : '';

    $response = wp_remote_post( "https://{$domain}/api/2024-10/graphql.json", [
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Shopify-Storefront-Access-Token' => $token,
      ],
      'body' => wp_json_encode( [ 'query' => $query, 'variables' => $variables ] ),
      'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
      return [];
    }

    return json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];
  }

  public static function bustCache( string $handle ): void
  {
    global $wpdb;
    $like = $wpdb->esc_like( '_transient_shopify_collection_' ) . '%';
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore
  }
}