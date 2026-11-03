<?php

namespace MMM\Shopify;

/**
 * Product search against the Storefront API.
 *
 * WordPress's own ?s= search can't see the catalogue — products live in
 * Shopify, not in wp_posts — so both the header's live results and the
 * /search page ask Shopify directly.
 *
 *   predictive()  as-you-type, for the header overlay (SearchApi.php)
 *   results()     the full /search page (SearchController.php)
 *
 * Both return products in the same shape ShopifyCollectionQuery gives
 * the collection grid, so product-card.twig renders them unchanged.
 */
class ShopifySearch
{
  /**
   * Short: search is mostly the same dozen queries, and a stale
   * "In stock" on a search result is less harmful than on a product
   * page, but not by much.
   */
  private const CACHE_TTL = 5 * MINUTE_IN_SECONDS;

  public const MIN_LENGTH = 2;
  public const MAX_LENGTH = 80;

  private const PRODUCT_FIELDS = <<<'GRAPHQL'
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
  GRAPHQL;

  /**
   * Trimmed, length-capped, and lowercased for the cache key's sake.
   * Empty when the query is too short to be worth sending.
   */
  public static function normalise( string $query ): string
  {
    $query = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $query ) ) );
    $query = mb_substr( $query, 0, self::MAX_LENGTH );

    return mb_strlen( $query ) >= self::MIN_LENGTH ? $query : '';
  }

  /**
   * As-you-type results: a handful of products plus Shopify's own
   * query suggestions ("hood" → "hoodie").
   *
   * @return array{products: array, suggestions: string[]}
   */
  public static function predictive( string $query, int $limit = 6 ): array
  {
    $empty = [ 'products' => [], 'suggestions' => [] ];
    $query = self::normalise( $query );

    if ( ! $query || ! ShopifyCollectionQuery::isConfigured() ) {
      return $empty;
    }

    $limit = max( 1, min( 10, $limit ) );
    $cacheKey = 'shopify_search_p_' . md5( mb_strtolower( $query ) . '|' . $limit );
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $fields = self::PRODUCT_FIELDS;
    $gql = <<<GRAPHQL
    query Predictive(\$query: String!, \$limit: Int!) {
      predictiveSearch(query: \$query, limit: \$limit, types: [PRODUCT, QUERY]) {
        products { {$fields} }
        queries { text }
      }
    }
    GRAPHQL;

    $response = ShopifyCollectionQuery::execute( $gql, [ 'query' => $query, 'limit' => $limit ] );
    $data = $response['data']['predictiveSearch'] ?? null;

    if ( ! $data ) {
      return $empty;
    }

    $result = [
      'products' => array_map(
        fn( array $node ) => ShopifyCollectionQuery::shapeProduct( [ 'node' => $node ] ),
        $data['products'] ?? []
      ),
      // Drop a suggestion that only repeats what was typed.
      'suggestions' => array_values( array_filter(
        array_map( fn( $q ) => (string) ( $q['text'] ?? '' ), $data['queries'] ?? [] ),
        fn( string $text ) => $text !== '' && mb_strtolower( $text ) !== mb_strtolower( $query )
      ) ),
    ];

    set_transient( $cacheKey, $result, self::CACHE_TTL );

    return $result;
  }

  /**
   * One page of full results for /search.
   *
   * @return array{configured: bool, query: string, products: array, total: int, has_next: bool, cursor: ?string}
   */
  public static function results( string $query, int $first = 24, ?string $after = null ): array
  {
    $query = self::normalise( $query );

    $empty = [
      'configured' => ShopifyCollectionQuery::isConfigured(),
      'query' => $query,
      'products' => [],
      'total' => 0,
      'has_next' => false,
      'cursor' => null,
    ];

    if ( ! $query || ! $empty['configured'] ) {
      return $empty;
    }

    $first = max( 1, min( 48, $first ) );
    $variables = [ 'query' => $query, 'first' => $first, 'after' => $after ];
    $cacheKey = 'shopify_search_r_' . md5( mb_strtolower( wp_json_encode( $variables ) ) );
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $fields = self::PRODUCT_FIELDS;
    $gql = <<<GRAPHQL
    query Search(\$query: String!, \$first: Int!, \$after: String) {
      search(query: \$query, first: \$first, after: \$after, types: [PRODUCT], sortKey: RELEVANCE) {
        totalCount
        pageInfo { hasNextPage endCursor }
        edges { node { ... on Product { {$fields} } } }
      }
    }
    GRAPHQL;

    $response = ShopifyCollectionQuery::execute( $gql, $variables );
    $search = $response['data']['search'] ?? null;

    if ( ! $search ) {
      return $empty;
    }

    // types: [PRODUCT] means every node is a product, but an empty node
    // would still break the shaper — skip anything without a handle.
    $edges = array_filter( $search['edges'] ?? [], fn( $edge ) => ! empty( $edge['node']['handle'] ) );

    $result = array_merge( $empty, [
      'products' => array_values( array_map( [ ShopifyCollectionQuery::class, 'shapeProduct' ], $edges ) ),
      'total' => (int) ( $search['totalCount'] ?? 0 ),
      'has_next' => (bool) ( $search['pageInfo']['hasNextPage'] ?? false ),
      'cursor' => $search['pageInfo']['endCursor'] ?? null,
    ] );

    set_transient( $cacheKey, $result, self::CACHE_TTL );

    return $result;
  }
}
