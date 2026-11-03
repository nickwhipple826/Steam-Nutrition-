<?php

namespace MMM\Shopify;

use MMM\Setup\OptionsPage;
use MMM\Traits\Singleton;
use WP_REST_Request;

/**
 * Site Settings → Shopify Connection: a one-time "Connect to Shopify"
 * button that gets the theme a permanent Admin API token.
 *
 * Why this exists: the simpler client-credentials route in
 * ShopifyAdmin.php only works when the Dev Dashboard app and the store
 * belong to the same Shopify organization. When they don't, Shopify's
 * answer is the authorization code grant — the store owner approves
 * the app once, Shopify redirects back here with a code, and the code
 * is exchanged for an offline token that lasts until the app is
 * uninstalled. That token is stored in wp_options (not autoloaded) and
 * ShopifyAdmin uses it ahead of client credentials.
 *
 * Needs the same wp-config constants as ShopifyAdmin:
 * SHOPIFY_STORE_DOMAIN (the *.myshopify.com one), SHOPIFY_ADMIN_CLIENT_ID,
 * SHOPIFY_ADMIN_CLIENT_SECRET. The callback URL shown on the page must
 * be added to the app's allowed redirect URLs in the Dev Dashboard.
 */
class ShopifyConnect
{
  use Singleton;

  public const TOKEN_OPTION = 'steam_shopify_admin_token';
  private const SCOPES_OPTION = 'steam_shopify_admin_scopes';
  private const PAGE = 'steam-shopify-connection';
  private const STATE_PREFIX = 'steam_shopify_oauth_';

  /** What the welcome offer needs. Ask for exactly this, no more. */
  public const SCOPES = 'read_customers,write_customers,read_discounts,write_discounts';

  public static function storedToken(): string
  {
    return (string) get_option( self::TOKEN_OPTION, '' );
  }

  public static function callbackUrl(): string
  {
    return rest_url( CartApi::NAMESPACE . '/shopify/callback' );
  }

  private static function hasAppCredentials(): bool
  {
    return defined( 'SHOPIFY_STORE_DOMAIN' ) && SHOPIFY_STORE_DOMAIN
      && defined( 'SHOPIFY_ADMIN_CLIENT_ID' ) && SHOPIFY_ADMIN_CLIENT_ID
      && defined( 'SHOPIFY_ADMIN_CLIENT_SECRET' ) && SHOPIFY_ADMIN_CLIENT_SECRET;
  }

  private static function shopHost(): string
  {
    return rtrim( preg_replace( '#^https?://#', '', trim( (string) SHOPIFY_STORE_DOMAIN ) ), '/' );
  }

  private static function pageUrl( array $args = [] ): string
  {
    return add_query_arg( array_merge( [ 'page' => self::PAGE ], $args ), admin_url( 'admin.php' ) );
  }

  // ---- admin page ----------------------------------------------------

  public function menu(): void
  {
    add_submenu_page(
      OptionsPage::SLUG,
      'Shopify Connection',
      'Shopify Connection',
      'manage_options',
      self::PAGE,
      [ $this, 'render' ]
    );
  }

