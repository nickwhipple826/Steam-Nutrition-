<?php

namespace MMM\Shopify;

/**
 * Storefront API reads and the checkout hand-off for a single product.
 *
 * Shares the HTTP layer and the wp-config constants with
 * ShopifyCollectionQuery, so there is still one place that talks to
 * Shopify and one place that knows the API version.
 */
class ShopifyProductQuery
{
  /**
   * Shorter than the collection cache. Price and stock are what a
   * product page is for, and a five-minute-old "In stock" is the most
   * stale it should ever be.
   */
  private const CACHE_TTL = 5 * MINUTE_IN_SECONDS;

  public const MAX_QUANTITY = 20;

  /**
   * One product by handle, shaped for pages/product.twig.
   * Null when Shopify is not configured or has no such product.
   */
  public static function getProduct( string $handle ): ?array
  {
    $handle = sanitize_title( $handle );

    if ( ! $handle || ! ShopifyCollectionQuery::isConfigured() ) {
      return null;
    }

    $cacheKey = 'shopify_product_' . md5( $handle );
    $cached = get_transient( $cacheKey );

    if ( false !== $cached ) {
      return $cached;
    }

    $query = <<<'GRAPHQL'
    query GetProduct($handle: String!) {
      product(handle: $handle) {
        id
        title
        handle
        description
        descriptionHtml
        productType
        vendor
        availableForSale
        seo { title description }
        featuredImage { url altText width height }
        images(first: 12) {
          edges { node { url altText width height } }
        }
        options { name values }
        collections(first: 10) {
          edges { node { handle title } }
        }
        variants(first: 100) {
          edges {
            node {
              id
              title
              availableForSale
              price { amount currencyCode }
              compareAtPrice { amount currencyCode }
              selectedOptions { name value }
              image { url altText width height }
            }
          }
        }
      }
    }
    GRAPHQL;

    $response = ShopifyCollectionQuery::execute( $query, [ 'handle' => $handle ] );
    $node = $response['data']['product'] ?? null;

    // Not cached when missing, so a product published a minute ago
    // shows up on the next request instead of after the TTL.
    if ( ! $node ) {
      return null;
    }

    $product = self::shapeProduct( $node );

    set_transient( $cacheKey, $product, self::CACHE_TTL );

    return $product;
  }

  private static function shapeProduct( array $node ): array
  {
    $variants = array_map( [ self::class, 'shapeVariant' ], $node['variants']['edges'] ?? [] );

    $images = array_map(
      fn( $edge ) => self::shapeImage( $edge['node'] ),
      $node['images']['edges'] ?? []
    );

    if ( ! $images && ! empty( $node['featuredImage'] ) ) {
      $images[] = self::shapeImage( $node['featuredImage'] );
    }

    // A product with no real options still has one: Title → Default
    // Title. That is Shopify bookkeeping, not a choice for the shopper.
    $hasOptions = count( $variants ) > 1;

    return [
      'id' => $node['id'],
      'title' => $node['title'],
      'handle' => $node['handle'],
      'description' => $node['description'] ?? '',
      // Merchant-authored, but still HTML from another system. Run it
      // through the same filter WordPress uses for post content.
      'description_html' => wp_kses_post( $node['descriptionHtml'] ?? '' ),
      'type' => $node['productType'] ?? '',
      'vendor' => $node['vendor'] ?? '',
      'available' => (bool) ( $node['availableForSale'] ?? false ),
      'seo_title' => $node['seo']['title'] ?? '',
      'seo_description' => $node['seo']['description'] ?? '',
      'images' => $images,
      'has_options' => $hasOptions,
      'options' => $hasOptions
        ? array_map( fn( $o ) => [ 'name' => $o['name'], 'values' => $o['values'] ?? [] ], $node['options'] ?? [] )
        : [],
      'collections' => array_map(
        fn( $edge ) => [ 'handle' => $edge['node']['handle'], 'title' => $edge['node']['title'] ],
        $node['collections']['edges'] ?? []
      ),
      'variants' => $variants,
    ];
  }

