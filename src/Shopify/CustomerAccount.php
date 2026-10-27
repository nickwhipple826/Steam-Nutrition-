<?php

namespace MMM\Shopify;

/**
 * Signing customers in with Shopify's Customer Account API.
 *
 * Shopify owns the login itself — the shopper is sent to Shopify's
 * sign-in page (email + one-time code, branded in Shopify's settings)
 * and comes back to /account/callback. From then on every account
 * screen is this theme's own, filled from the Customer Account API.
 *
 * Tokens never reach the browser. The browser holds one random session
 * ID in an HttpOnly cookie; the tokens sit in a transient keyed by a
 * hash of it.
 *
 * Setup — Shopify admin → Sales channels → Headless → your storefront →
 * Customer Account API settings:
 *   - Client type: Confidential
 *   - Callback URI:  https://YOUR-SITE/account/callback
 *   - Logout URI:    https://YOUR-SITE/
 * then in wp-config.php:
 *   define( 'SHOPIFY_CUSTOMER_CLIENT_ID', '…' );
 *   define( 'SHOPIFY_CUSTOMER_CLIENT_SECRET', '…' );
 *
 * A public client also works (leave the secret undefined, and add the
 * site under JavaScript origins) — PKCE is sent either way.
 *
 * Shopify refuses http:// and localhost callbacks, so test this on the
 * staging site or through a tunnel, not plain Local.
 */
class CustomerAccount
{
  public const SESSION_COOKIE = 'steam_account';
  private const STATE_COOKIE = 'steam_oauth_state';
  private const SCOPE = 'openid email customer-account-api:full';

  // How long a signed-in session lasts without a visit. The refresh
  // token decides the real ceiling; this only bounds the transient.
  private const SESSION_TTL = 30 * DAY_IN_SECONDS;
  private const LOGIN_TTL = 10 * MINUTE_IN_SECONDS;

  /** Memoised for the request, so refresh runs at most once. */
  private static ?array $session = null;
  private static bool $sessionLoaded = false;

  public static function isConfigured(): bool
  {
    return defined( 'SHOPIFY_CUSTOMER_CLIENT_ID' )
      && SHOPIFY_CUSTOMER_CLIENT_ID
      && self::shopDomain() !== '';
  }

  public static function isLoggedIn(): bool
  {
    return self::accessToken() !== null;
  }

  public static function callbackUrl(): string
  {
    return home_url( '/account/callback' );
  }

  // ---------------------------------------------------------------------
  // Sign in
  // ---------------------------------------------------------------------

  /**
   * Where to send the shopper to sign in. Generates the state and PKCE
   * verifier and parks them until the callback.
   */
  public static function loginUrl( string $returnTo = '' ): string
  {
    $endpoints = self::discovery();

    if ( empty( $endpoints['authorization_endpoint'] ) ) {
      return '';
    }

    $state = self::random( 16 );
    $verifier = self::random( 32 );
    $challenge = self::base64url( hash( 'sha256', $verifier, true ) );

    set_transient( self::loginKey( $state ), [
      'verifier' => $verifier,
      'return_to' => self::safeReturn( $returnTo ),
    ], self::LOGIN_TTL );

    // Bind the state to this browser, so a login link minted elsewhere
    // cannot sign this browser into someone else's account.
    self::setCookie( self::STATE_COOKIE, $state, time() + self::LOGIN_TTL );

    return add_query_arg( array_map( 'rawurlencode', [
      'client_id' => SHOPIFY_CUSTOMER_CLIENT_ID,
      'response_type' => 'code',
      'redirect_uri' => self::callbackUrl(),
      'scope' => self::SCOPE,
      'state' => $state,
      'code_challenge' => $challenge,
      'code_challenge_method' => 'S256',
    ] ), $endpoints['authorization_endpoint'] );
  }

  /**
   * Finish sign-in. Returns where to send the shopper next, or an error
   * message to show on /account.
   *
   * @return array{redirect: string, error: string}
   */
  public static function handleCallback( string $code, string $state ): array
  {
    $fail = fn( string $why ) => [ 'redirect' => '', 'error' => $why ];
    $cookieState = isset( $_COOKIE[ self::STATE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::STATE_COOKIE ] ) ) : '';

    if ( ! $code || ! $state || ! $cookieState || ! hash_equals( $cookieState, $state ) ) {
      return $fail( 'That sign-in link has expired. Please sign in again.' );
    }

    $pending = get_transient( self::loginKey( $state ) );
    delete_transient( self::loginKey( $state ) );
    self::setCookie( self::STATE_COOKIE, '', time() - HOUR_IN_SECONDS );

    if ( ! is_array( $pending ) ) {
      return $fail( 'That sign-in link has expired. Please sign in again.' );
    }

    $tokens = self::tokenRequest( [
      'grant_type' => 'authorization_code',
      'client_id' => SHOPIFY_CUSTOMER_CLIENT_ID,
      'redirect_uri' => self::callbackUrl(),
      'code' => $code,
      'code_verifier' => $pending['verifier'],
    ] );

    if ( ! $tokens ) {
      return $fail( 'We could not finish signing you in. Please try again.' );
    }

