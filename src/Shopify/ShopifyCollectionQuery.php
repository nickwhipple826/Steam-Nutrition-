<?php

namespace MMM\Shopify;

/**
 * Storefront API reads for the collection pages.
 *
 * Nothing here needs WooCommerce or a local catalogue — a collection
 * page is one GraphQL call that returns the products, the facets that
 * apply to them, and a cursor for the next page.
 *
 * Define these in wp-config.php before any of it returns anything:
 *
 *   define( 'SHOPIFY_STORE_DOMAIN', 'your-store.myshopify.com' );
 *   define( 'SHOPIFY_STOREFRONT_TOKEN', '...' );
 */
class ShopifyCollectionQuery
{
  private const CACHE_TTL = HOUR_IN_SECONDS;
  private const API_VERSION = '2024-10';

  /**
   * Editor-facing sort labels mapped onto Shopify's sortKey/reverse
   * pair. Shared by the controller, the product rail and the page's
   * own <select>, so the three cannot drift apart.
   */
  public const SORT_MAP = [
    'BEST_SELLING' => [ 'label' => 'Best selling', 'sort_key' => 'BEST_SELLING', 'reverse' => false ],
    'CREATED' => [ 'label' => 'Newest', 'sort_key' => 'CREATED', 'reverse' => true ],
    'PRICE_LOW_HIGH' => [ 'label' => 'Price: low to high', 'sort_key' => 'PRICE', 'reverse' => false ],
    'PRICE_HIGH_LOW' => [ 'label' => 'Price: high to low', 'sort_key' => 'PRICE', 'reverse' => true ],
    'TITLE' => [ 'label' => 'Alphabetically, A–Z', 'sort_key' => 'TITLE', 'reverse' => false ],
    'TITLE_DESC' => [ 'label' => 'Alphabetically, Z–A', 'sort_key' => 'TITLE', 'reverse' => true ],
  ];

  /**
   * True once both constants are defined. The templates use this to
   * tell an editor "connect Shopify" apart from "this collection is
   * empty", which are very different problems.
   */
  public static function isConfigured(): bool
  {
    return defined( 'SHOPIFY_STORE_DOMAIN' )
      && defined( 'SHOPIFY_STOREFRONT_TOKEN' )
      && SHOPIFY_STORE_DOMAIN
      && SHOPIFY_STOREFRONT_TOKEN;
  }

