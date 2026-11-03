<?php

namespace MMM\Shopify;

/**
 * Shopify Admin GraphQL API — server-side only, never sent to a browser.
 *
 * Used where the Storefront API can't reach: creating subscribers and
 * issuing discount codes (WelcomeOffer.php).
 *
 * Since January 2026 Shopify no longer shows a permanent Admin API
 * token for a store's own custom app. The app is created in the Dev
 * Dashboard, and its client ID + secret are swapped for a token that
 * lasts 24 hours (the "client credentials grant"). This class does that
 * swap itself, caches the token, and fetches a fresh one when it
 * expires or Shopify rejects it — nothing to rotate by hand.
 *
 * In wp-config.php, beside the Storefront constants:
 *
 *   define( 'SHOPIFY_ADMIN_CLIENT_ID', '…' );
 *   define( 'SHOPIFY_ADMIN_CLIENT_SECRET', '…' );
 *
 * Which token is used, first match wins:
 *   1. SHOPIFY_ADMIN_TOKEN, if defined — an old-style permanent token
 *   2. the token saved by Site Settings → Shopify Connection
 *      (ShopifyConnect.php) — needed when the app and the store sit in
 *      different Shopify organizations, where (3) is refused
 *   3. a 24-hour token from the client credentials grant
 *
 * SHOPIFY_STORE_DOMAIN must be the *.myshopify.com address — the token
 * endpoint does not answer on a custom domain.
 */
class ShopifyAdmin
{
  private const API_VERSION = '2026-07';
  private const TOKEN_CACHE = 'shopify_admin_token';

  public static function isConfigured(): bool
  {
    if ( ! defined( 'SHOPIFY_STORE_DOMAIN' ) || ! SHOPIFY_STORE_DOMAIN ) {
      return false;
    }

    if ( self::hasStaticToken() || ShopifyConnect::storedToken() !== '' ) {
      return true;
    }

    return defined( 'SHOPIFY_ADMIN_CLIENT_ID' ) && SHOPIFY_ADMIN_CLIENT_ID
      && defined( 'SHOPIFY_ADMIN_CLIENT_SECRET' ) && SHOPIFY_ADMIN_CLIENT_SECRET;
  }

  /**
   * Run one Admin API request.
   *
   * @return array The decoded body: ['data' => …] and/or ['errors' => …].
   *               Empty when not configured or the request never landed.
   */
  public static function execute( string $query, array $variables = [] ): array
  {
    if ( ! self::isConfigured() ) {
      return [];
    }

    $response = self::post( $query, $variables, self::token() );

    // A cached 24-hour token Shopify no longer accepts — revoked, or the
    // app's scopes changed. One fresh token, one retry. (A permanent
    // token that is refused means the app was uninstalled; retrying
    // cannot fix that, so it just logs.)
    if ( 401 === ( $response['status'] ?? 0 ) ) {
      if ( self::hasPermanentToken() ) {
        error_log( 'Shopify Admin: permanent token refused (401). Reconnect under Site Settings → Shopify Connection.' );
      } else {
        delete_transient( self::TOKEN_CACHE );
        $response = self::post( $query, $variables, self::token() );
      }
    }

    $body = $response['body'] ?? [];

    if ( ! empty( $body['errors'] ) ) {
      error_log( 'Shopify Admin GraphQL errors: ' . wp_json_encode( $body['errors'] ) );
    }

    return $body;
  }

  /**
   * @return array{status: int, body: array}
   */
  private static function post( string $query, array $variables, string $token ): array
  {
    if ( $token === '' ) {
      return [ 'status' => 0, 'body' => [] ];
    }

    $url = sprintf( 'https://%s/admin/api/%s/graphql.json', self::shopHost(), self::API_VERSION );

    $response = wp_remote_post( $url, [
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Shopify-Access-Token' => $token,
      ],
      // (object) so an empty variables list encodes as {} — GraphQL
      // rejects [] there.
      'body' => wp_json_encode( [ 'query' => $query, 'variables' => (object) $variables ] ),
      'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
      error_log( 'Shopify Admin request failed: ' . $response->get_error_message() );
      return [ 'status' => 0, 'body' => [] ];
    }

    return [
      'status' => (int) wp_remote_retrieve_response_code( $response ),
      'body' => json_decode( wp_remote_retrieve_body( $response ), true ) ?: [],
    ];
  }

  private static function hasStaticToken(): bool
  {
    return defined( 'SHOPIFY_ADMIN_TOKEN' ) && SHOPIFY_ADMIN_TOKEN;
  }

  private static function hasPermanentToken(): bool
  {
    return self::hasStaticToken() || ShopifyConnect::storedToken() !== '';
  }

  /**
   * A working access token, from cache when there is one.
   */
  private static function token(): string
  {
    if ( self::hasStaticToken() ) {
      return SHOPIFY_ADMIN_TOKEN;
    }

    $stored = ShopifyConnect::storedToken();
    if ( $stored !== '' ) {
      return $stored;
    }

    if ( ! defined( 'SHOPIFY_ADMIN_CLIENT_ID' ) || ! defined( 'SHOPIFY_ADMIN_CLIENT_SECRET' ) ) {
      return '';
    }

    $cached = get_transient( self::TOKEN_CACHE );

    if ( is_string( $cached ) && $cached !== '' ) {
      return $cached;
    }

    $response = wp_remote_post( sprintf( 'https://%s/admin/oauth/access_token', self::shopHost() ), [
      'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
      'body' => [
        'grant_type' => 'client_credentials',
        'client_id' => SHOPIFY_ADMIN_CLIENT_ID,
        'client_secret' => SHOPIFY_ADMIN_CLIENT_SECRET,
      ],
      'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
      error_log( 'Shopify Admin token request failed: ' . $response->get_error_message() );
      return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];
    $token = (string) ( $body['access_token'] ?? '' );

    if ( $token === '' ) {
      // Typical causes: the app is not installed on this store, the app
      // and store sit in different Shopify organizations, or the domain
      // is a custom one rather than *.myshopify.com.
      error_log( 'Shopify Admin token request refused: ' . wp_remote_retrieve_body( $response ) );
      return '';
    }

    // Shopify says how long it lasts (24h today). Retire it ten minutes
    // early so a request never goes out on a token about to lapse.
    $ttl = max( 300, (int) ( $body['expires_in'] ?? DAY_IN_SECONDS ) - 600 );
    set_transient( self::TOKEN_CACHE, $token, $ttl );

    return $token;
  }

  /**
   * The bare host, whatever form the constant was written in.
   */
  private static function shopHost(): string
  {
    $host = preg_replace( '#^https?://#', '', trim( (string) SHOPIFY_STORE_DOMAIN ) );

    return rtrim( $host, '/' );
  }
}
