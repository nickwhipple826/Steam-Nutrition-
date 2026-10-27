<?php

namespace MMM\Shopify;

use MMM\Traits\Singleton;

/**
 * /cart and /account/… — store pages with no WordPress post behind
 * them, routed the same way ProductRoute routes /products/{handle}.
 *
 * All of them are one person's data, so every one is sent no-cache.
 * WP Engine's page cache also needs to be told to leave them alone:
 * User Portal → your environment → Cache → Cache exclusions, add
 *   ^/cart
 *   ^/account
 */
class StoreRoutes
{
  use Singleton;

  public const QUERY_VAR = 'steam_store';
  public const PARAM_VAR = 'steam_store_param';

  private const REWRITE_VERSION = '1';
  private const REWRITE_OPTION = 'steam_store_rewrite_version';

  /** URL pattern => route name. */
  private const ROUTES = [
    '^cart/?$' => 'cart',
    '^account/?$' => 'account',
    '^account/login/?$' => 'account-login',
    '^account/callback/?$' => 'account-callback',
    '^account/logout/?$' => 'account-logout',
    '^account/orders/?$' => 'account-orders',
    '^account/orders/([0-9]+)/?$' => 'account-order',
  ];

  public function addRewrites(): void
  {
    foreach ( self::ROUTES as $pattern => $route ) {
      $query = 'index.php?' . self::QUERY_VAR . '=' . $route;

      if ( str_contains( $pattern, '(' ) ) {
        $query .= '&' . self::PARAM_VAR . '=$matches[1]';
      }

      add_rewrite_rule( $pattern, $query, 'top' );
    }

    if ( get_option( self::REWRITE_OPTION ) !== self::REWRITE_VERSION ) {
      flush_rewrite_rules( false );
      update_option( self::REWRITE_OPTION, self::REWRITE_VERSION );
    }
  }

  public function queryVars( array $vars ): array
  {
    $vars[] = self::QUERY_VAR;
    $vars[] = self::PARAM_VAR;

    return $vars;
  }

  public static function route(): string
  {
    return (string) get_query_var( self::QUERY_VAR );
  }

  public static function param(): string
  {
    return (string) get_query_var( self::PARAM_VAR );
  }

  public function template( string $template ): string
  {
    if ( ! self::route() ) {
      return $template;
    }

    nocache_headers();

    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
      define( 'DONOTCACHEPAGE', true );
    }

    return locate_template( 'store.php' ) ?: $template;
  }

  public function canonical( $redirect )
  {
    return self::route() ? false : $redirect;
  }

  public function bodyClass( array $classes ): array
  {
    $route = self::route();

    if ( ! $route ) {
      return $classes;
    }

    $classes = array_diff( $classes, [ 'home', 'blog' ] );
    $classes[] = str_starts_with( $route, 'account' ) ? 'account-page' : 'cart-page';

    return array_values( $classes );
  }

  /**
   * Store pages carry no SEO value and are per-person.
   */
  public function robots( array $robots ): array
  {
    if ( self::route() ) {
      $robots['noindex'] = true;
      $robots['nofollow'] = true;
    }

    return $robots;
  }

  private function init(): void
  {
    add_action( 'init', [ $this, 'addRewrites' ] );
    add_filter( 'query_vars', [ $this, 'queryVars' ] );
    add_filter( 'template_include', [ $this, 'template' ], 99 );
    add_filter( 'redirect_canonical', [ $this, 'canonical' ] );
    add_filter( 'body_class', [ $this, 'bodyClass' ], 20 );
    add_filter( 'wp_robots', [ $this, 'robots' ] );
  }
}
