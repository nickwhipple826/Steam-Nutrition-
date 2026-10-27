<?php

namespace MMM\Shopify;

/**
 * The shopper's cart, held in Shopify.
 *
 * WordPress never stores what is in a cart — only the cart's ID, in a
 * cookie (CartSession). Every read and change is a Storefront API call,
 * so the cart, its prices and its checkout URL are always Shopify's own.
 *
 * Every public method returns the same shape (see shape()) or null, so
 * the REST API, the /cart page and the drawer all render one structure.
 */
class ShopifyCart
{
  public const MAX_QUANTITY = 20;

  private const CART_FIELDS = <<<'GRAPHQL'
    id
    checkoutUrl
    totalQuantity
    cost {
      subtotalAmount { amount currencyCode }
      totalAmount { amount currencyCode }
    }
    lines(first: 100) {
      nodes {
        id
        quantity
        cost { totalAmount { amount currencyCode } }
        merchandise {
          ... on ProductVariant {
            id
            title
            availableForSale
            price { amount currencyCode }
            image { url altText }
            product { title handle featuredImage { url altText } }
          }
        }
      }
    }
  GRAPHQL;

  /**
   * The cart in the shopper's cookie, or null if there is none or
   * Shopify no longer has it (carts expire after a period of no use).
   */
  public static function current(): ?array
  {
    $id = CartSession::cartId();

    if ( ! $id ) {
      return null;
    }

    $cart = self::get( $id );

    if ( ! $cart ) {
      CartSession::clear();
    }

    return $cart;
  }

  public static function get( string $cartId ): ?array
  {
    $query = 'query GetCart($id: ID!) { cart(id: $id) { ' . self::CART_FIELDS . ' } }';
    $response = self::call( $query, [ 'id' => $cartId ] );
    $node = $response['data']['cart'] ?? null;

    return $node ? self::shape( $node ) : null;
  }

  /**
   * Add a variant. Creates the cart first if there isn't one, and starts
   * a fresh cart if the stored one has expired.
   *
   * @return array{cart: ?array, error: string}
   */
  public static function add( string $variantId, int $quantity ): array
  {
    $variantId = self::variantGid( $variantId );

    if ( ! $variantId ) {
      return [ 'cart' => self::current(), 'error' => 'That product could not be found.' ];
    }

    $line = [ 'merchandiseId' => $variantId, 'quantity' => self::clamp( $quantity ) ];
    $cartId = CartSession::cartId();

    if ( $cartId ) {
      $result = self::mutate( 'cartLinesAdd', '$cartId: ID!, $lines: [CartLineInput!]!', 'cartId: $cartId, lines: $lines', [
        'cartId' => $cartId,
        'lines' => [ $line ],
      ] );

      // A null cart with no user error means the stored ID is dead —
      // fall through and start a new one rather than failing the add.
      if ( $result['cart'] || $result['error'] ) {
        return $result;
      }
    }

    return self::create( [ $line ] );
  }

  /**
   * Set a line's quantity. Zero removes it.
   *
   * @return array{cart: ?array, error: string}
   */
  public static function update( string $lineId, int $quantity ): array
  {
    if ( $quantity <= 0 ) {
      return self::remove( $lineId );
    }

    $cartId = CartSession::cartId();

    if ( ! $cartId ) {
      return [ 'cart' => null, 'error' => 'Your bag has expired.' ];
    }

    return self::mutate( 'cartLinesUpdate', '$cartId: ID!, $lines: [CartLineUpdateInput!]!', 'cartId: $cartId, lines: $lines', [
      'cartId' => $cartId,
      'lines' => [ [ 'id' => $lineId, 'quantity' => self::clamp( $quantity ) ] ],
    ] );
  }

  /**
   * @return array{cart: ?array, error: string}
   */
  public static function remove( string $lineId ): array
  {
    $cartId = CartSession::cartId();

    if ( ! $cartId ) {
      return [ 'cart' => null, 'error' => 'Your bag has expired.' ];
    }

    return self::mutate( 'cartLinesRemove', '$cartId: ID!, $lineIds: [ID!]!', 'cartId: $cartId, lineIds: $lineIds', [
      'cartId' => $cartId,
      'lineIds' => [ $lineId ],
    ] );
  }

  /**
   * Tie the current cart to a signed-in customer, so checkout opens
   * signed in with their saved details. Best effort: a failure here
   * leaves a perfectly usable anonymous cart.
   */
  public static function attachCustomer( string $customerAccessToken ): void
  {
    $cartId = CartSession::cartId();

    if ( ! $cartId || ! $customerAccessToken ) {
      return;
    }

    $result = self::mutate(
      'cartBuyerIdentityUpdate',
      '$cartId: ID!, $buyerIdentity: CartBuyerIdentityInput!',
      'cartId: $cartId, buyerIdentity: $buyerIdentity',
      [ 'cartId' => $cartId, 'buyerIdentity' => [ 'customerAccessToken' => $customerAccessToken ] ]
    );

    if ( $result['error'] ) {
      error_log( 'Shopify cartBuyerIdentityUpdate failed: ' . $result['error'] );
    }
  }

