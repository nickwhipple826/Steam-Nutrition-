<?php

namespace MMM\Shopify;

/**
 * Customer Account API reads for the /account screens, shaped for the
 * templates. Never cached — this is one person's data, and it changes
 * the moment they place an order.
 */
class AccountQuery
{
  public const ORDERS_PER_PAGE = 20;

  private const ORDER_SUMMARY = <<<'GRAPHQL'
    id
    name
    number
    processedAt
    cancelledAt
    financialStatus
    fulfillmentStatus
    totalPrice { amount currencyCode }
    lineItems(first: 4) {
      nodes { name quantity image { url altText } }
    }
  GRAPHQL;

  private const ADDRESS = <<<'GRAPHQL'
    id
    formatted(withName: true)
    firstName
    lastName
  GRAPHQL;

  /**
   * Everything the dashboard shows, in one request.
   */
  public static function dashboard(): ?array
  {
    $query = 'query Dashboard {
      customer {
        firstName
        lastName
        displayName
        creationDate
        emailAddress { emailAddress }
        phoneNumber { phoneNumber }
        defaultAddress { ' . self::ADDRESS . ' }
        addresses(first: 10) { nodes { ' . self::ADDRESS . ' } }
        orders(first: 5, sortKey: PROCESSED_AT, reverse: true) {
          nodes { ' . self::ORDER_SUMMARY . ' }
        }
      }
    }';

    $customer = CustomerAccount::query( $query )['data']['customer'] ?? null;

    if ( ! $customer ) {
      return null;
    }

    $defaultId = $customer['defaultAddress']['id'] ?? '';

