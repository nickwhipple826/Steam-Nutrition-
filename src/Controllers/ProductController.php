<?php

namespace MMM\Controllers;

use MMM\Shopify\{ProductRoute, ShopifyCart, ShopifyProductQuery};

/**
 * A single Shopify product at /products/{handle}.
 *
 * "Add to bag" is a plain POST back to this URL. The controller adds
 * the line to the shopper's Shopify cart and redirects to /cart, so it
 * works with JavaScript off. ui/cart.ts catches the same submit, adds
 * over the REST API and opens the drawer instead.
 */
class ProductController extends BaseController
{
  public function render(): void
  {
    $handle = (string) get_query_var( ProductRoute::QUERY_VAR );
    $product = ShopifyProductQuery::getProduct( $handle );

    if ( ! $product ) {
      ( new NotFoundController() )->render();
      return;
    }

    $error = '';
    $selectedId = isset( $_GET['variant'] ) ? sanitize_text_field( wp_unslash( $_GET['variant'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['steam_add'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
      [ $error, $selectedId ] = $this->add( $product );
    }

    $variant = ShopifyProductQuery::initialVariant( $product, $selectedId );

    status_header( 200 );

    $this->renderView( 'pages/product.twig', [
      'product' => $product,
      'variant' => $variant,
      'error' => $error,
      'product_url' => home_url( '/products/' . $product['handle'] . '/' ),
      'collection_page' => $this->collectionPage( $product ),
      'max_quantity' => ShopifyProductQuery::MAX_QUANTITY,
      'page_title' => ( $product['seo_title'] ?: $product['title'] ) . ' | ' . get_bloginfo( 'name' ),
      'meta_description' => $product['seo_description'] ?: wp_trim_words( $product['description'], 30, '…' ),
      // For ui/product.ts. JSON_HEX_TAG so a "</script>" in a variant
      // title cannot close the script element it is printed into.
      'variants_json' => wp_json_encode(
        array_map( fn( $v ) => [
          'id' => $v['id'],
          'available' => $v['available'],
          'price' => $v['price'],
          'compare_at' => $v['compare_at'],
          'currency' => $v['currency'],
          'options' => $v['options'],
          'image' => $v['image'],
        ], $product['variants'] ),
        JSON_HEX_TAG | JSON_HEX_AMP
      ),
    ] );
  }

  /**
   * Handle an Add to bag submit (the no-JavaScript path). Redirects to
   * /cart on success; on failure returns the message to show and the
   * variant to keep selected, so the shopper does not have to pick again.
   *
   * @return array{0: string, 1: string}
   */
  private function add( array $product ): array
  {
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // No nonce: adding to your own bag is harmless, and a nonce would go
    // stale behind WP Engine's page cache. POSTs skip that cache.
    $options = isset( $_POST['options'] ) && is_array( $_POST['options'] )
      ? array_map( 'sanitize_text_field', wp_unslash( $_POST['options'] ) )
      : [];
    $variantId = isset( $_POST['variant'] ) ? sanitize_text_field( wp_unslash( $_POST['variant'] ) ) : '';
    $quantity = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1;
    // phpcs:enable

    $variant = ShopifyProductQuery::resolveVariant( $product, $options, $variantId );

    if ( ! $variant ) {
      return [ 'That combination is not available. Pick another option.', $variantId ];
    }

    if ( ! $variant['available'] ) {
      return [ 'That option is sold out.', $variant['id'] ];
    }

    $result = ShopifyCart::add( $variant['id'], $quantity );

    if ( $result['cart'] && ! $result['error'] ) {
      wp_safe_redirect( home_url( '/cart/' ), 303 );
      exit;
    }

    $message = current_user_can( 'manage_options' ) && $result['error']
      ? 'Could not add to bag: ' . $result['error']
      : 'We could not add that to your bag. Please try again in a moment.';

    return [ $message, $variant['id'] ];
  }

  /**
   * The Shop Collection page for one of this product's collections, for
   * the breadcrumb. Matches on the page's Shopify Collection field.
   *
   * @return array{title: string, url: string}|null
   */
  private function collectionPage( array $product ): ?array
  {
    foreach ( $product['collections'] as $collection ) {
      $ids = get_posts( [
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => 'shopify_collection_handle', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
        'meta_value' => $collection['handle'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
        'no_found_rows' => true,
      ] );

      if ( $ids ) {
        return [ 'title' => get_the_title( $ids[0] ), 'url' => get_permalink( $ids[0] ) ];
      }
    }

    return null;
  }
}