    self::startSession( $tokens );

    // Carry the bag across: checkout should open signed in.
    ShopifyCart::attachCustomer( $tokens['access_token'] );

    return [ 'redirect' => $pending['return_to'] ?: home_url( '/account/' ), 'error' => '' ];
  }

  // ---------------------------------------------------------------------
  // Sign out
  // ---------------------------------------------------------------------

  /**
   * End the session here, and return Shopify's logout URL so the
   * Shopify side of the session ends too.
   */
  public static function logout(): string
  {
    $session = self::session();
    self::endSession();

    $endpoint = self::discovery()['end_session_endpoint'] ?? '';

    if ( ! $endpoint || empty( $session['id_token'] ) ) {
      return home_url( '/' );
    }

    return add_query_arg( array_map( 'rawurlencode', [
      'id_token_hint' => $session['id_token'],
      'post_logout_redirect_uri' => home_url( '/' ),
    ] ), $endpoint );
  }

  // ---------------------------------------------------------------------
  // Calling the API
  // ---------------------------------------------------------------------

  /**
   * A live access token for the signed-in customer, refreshed if it is
   * about to expire. Null when nobody is signed in.
   */
  public static function accessToken(): ?string
  {
    if ( ! self::isConfigured() ) {
      return null;
    }

    $session = self::session();

    if ( ! $session ) {
      return null;
    }

    if ( $session['expires_at'] - 60 > time() ) {
      return $session['access_token'];
    }

    return self::refresh( $session );
  }

  /**
   * One Customer Account API request. Returns the decoded body; signs
   * the customer out if Shopify says the token is no good.
   */
  public static function query( string $query, array $variables = [] ): array
  {
    $token = self::accessToken();
    $url = self::discovery()['graphql_api'] ?? '';

    if ( ! $token || ! $url ) {
      return [];
    }

    $response = wp_remote_post( $url, [
      'headers' => [
        'Content-Type' => 'application/json',
        // The raw token — the Customer Account API takes no "Bearer".
        'Authorization' => $token,
      ],
      'body' => wp_json_encode( [ 'query' => $query, 'variables' => (object) $variables ] ),
      'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
      error_log( 'Customer Account API request failed: ' . $response->get_error_message() );
      return [];
    }

    if ( 401 === wp_remote_retrieve_response_code( $response ) ) {
      self::endSession();
      return [];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];

    if ( ! empty( $body['errors'] ) ) {
      error_log( 'Customer Account API errors: ' . wp_json_encode( $body['errors'] ) );
    }

    return $body;
  }

  // ---------------------------------------------------------------------
  // Internals
  // ---------------------------------------------------------------------

  /**
   * The endpoints, from Shopify's two well-known documents. Shopify asks
   * that these be discovered rather than hard-coded. Cached for a day.
   *
   * @return array{authorization_endpoint?: string, token_endpoint?: string, end_session_endpoint?: string, graphql_api?: string}
   */
  private static function discovery(): array
  {
    if ( ! self::isConfigured() ) {
      return [];
    }

    $key = 'steam_cust_discovery_' . md5( self::shopDomain() );
    $cached = get_transient( $key );

    if ( is_array( $cached ) ) {
      return $cached;
    }

    $openid = self::getJson( 'https://' . self::shopDomain() . '/.well-known/openid-configuration' );
    $api = self::getJson( 'https://' . self::shopDomain() . '/.well-known/customer-account-api' );

    $endpoints = array_filter( [
      'authorization_endpoint' => $openid['authorization_endpoint'] ?? '',
      'token_endpoint' => $openid['token_endpoint'] ?? '',
      'end_session_endpoint' => $openid['end_session_endpoint'] ?? '',
      'graphql_api' => $api['graphql_api'] ?? '',
    ] );

    // Only cache a complete answer, so a blip does not stick for a day.
    if ( isset( $endpoints['authorization_endpoint'], $endpoints['token_endpoint'], $endpoints['graphql_api'] ) ) {
      set_transient( $key, $endpoints, DAY_IN_SECONDS );
    }

    return $endpoints;
  }

  /**
   * POST to the token endpoint. Returns the token response, or null.
   */
  private static function tokenRequest( array $body ): ?array
  {
    $endpoint = self::discovery()['token_endpoint'] ?? '';

    if ( ! $endpoint ) {
      return null;
    }

    $headers = [ 'Content-Type' => 'application/x-www-form-urlencoded' ];

    if ( defined( 'SHOPIFY_CUSTOMER_CLIENT_SECRET' ) && SHOPIFY_CUSTOMER_CLIENT_SECRET ) {
      // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
      $headers['Authorization'] = 'Basic ' . base64_encode( SHOPIFY_CUSTOMER_CLIENT_ID . ':' . SHOPIFY_CUSTOMER_CLIENT_SECRET );
    } else {
      // Public clients are checked against the JavaScript origins list.
      $headers['Origin'] = untrailingslashit( home_url() );
    }

    $response = wp_remote_post( $endpoint, [
      'headers' => $headers,
      'body' => $body,
      'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
      error_log( 'Customer Account token request failed: ' . $response->get_error_message() );
      return null;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];

    if ( empty( $data['access_token'] ) ) {
      error_log( 'Customer Account token request refused: ' . wp_remote_retrieve_body( $response ) );
      return null;
    }

    return $data;
  }

  private static function refresh( array $session ): ?string
  {
    if ( empty( $session['refresh_token'] ) ) {
      self::endSession();
      return null;
    }

    $tokens = self::tokenRequest( [
      'grant_type' => 'refresh_token',
      'client_id' => SHOPIFY_CUSTOMER_CLIENT_ID,
      'refresh_token' => $session['refresh_token'],
    ] );

    if ( ! $tokens ) {
      self::endSession();
      return null;
    }

    // A refresh does not always send a new id_token or refresh_token.
    $tokens['id_token'] = $tokens['id_token'] ?? $session['id_token'];
    $tokens['refresh_token'] = $tokens['refresh_token'] ?? $session['refresh_token'];

    self::saveSession( self::sessionId(), $tokens );

    return $tokens['access_token'];
  }

  private static function startSession( array $tokens ): void
  {
    // A fresh ID on every sign-in, never one carried over.
    $id = self::random( 32 );
    self::setCookie( self::SESSION_COOKIE, $id, time() + self::SESSION_TTL );
    self::saveSession( $id, $tokens );
  }

  private static function saveSession( string $id, array $tokens ): void
  {
    $session = [
      'access_token' => $tokens['access_token'],
      'expires_at' => time() + (int) ( $tokens['expires_in'] ?? 3600 ),
      'refresh_token' => $tokens['refresh_token'] ?? '',
      'id_token' => $tokens['id_token'] ?? '',
    ];

    set_transient( self::sessionKey( $id ), $session, self::SESSION_TTL );

    self::$session = $session;
    self::$sessionLoaded = true;
  }

  private static function session(): ?array
  {
    if ( self::$sessionLoaded ) {
      return self::$session;
    }

    self::$sessionLoaded = true;
    $id = self::sessionId();
    $stored = $id ? get_transient( self::sessionKey( $id ) ) : false;
    self::$session = is_array( $stored ) ? $stored : null;

    return self::$session;
  }

  private static function endSession(): void
  {
    $id = self::sessionId();

    if ( $id ) {
      delete_transient( self::sessionKey( $id ) );
    }

    self::setCookie( self::SESSION_COOKIE, '', time() - HOUR_IN_SECONDS );
    self::$session = null;
    self::$sessionLoaded = true;
  }

  private static function sessionId(): string
  {
    $id = isset( $_COOKIE[ self::SESSION_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::SESSION_COOKIE ] ) ) : '';

    return preg_match( '/^[A-Za-z0-9_\-]{40,64}$/', $id ) ? $id : '';
  }

  // The cookie value is never the storage key itself, so reading the
  // options table does not hand anyone a usable cookie.
  private static function sessionKey( string $id ): string
  {
    return 'steam_cust_' . hash_hmac( 'sha256', $id, wp_salt( 'auth' ) );
  }

  private static function loginKey( string $state ): string
  {
    return 'steam_login_' . hash_hmac( 'sha256', $state, wp_salt( 'auth' ) );
  }

  private static function shopDomain(): string
  {
    if ( defined( 'SHOPIFY_CUSTOMER_ACCOUNT_DOMAIN' ) && SHOPIFY_CUSTOMER_ACCOUNT_DOMAIN ) {
      return SHOPIFY_CUSTOMER_ACCOUNT_DOMAIN;
    }

    return defined( 'SHOPIFY_STORE_DOMAIN' ) ? (string) SHOPIFY_STORE_DOMAIN : '';
  }

  private static function getJson( string $url ): array
  {
    $response = wp_remote_get( $url, [ 'timeout' => 10 ] );

    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
      error_log( 'Customer Account discovery failed for ' . $url );
      return [];
    }

    return json_decode( wp_remote_retrieve_body( $response ), true ) ?: [];
  }

  /**
   * Only same-site paths, so the login cannot be used as an open
   * redirect to somewhere else.
   */
  private static function safeReturn( string $url ): string
  {
    if ( ! $url ) {
      return '';
    }

    return wp_validate_redirect( $url, '' );
  }

  private static function setCookie( string $name, string $value, int $expires ): void
  {
    if ( headers_sent() ) {
      return;
    }

    setcookie( $name, $value, [
      'expires' => $expires,
      'path' => COOKIEPATH ?: '/',
      'domain' => COOKIE_DOMAIN ?: '',
      'secure' => is_ssl(),
      'httponly' => true,
      // Lax, not Strict: the callback is a top-level redirect back from
      // Shopify, and Strict would drop the state cookie on it.
      'samesite' => 'Lax',
    ] );

    if ( $expires > time() ) {
      $_COOKIE[ $name ] = $value;
    } else {
      unset( $_COOKIE[ $name ] );
    }
  }

  private static function random( int $bytes ): string
  {
    return self::base64url( random_bytes( $bytes ) );
  }

  private static function base64url( string $raw ): string
  {
    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
    return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
  }
}