    return [
      'first_name' => $customer['firstName'] ?? '',
      'name' => $customer['displayName'] ?: trim( ( $customer['firstName'] ?? '' ) . ' ' . ( $customer['lastName'] ?? '' ) ),
      'email' => $customer['emailAddress']['emailAddress'] ?? '',
      'phone' => $customer['phoneNumber']['phoneNumber'] ?? '',
      'since' => $customer['creationDate'] ?? '',
      'default_address' => $customer['defaultAddress'] ? self::shapeAddress( $customer['defaultAddress'], $defaultId ) : null,
      'addresses' => array_map(
        fn( $a ) => self::shapeAddress( $a, $defaultId ),
        $customer['addresses']['nodes'] ?? []
      ),
      'orders' => array_map( [ self::class, 'shapeSummary' ], $customer['orders']['nodes'] ?? [] ),
    ];
  }

  /**
   * One page of order history, newest first.
   *
   * @return array{orders: array, has_next: bool, cursor: ?string}|null
   */
  public static function orders( ?string $after = null ): ?array
  {
    $query = 'query Orders($first: Int!, $after: String) {
      customer {
        orders(first: $first, after: $after, sortKey: PROCESSED_AT, reverse: true) {
          nodes { ' . self::ORDER_SUMMARY . ' }
          pageInfo { hasNextPage endCursor }
        }
      }
    }';

    $orders = CustomerAccount::query( $query, [
      'first' => self::ORDERS_PER_PAGE,
      'after' => $after ?: null,
    ] )['data']['customer']['orders'] ?? null;

    if ( ! is_array( $orders ) ) {
      return null;
    }

    return [
      'orders' => array_map( [ self::class, 'shapeSummary' ], $orders['nodes'] ?? [] ),
      'has_next' => (bool) ( $orders['pageInfo']['hasNextPage'] ?? false ),
      'cursor' => $orders['pageInfo']['endCursor'] ?? null,
    ];
  }

  /**
   * One order in full. Null if it does not exist or is not theirs —
   * the API only ever returns the signed-in customer's own orders.
   */
  public static function order( string $number ): ?array
  {
    if ( ! ctype_digit( $number ) ) {
      return null;
    }

    $query = 'query Order($id: ID!) {
      order(id: $id) {
        ' . self::ORDER_SUMMARY . '
        statusPageUrl
        subtotal { amount currencyCode }
        totalShipping { amount currencyCode }
        totalTax { amount currencyCode }
        shippingAddress { formatted(withName: true) }
        fullLines: lineItems(first: 100) {
          nodes {
            name
            variantTitle
            quantity
            image { url altText }
            price { amount currencyCode }
            totalPrice { amount currencyCode }
          }
        }
      }
    }';

    $order = CustomerAccount::query( $query, [ 'id' => 'gid://shopify/Order/' . $number ] )['data']['order'] ?? null;

    if ( ! $order ) {
      return null;
    }

    $summary = self::shapeSummary( $order );

    return array_merge( $summary, [
      'status_url' => $order['statusPageUrl'] ?? '',
      'subtotal' => $order['subtotal']['amount'] ?? null,
      'shipping' => $order['totalShipping']['amount'] ?? null,
      'tax' => $order['totalTax']['amount'] ?? null,
      'shipping_address' => $order['shippingAddress']['formatted'] ?? [],
      'lines' => array_map( fn( $line ) => [
        'name' => $line['name'] ?? '',
        'variant_title' => ( $line['variantTitle'] ?? '' ) === 'Default Title' ? '' : ( $line['variantTitle'] ?? '' ),
        'quantity' => (int) ( $line['quantity'] ?? 0 ),
        'image' => ! empty( $line['image'] ) ? [ 'url' => $line['image']['url'], 'alt' => $line['image']['altText'] ?? '' ] : null,
        'price' => $line['price']['amount'] ?? null,
        'total' => $line['totalPrice']['amount'] ?? null,
      ], $order['fullLines']['nodes'] ?? [] ),
    ] );
  }

  // ---------------------------------------------------------------------

  private static function shapeSummary( array $order ): array
  {
    return [
      'id' => $order['id'],
      'number' => (string) ( $order['number'] ?? '' ),
      'name' => $order['name'] ?? '',
      'date' => $order['processedAt'] ?? '',
      'url' => home_url( '/account/orders/' . ( $order['number'] ?? '' ) . '/' ),
      'status' => self::status( $order ),
      'total' => $order['totalPrice']['amount'] ?? null,
      'currency' => $order['totalPrice']['currencyCode'] ?? 'USD',
      'items' => array_map( fn( $line ) => [
        'name' => $line['name'] ?? '',
        'quantity' => (int) ( $line['quantity'] ?? 0 ),
        'image' => ! empty( $line['image'] ) ? [ 'url' => $line['image']['url'], 'alt' => $line['image']['altText'] ?? '' ] : null,
      ], $order['lineItems']['nodes'] ?? [] ),
    ];
  }

  /**
   * One plain status for the shopper, from Shopify's two enums.
   *
   * @return array{label: string, tone: string}  tone: done|active|alert
   */
  private static function status( array $order ): array
  {
    if ( ! empty( $order['cancelledAt'] ) ) {
      return [ 'label' => 'Cancelled', 'tone' => 'alert' ];
    }

    $financial = $order['financialStatus'] ?? '';

    if ( in_array( $financial, [ 'REFUNDED', 'VOIDED' ], true ) ) {
      return [ 'label' => ucfirst( strtolower( $financial ) ), 'tone' => 'alert' ];
    }

    return match ( $order['fulfillmentStatus'] ?? '' ) {
      'FULFILLED' => [ 'label' => 'Shipped', 'tone' => 'done' ],
      'PARTIALLY_FULFILLED' => [ 'label' => 'Partly shipped', 'tone' => 'active' ],
      'IN_PROGRESS', 'SCHEDULED', 'OPEN' => [ 'label' => 'Being packed', 'tone' => 'active' ],
      'ON_HOLD' => [ 'label' => 'On hold', 'tone' => 'alert' ],
      default => [ 'label' => 'Order placed', 'tone' => 'active' ],
    };
  }

  private static function shapeAddress( array $address, string $defaultId ): array
  {
    return [
      'id' => $address['id'] ?? '',
      'lines' => $address['formatted'] ?? [],
      'is_default' => ( $address['id'] ?? '' ) === $defaultId,
    ];
  }
}