  /**
   * One page of a collection, plus the facets that apply to it.
   *
   * @param string $handle Shopify collection handle
   * @param array $args [
   *   'sort'    => string key of SORT_MAP
   *   'filters' => array of Shopify ProductFilter inputs (already decoded)
   *   'first'   => int
   *   'after'   => string|null cursor
   * ]
   * @return array{
   *   configured: bool, found: bool, title: string, description: string,
   *   products: array, facets: array, has_next: bool, cursor: ?string, total: ?int
   * }
   */
  public static function getCollection( string $handle, array $args = [] ): array
  {
    $empty = [
      'configured' => self::isConfigured(),
      'found' => false,
      'title' => '',
      'description' => '',
      'products' => [],
      'facets' => [],
      'has_next' => false,
      'cursor' => null,
      'total' => null,
    ];

    if ( ! $handle || ! self::isConfigured() ) {
      return $empty;
    }

    $sort = self::SORT_MAP[ $args['sort'] ?? 'BEST_SELLING' ] ?? self::SORT_MAP['BEST_SELLING'];
    $filters = array_values( $args['filters'] ?? [] );
    $first = max( 1, min( 48, (int) ( $args['first'] ?? 24 ) ) );
    $after = $args['after'] ?? null;

    $variables = [
      'handle' => $handle,
      'first' => $first,
      'after' => $after,
      'sortKey' => $sort['sort_key'],
      'reverse' => $sort['reverse'],
      'filters' => $filters,
    ];

    $cacheKey = 'shopify_collection_' . md5( wp_json_encode( $variables ) );
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $query = <<<'GRAPHQL'
    query GetCollection(
      $handle: String!
      $first: Int!
      $after: String
      $sortKey: ProductCollectionSortKeys!
      $reverse: Boolean!
      $filters: [ProductFilter!]
    ) {
      collectionByHandle(handle: $handle) {
        title
        description
        products(first: $first, after: $after, sortKey: $sortKey, reverse: $reverse, filters: $filters) {
          filters {
            id
            label
            type
            values { id label count input }
          }
          pageInfo { hasNextPage endCursor }
          edges {
            node {
              id
              title
              handle
              availableForSale
              productType
              tags
              featuredImage { url altText width height }
              priceRange { minVariantPrice { amount currencyCode } }
              compareAtPriceRange { minVariantPrice { amount currencyCode } }
              options { name values }
            }
          }
        }
      }
    }
    GRAPHQL;

    $response = self::execute( $query, $variables );
    $collection = $response['data']['collectionByHandle'] ?? null;

    if ( ! $collection ) {
      // Distinguish "no such handle" from "not configured" — the first
      // is an editor typo, the second is a deploy step.
      return array_merge( $empty, [ 'configured' => true ] );
    }

    $products = $collection['products'];

    $result = [
      'configured' => true,
      'found' => true,
      'title' => $collection['title'] ?? '',
      'description' => $collection['description'] ?? '',
      'products' => array_map( [ self::class, 'shapeProduct' ], $products['edges'] ),
      'facets' => self::shapeFacets( $products['filters'] ?? [] ),
      'has_next' => (bool) ( $products['pageInfo']['hasNextPage'] ?? false ),
      'cursor' => $products['pageInfo']['endCursor'] ?? null,
      // Shopify does not return a total alongside a filtered page, so
      // the templates say "showing N" rather than "N of M". Claiming a
      // total we do not have would be worse than not showing one.
      'total' => null,
    ];

    set_transient( $cacheKey, $result, self::CACHE_TTL );

    return $result;
  }

  /**
   * Flatten one product edge into the shape every template expects.
   */
  private static function shapeProduct( array $edge ): array
  {
    $node = $edge['node'];

    $price = $node['priceRange']['minVariantPrice']['amount'] ?? null;
    $compare = $node['compareAtPriceRange']['minVariantPrice']['amount'] ?? null;

    // Shopify returns compareAtPrice even when it equals the selling
    // price, which would render a struck-through number identical to
    // the one beside it. Only keep a genuine markdown.
    $onSale = $compare && (float) $compare > (float) $price;

    // The first option with more than one value is the one worth
    // showing on a card — Flavor for a tub, Color for a tee. Shopify
    // has no "primary option", so this is the closest honest guess.
    $swatches = [];
    foreach ( $node['options'] ?? [] as $option ) {
      if ( count( $option['values'] ?? [] ) > 1 ) {
        $swatches = [ 'name' => $option['name'], 'values' => $option['values'] ];
        break;
      }
    }

    return [
      'id' => $node['id'],
      'title' => $node['title'],
      'handle' => $node['handle'],
      'available' => (bool) $node['availableForSale'],
      'type' => $node['productType'] ?? '',
      'tags' => $node['tags'] ?? [],
      'image' => $node['featuredImage']['url'] ?? '',
      'alt' => $node['featuredImage']['altText'] ?? '',
      'width' => $node['featuredImage']['width'] ?? null,
      'height' => $node['featuredImage']['height'] ?? null,
      'price' => $price,
      'compare_at' => $onSale ? $compare : null,
      'on_sale' => $onSale,
      'currency' => $node['priceRange']['minVariantPrice']['currencyCode'] ?? 'USD',
      'swatches' => $swatches,
    ];
  }

