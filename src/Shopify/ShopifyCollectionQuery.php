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
   * Maps the editor-facing sort choices onto Shopify's sortKey/reverse pair.
   * Shared by ShopCollectionController and ProductRailLayout so the two
   * cannot drift apart.
   */
  public const SORT_MAP = [
    'BEST_SELLING' => [ 'sort_key' => 'BEST_SELLING', 'reverse' => false ],
    'PRICE_LOW_HIGH' => [ 'sort_key' => 'PRICE', 'reverse' => false ],
    'PRICE_HIGH_LOW' => [ 'sort_key' => 'PRICE', 'reverse' => true ],
    'TITLE' => [ 'sort_key' => 'TITLE', 'reverse' => false ],
    'CREATED' => [ 'sort_key' => 'CREATED', 'reverse' => true ],
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
              productType
              tags
              featuredImage { url altText }
              priceRange { minVariantPrice { amount currencyCode } }
              compareAtPriceRange { minVariantPrice { amount currencyCode } }
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

        $price = $node['priceRange']['minVariantPrice']['amount'] ?? null;
        $compare = $node['compareAtPriceRange']['minVariantPrice']['amount'] ?? null;

        return [
          'id' => $node['id'],
          'title' => $node['title'],
          'handle' => $node['handle'],
          'available' => $node['availableForSale'],
          'type' => $node['productType'] ?? '',
          'tags' => $node['tags'] ?? [],
          'image' => $node['featuredImage']['url'] ?? '',
          'alt' => $node['featuredImage']['altText'] ?? '',
          'price' => $price,
          // Shopify returns compareAtPrice even when it equals the
          // selling price, which would render a struck-through number
          // identical to the one beside it. Only keep a genuine markdown.
          'compare_at' => ( $compare && (float) $compare > (float) $price ) ? $compare : null,
          'currency' => $node['priceRange']['minVariantPrice']['currencyCode'] ?? 'USD',
        ];
      }, $collection['products']['edges'] ),
      'filters' => $collection['products']['filters'] ?? [],
    ];

    set_transient( $cacheKey, $result, self::CACHE_TTL );

    return $result;
  }

  /**
   * Fetch a collection using the editor-facing sort labels rather than
   * Shopify's sortKey/reverse pair. This is what the Twig function and
   * the product rail call.
   */
  public static function getCollectionSorted( string $handle, string $sort = 'BEST_SELLING', int $first = 8 ): array
  {
    $args = self::SORT_MAP[ $sort ] ?? self::SORT_MAP['BEST_SELLING'];
    $args['first'] = $first;

    return self::getCollection( $handle, $args );
  }

  /**
   * List every collection on the store, for populating ACF dropdowns.
   * @return array<int, array{handle: string, title: string}>
   */
  public static function listCollections( int $first = 100 ): array
  {
    $cacheKey = 'shopify_collection_index_' . $first;
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $query = <<<'GRAPHQL'
    query ListCollections($first: Int!) {
      collections(first: $first, sortKey: TITLE) {
        edges {
          node { handle title }
        }
      }
    }
    GRAPHQL;

    $response = self::execute( $query, [ 'first' => $first ] );
    $edges = $response['data']['collections']['edges'] ?? [];

    $result = array_map(
      fn( $edge ) => [ 'handle' => $edge['node']['handle'], 'title' => $edge['node']['title'] ],
      $edges
    );

    // Only cache a non-empty index. Caching an empty array would pin a
    // blank dropdown in place for an hour every time the token is
    // missing or the store is briefly unreachable.
    if ( ! empty( $result ) ) {
      set_transient( $cacheKey, $result, self::CACHE_TTL );
    }

    return $result;
  }

  /**
   * handle => title, shaped for an ACF select's `choices`.
   */
  public static function collectionChoices(): array
  {
    $choices = [];

    foreach ( self::listCollections() as $collection ) {
      $choices[ $collection['handle'] ] = $collection['title'];
    }

    return $choices;
  }

  private static function execute( string $query, array $variables ): array
  {
    $domain = defined( 'SHOPIFY_STORE_DOMAIN' ) ? SHOPIFY_STORE_DOMAIN : '';
    $token = defined( 'SHOPIFY_STOREFRONT_TOKEN' ) ? SHOPIFY_STOREFRONT_TOKEN : '';

    if ( ! $domain || ! $token ) {
      return [];
    }

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

  /**
   * Clears every cached collection response and the collection index.
   */
  public static function bustCache( string $handle = '' ): void
  {
    global $wpdb;

    foreach ( [ '_transient_shopify_collection_', '_transient_timeout_shopify_collection_' ] as $prefix ) {
      $like = $wpdb->esc_like( $prefix ) . '%';
      $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore
    }
  }
}