  public function render(): void
  {
    $status = sanitize_key( $_GET['shopify'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $token = self::storedToken();
    $messages = [
      'connected' => [ 'success', 'Connected. The theme now has a permanent Admin API token.' ],
      'disconnected' => [ 'info', 'Disconnected. The stored token was removed from this site.' ],
      'denied' => [ 'error', 'Shopify did not approve the connection. Try again, and approve the app when Shopify asks.' ],
      'invalid' => [ 'error', 'The reply from Shopify could not be verified (signature, store or session mismatch). Start again from this page.' ],
      'failed' => [ 'error', 'Shopify approved, but swapping the code for a token failed. The PHP error log has Shopify\'s exact answer.' ],
    ];
    ?>
    <div class="wrap">
      <h1>Shopify Connection</h1>

      <?php if ( isset( $messages[ $status ] ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $messages[ $status ][0] ); ?>"><p><?php echo esc_html( $messages[ $status ][1] ); ?></p></div>
      <?php endif; ?>

      <p>The welcome-code signup talks to Shopify's Admin API. This page connects it once; the token it gets lasts until the app is uninstalled.</p>

      <?php if ( ! self::hasAppCredentials() ) : ?>
        <div class="notice notice-warning inline"><p>
          Add these to <code>wp-config.php</code> first, then reload this page:<br>
          <code>SHOPIFY_STORE_DOMAIN</code> (must be the <code>….myshopify.com</code> address),
          <code>SHOPIFY_ADMIN_CLIENT_ID</code>, <code>SHOPIFY_ADMIN_CLIENT_SECRET</code>.
        </p></div>
      <?php else : ?>
        <table class="form-table" role="presentation">
          <tr><th>Store</th><td><code><?php echo esc_html( self::shopHost() ); ?></code></td></tr>
          <tr><th>Status</th><td>
            <?php if ( $token ) : ?>
              <strong style="color:#008a20;">Connected</strong>
              <?php $scopes = (string) get_option( self::SCOPES_OPTION, '' ); ?>
              <?php if ( $scopes ) : ?><br><span class="description">Granted: <?php echo esc_html( $scopes ); ?></span><?php endif; ?>
            <?php else : ?>
              <strong>Not connected</strong>
            <?php endif; ?>
          </td></tr>
          <tr><th>Redirect URL</th><td>
            <input type="text" class="large-text code" readonly value="<?php echo esc_attr( self::callbackUrl() ); ?>" onclick="this.select()">
            <p class="description">Add this exact URL to the app's allowed redirect URLs in the Shopify Dev Dashboard (app → Versions → URLs), then release that version. Each environment (Local, dev, live) has its own URL — add each one you connect from.</p>
          </td></tr>
        </table>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
          <?php wp_nonce_field( 'steam_shopify_connect' ); ?>
          <input type="hidden" name="action" value="steam_shopify_connect">
          <?php submit_button( $token ? 'Reconnect to Shopify' : 'Connect to Shopify', 'primary', 'submit', false ); ?>
        </form>

        <?php if ( $token ) : ?>
          <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
            <?php wp_nonce_field( 'steam_shopify_disconnect' ); ?>
            <input type="hidden" name="action" value="steam_shopify_disconnect">
            <?php submit_button( 'Disconnect', 'secondary', 'submit', false ); ?>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
  }

  // ---- step 1: send the admin to Shopify -----------------------------

  public function start(): void
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Not allowed.' );
    }

    check_admin_referer( 'steam_shopify_connect' );

    if ( ! self::hasAppCredentials() ) {
      wp_safe_redirect( self::pageUrl() );
      exit;
    }

    // Single-use, short-lived: proves the callback answers a request
    // this site actually started.
    $state = wp_generate_password( 32, false );
    set_transient( self::STATE_PREFIX . $state, 1, 10 * MINUTE_IN_SECONDS );

    $url = add_query_arg( [
      'client_id' => SHOPIFY_ADMIN_CLIENT_ID,
      'scope' => self::SCOPES,
      'redirect_uri' => rawurlencode( self::callbackUrl() ),
      'state' => $state,
    ], sprintf( 'https://%s/admin/oauth/authorize', self::shopHost() ) );

    // Off-site on purpose, so wp_redirect rather than wp_safe_redirect.
    wp_redirect( $url );
    exit;
  }

  // ---- step 2: Shopify sends them back with a code -------------------

  public function routes(): void
  {
    register_rest_route( CartApi::NAMESPACE, '/shopify/callback', [
      'methods' => 'GET',
      'callback' => [ $this, 'callback' ],
      // Public by necessity — it's a browser redirect from Shopify with
      // no WordPress nonce. It is protected by the HMAC Shopify signs
      // the query with, the shop check, and the single-use state.
      'permission_callback' => '__return_true',
    ] );
  }

  public function callback( WP_REST_Request $request ): void
  {
    $query = $request->get_query_params();

    if ( ! self::hasAppCredentials() ) {
      $this->finish( 'invalid' );
    }

    if ( ! empty( $query['error'] ) ) {
      $this->finish( 'denied' );
    }

    $state = (string) ( $query['state'] ?? '' );
    $stateKey = self::STATE_PREFIX . preg_replace( '/[^A-Za-z0-9]/', '', $state );

    if ( ! self::validHmac( $query )
      || strtolower( (string) ( $query['shop'] ?? '' ) ) !== strtolower( self::shopHost() )
      || $state === ''
      || ! get_transient( $stateKey ) ) {
      $this->finish( 'invalid' );
    }

    delete_transient( $stateKey );

    $response = wp_remote_post( sprintf( 'https://%s/admin/oauth/access_token', self::shopHost() ), [
      'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
      'body' => wp_json_encode( [
        'client_id' => SHOPIFY_ADMIN_CLIENT_ID,
        'client_secret' => SHOPIFY_ADMIN_CLIENT_SECRET,
        'code' => (string) ( $query['code'] ?? '' ),
      ] ),
      'timeout' => 15,
    ] );

    $body = is_wp_error( $response ) ? [] : ( json_decode( wp_remote_retrieve_body( $response ), true ) ?: [] );
    $token = (string) ( $body['access_token'] ?? '' );

    if ( $token === '' ) {
      error_log( 'Shopify connect: token exchange failed: ' . ( is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response ) ) );
      $this->finish( 'failed' );
    }

    update_option( self::TOKEN_OPTION, $token, false );
    update_option( self::SCOPES_OPTION, (string) ( $body['scope'] ?? '' ), false );
    delete_transient( 'shopify_admin_token' ); // any cached client-credentials token

    $this->finish( 'connected' );
  }

  /**
   * Shopify signs every redirect: HMAC-SHA256 over the other query
   * parameters, sorted by key, joined as k=v with &, keyed by the app's
   * client secret.
   */
  public static function validHmac( array $query ): bool
  {
    $hmac = (string) ( $query['hmac'] ?? '' );

    if ( $hmac === '' ) {
      return false;
    }

    unset( $query['hmac'], $query['signature'] );
    ksort( $query );

    $message = implode( '&', array_map(
      fn( $key, $value ) => $key . '=' . ( is_array( $value ) ? implode( ',', $value ) : $value ),
      array_keys( $query ),
      $query
    ) );

    return hash_equals( hash_hmac( 'sha256', $message, SHOPIFY_ADMIN_CLIENT_SECRET ), $hmac );
  }

  private function finish( string $status ): void
  {
    wp_safe_redirect( self::pageUrl( [ 'shopify' => $status ] ) );
    exit;
  }

  public function disconnect(): void
  {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Not allowed.' );
    }

    check_admin_referer( 'steam_shopify_disconnect' );
    delete_option( self::TOKEN_OPTION );
    delete_option( self::SCOPES_OPTION );

    wp_safe_redirect( self::pageUrl( [ 'shopify' => 'disconnected' ] ) );
    exit;
  }

  private function init(): void
  {
    // After ACF registers Site Settings, so the submenu has a parent.
    add_action( 'admin_menu', [ $this, 'menu' ], 99 );
    add_action( 'admin_post_steam_shopify_connect', [ $this, 'start' ] );
    add_action( 'admin_post_steam_shopify_disconnect', [ $this, 'disconnect' ] );
    add_action( 'rest_api_init', [ $this, 'routes' ] );
  }
}
