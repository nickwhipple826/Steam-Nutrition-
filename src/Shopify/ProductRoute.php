<?php

namespace MMM\Shopify;

use MMM\Traits\Singleton;

/**
 * /products/{handle} — a product page with no WordPress post behind it.
 *
 * Products live in Shopify, so there is nothing to make a page or a
 * post type out of. A rewrite rule catches the URL the product cards
 * already link to, and product.php renders it from the Storefront API.
 */
class ProductRoute
{
  use Singleton;

  public const QUERY_VAR = 'steam_product';

  /**
   * Bump this whenever the rewrite rule changes. The rules are flushed
   * once when the stored value differs, so nobody has to remember to
   * re-save Settings → Permalinks after a deploy.
   */
  private const REWRITE_VERSION = '1';
  private const REWRITE_OPTION = 'steam_product_rewrite_version';

  public function addRewrite(): void
  {
    add_rewrite_rule(
      '^products/([^/]+)/?$',
      'index.php?' . self::QUERY_VAR . '=$matches[1]',
      'top'
    );

    if ( get_option( self::REWRITE_OPTION ) !== self::REWRITE_VERSION ) {
      flush_rewrite_rules( false );
      update_option( self::REWRITE_OPTION, self::REWRITE_VERSION );
    }
  }

  public function queryVars( array $vars ): array
  {
    $vars[] = self::QUERY_VAR;

    return $vars;
  }

  public static function isProductRequest(): bool
  {
    return (bool) get_query_var( self::QUERY_VAR );
  }

  public function template( string $template ): string
  {
    if ( ! self::isProductRequest() ) {
      return $template;
    }

    return locate_template( 'product.php' ) ?: $template;
  }

  /**
   * WordPress sees no post here and treats the request as the blog
   * index. Keep it from "correcting" the URL away from /products/….
   */
  public function canonical( $redirect )
  {
    return self::isProductRequest() ? false : $redirect;
  }

  /**
   * Drop the blog-index classes WordPress adds for the same reason.
   */
  public function bodyClass( array $classes ): array
  {
    if ( ! self::isProductRequest() ) {
      return $classes;
    }

    $classes = array_diff( $classes, [ 'home', 'blog' ] );
    $classes[] = 'single-product';

    return array_values( $classes );
  }

  private function init(): void
  {
    add_action( 'init', [ $this, 'addRewrite' ] );
    add_filter( 'query_vars', [ $this, 'queryVars' ] );
    add_filter( 'template_include', [ $this, 'template' ], 99 );
    add_filter( 'redirect_canonical', [ $this, 'canonical' ] );
    add_filter( 'body_class', [ $this, 'bodyClass' ], 20 );
  }
}
