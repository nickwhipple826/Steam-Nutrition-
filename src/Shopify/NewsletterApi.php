<?php

namespace MMM\Shopify;

use MMM\Traits\Singleton;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /wp-json/steam/v1/newsletter — what ui/newsletter.ts sends a
 * signup to. The work is in WelcomeOffer.php; this is the door.
 *
 * Every signup can mint a real discount code, so the door has two
 * guards a bot has to get past:
 *   - a honeypot field humans never see or fill in
 *   - a per-IP limit (5 an hour), stored as a transient
 *
 * POST, so WP Engine's page cache never stores one person's code and
 * hands it to the next visitor.
 */
class NewsletterApi
{
  use Singleton;

  private const PER_HOUR = 5;

  public function routes(): void
  {
    register_rest_route( CartApi::NAMESPACE, '/newsletter', [
      'methods' => 'POST',
      'callback' => [ $this, 'signUp' ],
      'permission_callback' => '__return_true',
    ] );
  }

  public function signUp( WP_REST_Request $request ): WP_REST_Response
  {
    // Honeypot filled in: a bot. Answer like a success so it learns
    // nothing, and do nothing.
    if ( trim( (string) $request->get_param( 'company' ) ) !== '' ) {
      return $this->respond( [ 'status' => 'subscribed', 'code' => '', 'message' => 'Thanks — you are on the list.' ] );
    }

    $key = 'steam_signup_rate_' . md5( $this->clientIp() );
    $count = (int) get_transient( $key );

    if ( $count >= self::PER_HOUR ) {
      return $this->respond( [ 'status' => 'error', 'code' => '', 'message' => 'Too many signups from this connection. Please try again later.' ], 429 );
    }

    set_transient( $key, $count + 1, HOUR_IN_SECONDS );

    $result = WelcomeOffer::signUp( (string) $request->get_param( 'email' ) );

    return $this->respond( $result, 'error' === $result['status'] ? 422 : 200 );
  }

  private function respond( array $result, int $status = 200 ): WP_REST_Response
  {
    $response = new WP_REST_Response( $result, $status );
    $response->header( 'Cache-Control', 'no-store' );

    return $response;
  }

  /**
   * WP Engine sits behind a proxy, so REMOTE_ADDR is the proxy for
   * everyone; the real address is the first in X-Forwarded-For.
   */
  private function clientIp(): string
  {
    $forwarded = (string) ( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' );

    if ( $forwarded !== '' ) {
      return trim( explode( ',', $forwarded )[0] );
    }

    return (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
  }

  private function init(): void
  {
    add_action( 'rest_api_init', [ $this, 'routes' ] );
  }
}
