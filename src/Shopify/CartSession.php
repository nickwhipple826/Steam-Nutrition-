<?php

namespace MMM\Shopify;

/**
 * Which Shopify cart belongs to this browser.
 *
 * Two cookies:
 *   steam_cart        the cart GID. HttpOnly — page script never needs
 *                     it, and a cart can carry the buyer's email.
 *   steam_cart_count  the item count, readable by script, so the header
 *                     badge is right on a cached page without a request.
 */
class CartSession
{
  public const COOKIE = 'steam_cart';
  public const COUNT_COOKIE = 'steam_cart_count';

  // Shopify keeps an untouched cart for about ten days; outliving it
  // only means one wasted lookup before a fresh cart is started.
  private const LIFETIME = 14 * DAY_IN_SECONDS;

  public static function cartId(): string
  {
    $id = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';

    // Only ever a Shopify cart GID (which carries a ?key= suffix).
    return preg_match( '#^gid://shopify/Cart/[A-Za-z0-9\-_?=&]+$#', $id ) ? $id : '';
  }

  public static function store( string $cartId, int $count ): void
  {
    self::set( self::COOKIE, $cartId, time() + self::LIFETIME, true );
    self::set( self::COUNT_COOKIE, (string) $count, time() + self::LIFETIME, false );
  }

  public static function clear(): void
  {
    self::set( self::COOKIE, '', time() - HOUR_IN_SECONDS, true );
    self::set( self::COUNT_COOKIE, '', time() - HOUR_IN_SECONDS, false );
  }

  private static function set( string $name, string $value, int $expires, bool $httpOnly ): void
  {
    if ( headers_sent() ) {
      return;
    }

    setcookie( $name, $value, [
      'expires' => $expires,
      'path' => COOKIEPATH ?: '/',
      'domain' => COOKIE_DOMAIN ?: '',
      'secure' => is_ssl(),
      'httponly' => $httpOnly,
      'samesite' => 'Lax',
    ] );

    // Visible to the rest of this request too.
    if ( $expires > time() ) {
      $_COOKIE[ $name ] = $value;
    } else {
      unset( $_COOKIE[ $name ] );
    }
  }
}
