<?php
/** @noinspection SpellCheckingInspection */

namespace MMM\Setup;

use MMM\Traits\Singleton;
use WP_REST_Response;

class Security {
  use Singleton;

  private function init(): void
  {
    $this->cleanupHead();
    $this->disableXmlRpc();
    $this->disableFileEditing();
    $this->protectAuthorEnumeration();

    add_filter( 'rest_index', [ $this, 'filterRestIndex' ] );
    add_action( 'send_headers', [ $this, 'addSecurityHeaders' ] );
  }

  /**
   * Remove unnecessary WP metadata from <head>.
   */
  private function cleanupHead(): void
  {
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

    remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
  }

  /**
   * Disable XML-RPC.
   */
  private function disableXmlRpc(): void
  {
    add_filter( 'xmlrpc_enabled', '__return_false' );
  }

  /**
   * Disable theme/plugin editor in admin.
   * (Ideally set in wp-config.php, but safe fallback here.)
   */
  private function disableFileEditing(): void
  {
    if ( !defined( 'DISALLOW_FILE_EDIT' ) ) {
      define( 'DISALLOW_FILE_EDIT', true );
    }
  }

  /**
   * Remove generator and some environment hints from REST index.
   */
  public function filterRestIndex( WP_REST_Response $response ): WP_REST_Response
  {
    $data = $response->get_data();

    unset(
      $data['generator'],
      $data['gmt_offset'],
      $data['timezone_string']
    );

    $response->set_data( $data );

    return $response;
  }

  /**
   * Prevent username enumeration via /?author=N.
   */
  private function protectAuthorEnumeration(): void
  {
    add_action( 'template_redirect', function (): void {
      if ( !is_admin() && isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) {
        wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
      }
    } );
  }

  /**
   * Add baseline security headers.
   */
  public function addSecurityHeaders(): void
  {
    if ( is_admin() ) {
      return;
    }

    // Remove potentially exposed server header
    header_remove( 'X-Powered-By' );

    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'Referrer-Policy: no-referrer-when-cross-origin' );

    // Modern baseline CSP (safe for typical WP themes)
    header( "Content-Security-Policy: default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'" );

    // Disable unused browser features
    header( 'Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()' );
  }
}