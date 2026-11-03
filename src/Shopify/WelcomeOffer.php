<?php

namespace MMM\Shopify;

use Timber\Timber;

/**
 * Newsletter signup → a one-time welcome code for that subscriber.
 *
 * 1. The address becomes a Shopify customer subscribed to email
 *    marketing (or an existing customer gets subscribed), so the list
 *    lives in Shopify and Shopify Email / Flow can use it.
 * 2. A unique code — WELCOME-7K2QX9 — is added to the existing welcome
 *    discount as one of its codes. It inherits everything set on that
 *    discount in the Shopify admin: the 10%, one use per customer, no
 *    combining, the start date. Change the offer there, not here.
 * 3. The code is saved on the customer (metafield steam.welcome_code),
 *    so signing up twice returns the same code instead of minting a
 *    fresh one, and that holds across dev, staging and production
 *    because the record lives in Shopify, not in a WordPress database.
 * 4. The code is shown on screen and emailed through wp_mail.
 *
 * Admin API scopes the app needs: read_customers, write_customers,
 * read_discounts, write_discounts.
 */
class WelcomeOffer
{
  private const METAFIELD_NAMESPACE = 'steam';
  private const METAFIELD_KEY = 'welcome_code';

  /** No 0/O, 1/I/L: the code gets read off a phone and typed in. */
  private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
  private const CODE_LENGTH = 6;

  // ---- settings ------------------------------------------------------

  private static function option( string $key ): mixed
  {
    return function_exists( 'get_field' ) ? get_field( $key, 'option' ) : null;
  }

  /**
   * The admin URL's number (1612290883805) or a full GID both work.
   */
  public static function discountId(): string
  {
    $raw = trim( (string) self::option( 'welcome_discount_id' ) );

    if ( $raw === '' ) {
      return '';
    }

    if ( str_starts_with( $raw, 'gid://' ) ) {
      return $raw;
    }

    // Pasted the whole admin URL? Take the number off the end.
    if ( preg_match( '/(\d{6,})\/?$/', $raw, $m ) ) {
      return 'gid://shopify/DiscountCodeNode/' . $m[1];
    }

    return '';
  }

  public static function isEnabled(): bool
  {
    return (bool) self::option( 'welcome_enabled' )
      && self::discountId() !== ''
      && ShopifyAdmin::isConfigured();
  }

  public static function offerLabel(): string
  {
    return trim( (string) self::option( 'welcome_offer_label' ) ) ?: '10% off your first order';
  }