  private static function shapeVariant( array $edge ): array
  {
    $node = $edge['node'];

    $price = $node['price']['amount'] ?? null;
    $compare = $node['compareAtPrice']['amount'] ?? null;
    $onSale = $compare && (float) $compare > (float) $price;

    $options = [];
    foreach ( $node['selectedOptions'] ?? [] as $selected ) {
      $options[ $selected['name'] ] = $selected['value'];
    }

    return [
      'id' => $node['id'],
      'title' => $node['title'],
      'available' => (bool) ( $node['availableForSale'] ?? false ),
      'price' => $price,
      'compare_at' => $onSale ? $compare : null,
      'currency' => $node['price']['currencyCode'] ?? 'USD',
      'options' => $options,
      'image' => ! empty( $node['image'] ) ? self::shapeImage( $node['image'] ) : null,
    ];
  }

  private static function shapeImage( array $image ): array
  {
    return [
      'url' => $image['url'] ?? '',
      'alt' => $image['altText'] ?? '',
      'width' => $image['width'] ?? null,
      'height' => $image['height'] ?? null,
    ];
  }

  /**
   * The variant to show first: the one named in ?variant=, else the
   * first that can be bought, else the first.
   *
   * @param string $requested Numeric id from the URL, or a full GID.
   */
  public static function initialVariant( array $product, string $requested = '' ): ?array
  {
    $variants = $product['variants'];

    if ( ! $variants ) {
      return null;
    }

    if ( $requested !== '' ) {
      foreach ( $variants as $variant ) {
        if ( $variant['id'] === $requested || self::numericId( $variant['id'] ) === $requested ) {
          return $variant;
        }
      }
    }

    foreach ( $variants as $variant ) {
      if ( $variant['available'] ) {
        return $variant;
      }
    }

    return $variants[0];
  }

  /**
   * Which variant a submitted form means.
   *
   * The option radios win over the hidden variant id: with JavaScript
   * off the radios change but the hidden field does not, and the radios
   * are what the shopper actually picked.
   *
   * @param array $options name => value, from $_POST['options']
   * @param string $variantId GID from $_POST['variant']
   */
  public static function resolveVariant( array $product, array $options, string $variantId ): ?array
  {
    if ( $product['has_options'] && $options ) {
      foreach ( $product['variants'] as $variant ) {
        $match = true;

        foreach ( $variant['options'] as $name => $value ) {
          if ( ( $options[ $name ] ?? null ) !== $value ) {
            $match = false;
            break;
          }
        }

        if ( $match ) {
          return $variant;
        }
      }

      return null; // a combination Shopify does not sell
    }

    // Only ever a variant of THIS product — the id came from a form.
    foreach ( $product['variants'] as $variant ) {
      if ( $variant['id'] === $variantId ) {
        return $variant;
      }
    }

    return count( $product['variants'] ) === 1 ? $product['variants'][0] : null;
  }

  /**
   * Make a Shopify cart holding one line and return its checkout URL.
   *
   * @return array{url: string, error: string}
   */
  public static function createCheckout( string $variantId, int $quantity ): array
  {
    $quantity = max( 1, min( self::MAX_QUANTITY, $quantity ) );

    $mutation = <<<'GRAPHQL'
    mutation CartCreate($input: CartInput!) {
      cartCreate(input: $input) {
        cart { checkoutUrl }
        userErrors { field message }
      }
    }
    GRAPHQL;

    $response = ShopifyCollectionQuery::execute( $mutation, [
      'input' => [
        'lines' => [
          [ 'merchandiseId' => $variantId, 'quantity' => $quantity ],
        ],
      ],
    ] );

    $result = $response['data']['cartCreate'] ?? null;
    $url = $result['cart']['checkoutUrl'] ?? '';

    if ( $url ) {
      return [ 'url' => $url, 'error' => '' ];
    }

    $message = $result['userErrors'][0]['message']
      ?? $response['errors'][0]['message']
      ?? 'Shopify did not respond.';

    error_log( 'Shopify cartCreate failed: ' . $message );

    return [ 'url' => '', 'error' => $message ];
  }

  /**
   * gid://shopify/ProductVariant/4711 → 4711
   */
  public static function numericId( string $gid ): string
  {
    $pos = strrpos( $gid, '/' );

    return $pos === false ? $gid : substr( $gid, $pos + 1 );
  }
}