  /**
   * Shopify describes each facet value with an `input` — a JSON string
   * that is passed straight back as a ProductFilter to apply it. Keep
   * it opaque: round-tripping Shopify's own input is what makes one
   * filter panel work for supplements, apparel and merch alike, and
   * keeps working when the client adds an option we never coded for.
   */
  private static function shapeFacets( array $filters ): array
  {
    $facets = [];

    foreach ( $filters as $filter ) {
      $values = [];

      foreach ( $filter['values'] ?? [] as $value ) {
        if ( (int) ( $value['count'] ?? 0 ) === 0 ) {
          continue; // a facet nobody can reach is noise
        }

        $values[] = [
          'id' => $value['id'],
          'label' => $value['label'],
          'count' => (int) $value['count'],
          'input' => $value['input'],
        ];
      }

      if ( ! $values ) {
        continue;
      }

      $facets[] = [
        'id' => $filter['id'],
        'label' => $filter['label'],
        'type' => $filter['type'],
        'values' => $values,
      ];
    }

    return $facets;
  }

  /**
   * Decode the filter inputs submitted in the query string back into
   * Shopify ProductFilter arrays.
   *
   * Only well-formed JSON objects survive. The value arrives from the
   * URL, so it is attacker-controlled; anything that is not a JSON
   * object is dropped rather than forwarded to Shopify.
   *
   * @param array $raw e.g. $_GET['f']
   */
  public static function decodeFilters( array $raw ): array
  {
    $filters = [];

    foreach ( $raw as $json ) {
      if ( ! is_string( $json ) ) {
        continue;
      }

      $decoded = json_decode( wp_unslash( $json ), true );

      // A ProductFilter is a JSON *object*; a list or a scalar is junk.
      // array_is_list() would read better but is PHP 8.1+, and the
      // theme header still declares 8.0.
      $isObject = is_array( $decoded )
        && $decoded !== []
        && array_keys( $decoded ) !== range( 0, count( $decoded ) - 1 );

      if ( $isObject ) {
        $filters[] = $decoded;
      }
    }

    return $filters;
  }

  /**
   * Every collection on the store, for the ACF dropdown.
   * @return array<int, array{handle: string, title: string}>
   */
  public static function listCollections( int $first = 100 ): array
  {
    if ( ! self::isConfigured() ) {
      return [];
    }

    $cacheKey = 'shopify_collection_index_' . $first;
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $query = <<<'GRAPHQL'
    query ListCollections($first: Int!) {
      collections(first: $first, sortKey: TITLE) {
        edges { node { handle title } }
      }
    }
    GRAPHQL;

    $response = self::execute( $query, [ 'first' => $first ] );
    $edges = $response['data']['collections']['edges'] ?? [];

    $result = array_map(
      fn( $edge ) => [ 'handle' => $edge['node']['handle'], 'title' => $edge['node']['title'] ],
      $edges
    );

    // Never cache an empty index: one missing token or a brief outage
    // would otherwise pin a blank dropdown in place for an hour.
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

  /**
   * Convenience wrapper for the Product Rail flexible-content layout.
   */
  public static function getCollectionSorted( string $handle, string $sort = 'BEST_SELLING', int $first = 8 ): array
  {
    return self::getCollection( $handle, [ 'sort' => $sort, 'first' => $first ] );
  }

  private static function execute( string $query, array $variables ): array
  {
    if ( ! self::isConfigured() ) {
      return [];
    }

    $url = sprintf( 'https://%s/api/%s/graphql.json', SHOPIFY_STORE_DOMAIN, self::API_VERSION );

    $response = wp_remote_post( $url, [
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Shopify-Storefront-Access-Token' => SHOPIFY_STOREFRONT_TOKEN,
      ],
      'body' => wp_json_encode( [ 'query' => $query, 'variables' => $variables ] ),
      'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
      error_log( 'Shopify request failed: ' . $response->get_error_message() );
      return [];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];

    // GraphQL answers 200 with an errors array, so a non-error HTTP
    // status is not on its own a success.
    if ( ! empty( $body['errors'] ) ) {
      error_log( 'Shopify GraphQL errors: ' . wp_json_encode( $body['errors'] ) );
    }

    return $body;
  }

  /**
   * Clear every cached collection response and the collection index.
   */
  public static function bustCache(): void
  {
    global $wpdb;

    foreach ( [ '_transient_shopify_collection_', '_transient_timeout_shopify_collection_' ] as $prefix ) {
      $like = $wpdb->esc_like( $prefix ) . '%';
      $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore
    }
  }
}