  private static function prefix(): string
  {
    $prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) self::option( 'welcome_code_prefix' ) ) );

    return $prefix ?: 'WELCOME';
  }

  private static function firstOrderOnly(): bool
  {
    $value = self::option( 'welcome_first_order_only' );

    // Unset (settings never saved) means the safe default: on.
    return $value === null || $value === '' || (bool) $value;
  }

  // ---- the signup ----------------------------------------------------

  /**
   * @return array{status: string, code: string, message: string}
   *   status: issued   — new code made
   *           existing — this address already had one; same code back
   *           ineligible — subscribed, but has ordered before (first-order-only)
   *           error    — nothing could be done; message says so
   */
  public static function signUp( string $email ): array
  {
    $email = strtolower( trim( $email ) );

    if ( ! is_email( $email ) ) {
      return self::result( 'error', '', 'That address looks incomplete — check it and try again.' );
    }

    if ( ! self::isEnabled() ) {
      return self::result( 'error', '', 'Signups are not connected yet. Please try again later.' );
    }

    // Two quick submits from the same address must not mint two codes.
    $lock = 'steam_welcome_lock_' . md5( $email );
    if ( get_transient( $lock ) ) {
      return self::result( 'error', '', 'One moment — your code is already on its way.' );
    }
    set_transient( $lock, 1, 30 );

    try {
      $customer = self::findCustomer( $email ) ?? self::createCustomer( $email );

      if ( ! $customer ) {
        return self::result( 'error', '', 'We could not sign you up just now. Please try again in a minute.' );
      }

      self::ensureSubscribed( $customer );

      if ( $customer['code'] !== '' ) {
        return self::result( 'existing', $customer['code'], 'You are already on the list — here is your welcome code again.' );
      }

      if ( self::firstOrderOnly() && $customer['orders'] > 0 ) {
        return self::result( 'ineligible', '', 'You are on the list. The welcome code is for first orders, but you will hear about every drop.' );
      }

      $code = self::issueCode();

      if ( $code === '' ) {
        return self::result( 'error', '', 'You are on the list, but your code did not generate. Please try again in a minute.' );
      }

      self::saveCode( $customer['id'], $code );
      self::sendEmail( $email, $code );

      return self::result( 'issued', $code, 'Welcome aboard. Your code is below and on its way to your inbox.' );
    } finally {
      delete_transient( $lock );
    }
  }

  private static function result( string $status, string $code, string $message ): array
  {
    return [ 'status' => $status, 'code' => $code, 'message' => $message ];
  }

  // ---- customers -----------------------------------------------------

  /**
   * @return array{id: string, orders: int, code: string, subscribed: bool}|null
   */
  private static function findCustomer( string $email ): ?array
  {
    $query = <<<'GRAPHQL'
    query FindCustomer($query: String!) {
      customers(first: 1, query: $query) {
        nodes {
          id
          numberOfOrders
          emailMarketingConsent { marketingState }
          metafield(namespace: "steam", key: "welcome_code") { value }
        }
      }
    }
    GRAPHQL;

    // Quoted, so an address with + or - is matched whole rather than
    // parsed as search syntax.
    $body = ShopifyAdmin::execute( $query, [ 'query' => 'email:"' . addcslashes( $email, '"\\' ) . '"' ] );
    $node = $body['data']['customers']['nodes'][0] ?? null;

    return $node ? self::shapeCustomer( $node ) : null;
  }

  private static function createCustomer( string $email ): ?array
  {
    $query = <<<'GRAPHQL'
    mutation CreateSubscriber($input: CustomerInput!) {
      customerCreate(input: $input) {
        customer {
          id
          numberOfOrders
          emailMarketingConsent { marketingState }
          metafield(namespace: "steam", key: "welcome_code") { value }
        }
        userErrors { field message }
      }
    }
    GRAPHQL;

    $body = ShopifyAdmin::execute( $query, [
      'input' => [
        'email' => $email,
        'tags' => [ 'newsletter', 'welcome-offer' ],
        'emailMarketingConsent' => [
          'marketingState' => 'SUBSCRIBED',
          'marketingOptInLevel' => 'SINGLE_OPT_IN',
          'consentUpdatedAt' => gmdate( 'c' ),
        ],
      ],
    ] );

    $node = $body['data']['customerCreate']['customer'] ?? null;

    if ( ! $node ) {
      error_log( 'Welcome offer: customerCreate failed: ' . wp_json_encode( $body['data']['customerCreate']['userErrors'] ?? $body ) );
      return null;
    }

    return self::shapeCustomer( $node );
  }

  private static function shapeCustomer( array $node ): array
  {
    return [
      'id' => (string) $node['id'],
      'orders' => (int) ( $node['numberOfOrders'] ?? 0 ),
      'code' => (string) ( $node['metafield']['value'] ?? '' ),
      'subscribed' => ( $node['emailMarketingConsent']['marketingState'] ?? '' ) === 'SUBSCRIBED',
    ];
  }

  /**
   * An existing customer who had not opted in has now asked to — record it.
   */
  private static function ensureSubscribed( array $customer ): void
  {
    if ( $customer['subscribed'] ) {
      return;
    }

    $query = <<<'GRAPHQL'
    mutation Subscribe($input: CustomerEmailMarketingConsentUpdateInput!) {
      customerEmailMarketingConsentUpdate(input: $input) {
        userErrors { field message }
      }
    }
    GRAPHQL;

    $body = ShopifyAdmin::execute( $query, [
      'input' => [
        'customerId' => $customer['id'],
        'emailMarketingConsent' => [
          'marketingState' => 'SUBSCRIBED',
          'marketingOptInLevel' => 'SINGLE_OPT_IN',
          'consentUpdatedAt' => gmdate( 'c' ),
        ],
      ],
    ] );

    $errors = $body['data']['customerEmailMarketingConsentUpdate']['userErrors'] ?? [];
    if ( $errors ) {
      error_log( 'Welcome offer: consent update failed: ' . wp_json_encode( $errors ) );
    }
  }

  private static function saveCode( string $customerId, string $code ): void
  {
    $query = <<<'GRAPHQL'
    mutation SaveCode($metafields: [MetafieldsSetInput!]!) {
      metafieldsSet(metafields: $metafields) {
        userErrors { field message }
      }
    }
    GRAPHQL;

    $body = ShopifyAdmin::execute( $query, [
      'metafields' => [ [
        'ownerId' => $customerId,
        'namespace' => self::METAFIELD_NAMESPACE,
        'key' => self::METAFIELD_KEY,
        'type' => 'single_line_text_field',
        'value' => $code,
      ] ],
    ] );

    $errors = $body['data']['metafieldsSet']['userErrors'] ?? [];
    if ( $errors ) {
      error_log( 'Welcome offer: saving code to customer failed: ' . wp_json_encode( $errors ) );
    }
  }

  // ---- codes ---------------------------------------------------------

  public static function generateCode(): string
  {
    $chars = '';
    $max = strlen( self::ALPHABET ) - 1;

    for ( $i = 0; $i < self::CODE_LENGTH; $i++ ) {
      $chars .= self::ALPHABET[ random_int( 0, $max ) ];
    }

    return self::prefix() . '-' . $chars;
  }

  /**
   * Add a fresh code to the welcome discount and return it.
   *
   * Shopify adds codes as a background job. It normally finishes in
   * well under a second, so this waits briefly to learn whether the
   * code was accepted (a collision with an existing code is the one
   * realistic failure) and retries once with a new code if not. If the
   * job is merely slow, the code is returned anyway: it goes live
   * moments later, long before anyone reaches checkout.
   */
  private static function issueCode(): string
  {
    $add = <<<'GRAPHQL'
    mutation AddCode($discountId: ID!, $codes: [DiscountRedeemCodeInput!]!) {
      discountRedeemCodeBulkAdd(discountId: $discountId, codes: $codes) {
        bulkCreation { id }
        userErrors { field message }
      }
    }
    GRAPHQL;

    $check = <<<'GRAPHQL'
    query CheckCode($id: ID!) {
      discountRedeemCodeBulkCreation(id: $id) {
        done
        importedCount
        failedCount
      }
    }
    GRAPHQL;

    for ( $attempt = 0; $attempt < 2; $attempt++ ) {
      $code = self::generateCode();

      $body = ShopifyAdmin::execute( $add, [
        'discountId' => self::discountId(),
        'codes' => [ [ 'code' => $code ] ],
      ] );

      $jobId = $body['data']['discountRedeemCodeBulkAdd']['bulkCreation']['id'] ?? null;

      if ( ! $jobId ) {
        // Wrong discount ID, missing scope, or the discount was deleted —
        // retrying will not help.
        error_log( 'Welcome offer: discountRedeemCodeBulkAdd failed: ' . wp_json_encode( $body['data']['discountRedeemCodeBulkAdd']['userErrors'] ?? $body ) );
        return '';
      }

      for ( $poll = 0; $poll < 5; $poll++ ) {
        usleep( 400000 );

        $job = ShopifyAdmin::execute( $check, [ 'id' => $jobId ] )['data']['discountRedeemCodeBulkCreation'] ?? null;

        if ( $job && $job['done'] ) {
          if ( (int) $job['importedCount'] > 0 ) {
            return $code;
          }

          // Rejected — almost certainly a duplicate. Try a new one.
          continue 2;
        }
      }

      // Still running after ~2s. It will land; hand the code over.
      return $code;
    }

    return '';
  }

  // ---- email ---------------------------------------------------------

  private static function sendEmail( string $email, string $code ): void
  {
    $subject = trim( (string) self::option( 'welcome_email_subject' ) )
      ?: sprintf( 'Your welcome code: %s', $code );

    $html = Timber::compile( 'views/emails/welcome-code.twig', [
      'site' => Timber::context()['site'] ?? null,
      'code' => $code,
      'offer' => self::offerLabel(),
      'intro' => (string) self::option( 'welcome_email_intro' ),
      'shop_url' => home_url( '/' ),
    ] );

    $sent = wp_mail( $email, $subject, $html, [ 'Content-Type: text/html; charset=UTF-8' ] );

    if ( ! $sent ) {
      // The code was still shown on screen, so the shopper has it; this
      // is for whoever reads the logs.
      error_log( 'Welcome offer: wp_mail failed for a signup (code ' . $code . ').' );
    }
  }
}