  // ---------------------------------------------------------------------

  /**
   * @return array{cart: ?array, error: string}
   */
  private static function create( array $lines ): array
  {
    $input = [ 'lines' => $lines ];

    $token = CustomerAccount::accessToken();
    if ( $token ) {
      $input['buyerIdentity'] = [ 'customerAccessToken' => $token ];
    }

    $result = self::mutate( 'cartCreate', '$input: CartInput!', 'input: $input', [ 'input' => $input ] );

    // The buyer identity is a nicety. If Shopify refuses it, make the
    // cart without it rather than losing the shopper's add.
    if ( ! $result['cart'] && isset( $input['buyerIdentity'] ) ) {
      unset( $input['buyerIdentity'] );
      $result = self::mutate( 'cartCreate', '$input: CartInput!', 'input: $input', [ 'input' => $input ] );
    }

    return $result;
  }

  /**
   * Run one cart mutation and normalise the answer.
   *
   * @return array{cart: ?array, error: string}
   */
  private static function mutate( string $name, string $args, string $call, array $variables ): array
  {
    $query = "mutation {$name}Op({$args}) {
      {$name}({$call}) {
        cart { " . self::CART_FIELDS . " }
        userErrors { field message }
      }
    }";

    $response = self::call( $query, $variables );
    $payload = $response['data'][ $name ] ?? null;
    $node = $payload['cart'] ?? null;

    if ( $node ) {
      CartSession::store( $node['id'], (int) ( $node['totalQuantity'] ?? 0 ) );
    }

    $error = $payload['userErrors'][0]['message'] ?? '';

    if ( ! $payload && ! empty( $response['errors'] ) ) {
      $error = 'Shopify did not respond. Please try again.';
    }

    return [ 'cart' => $node ? self::shape( $node ) : null, 'error' => $error ];
  }

  /**
   * Storefront call with the shopper's IP attached. Shopify uses it for
   * bot protection on cart and checkout when the request comes from a
   * server instead of a browser.
   */
  private static function call( string $query, array $variables ): array
  {
    $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

    return ShopifyCollectionQuery::execute(
      $query,
      $variables,
      $ip ? [ 'Shopify-Storefront-Buyer-IP' => $ip ] : []
    );
  }

  /**
   * One shape for the drawer, the /cart page and the header count.
   */
  public static function shape( array $node ): array
  {
    $lines = [];

    foreach ( $node['lines']['nodes'] ?? [] as $line ) {
      $variant = $line['merchandise'] ?? [];
      $product = $variant['product'] ?? [];
      $image = $variant['image'] ?? $product['featuredImage'] ?? null;

      $lines[] = [
        'id' => $line['id'],
        'quantity' => (int) $line['quantity'],
        'variant_id' => $variant['id'] ?? '',
        'title' => $product['title'] ?? '',
        // Shopify names a variant-less product's only variant "Default
        // Title". That is bookkeeping, not something to show a shopper.
        'variant_title' => ( $variant['title'] ?? '' ) === 'Default Title' ? '' : ( $variant['title'] ?? '' ),
        'handle' => $product['handle'] ?? '',
        'url' => ! empty( $product['handle'] ) ? home_url( '/products/' . $product['handle'] . '/' ) : '',
        'image' => $image ? [ 'url' => $image['url'], 'alt' => $image['altText'] ?? '' ] : null,
        'available' => (bool) ( $variant['availableForSale'] ?? true ),
        'price' => $variant['price']['amount'] ?? null,
        'line_total' => $line['cost']['totalAmount']['amount'] ?? null,
        'currency' => $line['cost']['totalAmount']['currencyCode'] ?? 'USD',
      ];
    }

    return [
      'id' => $node['id'],
      'checkout_url' => $node['checkoutUrl'] ?? '',
      'count' => (int) ( $node['totalQuantity'] ?? 0 ),
      'subtotal' => $node['cost']['subtotalAmount']['amount'] ?? null,
      'total' => $node['cost']['totalAmount']['amount'] ?? null,
      'currency' => $node['cost']['subtotalAmount']['currencyCode'] ?? 'USD',
      'lines' => $lines,
      'max_quantity' => self::MAX_QUANTITY,
    ];
  }

  /**
   * The empty-cart shape, so templates never branch on null.
   */
  public static function empty(): array
  {
    return [
      'id' => '',
      'checkout_url' => '',
      'count' => 0,
      'subtotal' => null,
      'total' => null,
      'currency' => 'USD',
      'lines' => [],
      'max_quantity' => self::MAX_QUANTITY,
    ];
  }

  private static function clamp( int $quantity ): int
  {
    return max( 1, min( self::MAX_QUANTITY, $quantity ) );
  }

  /**
   * Accept a full GID or the bare number that ?variant= uses.
   */
  private static function variantGid( string $id ): string
  {
    if ( preg_match( '#^gid://shopify/ProductVariant/\d+$#', $id ) ) {
      return $id;
    }

    return ctype_digit( $id ) ? 'gid://shopify/ProductVariant/' . $id : '';
  }
}
