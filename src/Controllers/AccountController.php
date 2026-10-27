<?php

namespace MMM\Controllers;

use MMM\Shopify\{AccountQuery, CustomerAccount};

/**
 * /account/… — the customer's own screens.
 *
 *   /account              dashboard, or the sign-in landing when signed out
 *   /account/login        → Shopify sign-in
 *   /account/callback     ← back from Shopify
 *   /account/logout       ends both sessions
 *   /account/orders       full order history
 *   /account/orders/{n}   one order
 */
class AccountController extends BaseController
{
  public function __construct( private string $route )
  {
    parent::__construct();
  }

  public function render(): void
  {
    if ( ! CustomerAccount::isConfigured() ) {
      $this->landing( current_user_can( 'manage_options' )
        ? 'Customer accounts are not connected yet. Define SHOPIFY_CUSTOMER_CLIENT_ID (and SHOPIFY_CUSTOMER_CLIENT_SECRET) in wp-config.php.'
        : 'Accounts are not available right now. Please check back soon.' );
      return;
    }

    switch ( $this->route ) {
      case 'account-login':
        $this->login();
        return;
      case 'account-callback':
        $this->callback();
        return;
      case 'account-logout':
        $this->redirect( CustomerAccount::logout(), true );
        return;
    }

    // Everything below needs a signed-in customer.
    if ( ! CustomerAccount::isLoggedIn() ) {
      $this->landing();
      return;
    }

    match ( $this->route ) {
      'account-orders' => $this->orders(),
      'account-order' => $this->order(),
      default => $this->dashboard(),
    };
  }

  // ---------------------------------------------------------------------

  private function login(): void
  {
    $return = isset( $_GET['return_to'] ) ? esc_url_raw( wp_unslash( $_GET['return_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $url = CustomerAccount::loginUrl( $return );

    if ( ! $url ) {
      $this->landing( 'Sign-in is unavailable right now. Please try again in a moment.' );
      return;
    }

    $this->redirect( $url, true );
  }

  private function callback(): void
  {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    // The OAuth state parameter is the CSRF check here.
    if ( isset( $_GET['error'] ) ) {
      $this->landing( 'Sign-in was cancelled or did not complete. Please try again.' );
      return;
    }

    $result = CustomerAccount::handleCallback(
      sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ),
      sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) )
    );
    // phpcs:enable

    if ( $result['error'] ) {
      $this->landing( $result['error'] );
      return;
    }

    $this->redirect( $result['redirect'] );
  }

  private function dashboard(): void
  {
    $customer = AccountQuery::dashboard();

    if ( ! $customer ) {
      $this->landing( 'We could not load your account. Please sign in again.' );
      return;
    }

    $this->view( 'pages/account/dashboard.twig', 'Your account', [ 'customer' => $customer ] );
  }

  private function orders(): void
  {
    $after = isset( $_GET['after'] ) ? sanitize_text_field( wp_unslash( $_GET['after'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page = AccountQuery::orders( $after );

    if ( null === $page ) {
      $this->landing( 'We could not load your orders. Please sign in again.' );
      return;
    }

    $this->view( 'pages/account/orders.twig', 'Order history', [
      'page' => $page,
      'next_url' => $page['has_next'] ? add_query_arg( 'after', rawurlencode( (string) $page['cursor'] ), home_url( '/account/orders/' ) ) : '',
      'is_first_page' => ! $after,
    ] );
  }

  private function order(): void
  {
    $order = AccountQuery::order( \MMM\Shopify\StoreRoutes::param() );

    if ( ! $order ) {
      ( new NotFoundController() )->render();
      return;
    }

    $this->view( 'pages/account/order.twig', 'Order ' . $order['name'], [ 'order' => $order ] );
  }

  // ---------------------------------------------------------------------

  /**
   * Signed out (or something went wrong): the sign-in landing.
   */
  private function landing( string $message = '' ): void
  {
    $this->view( 'pages/account/login.twig', 'Sign in', [
      'message' => $message,
      'login_url' => home_url( '/account/login/' ),
    ] );
  }

  private function view( string $template, string $title, array $data ): void
  {
    status_header( 200 );

    $this->renderView( $template, array_merge( [
      'page_title' => $title . ' | ' . get_bloginfo( 'name' ),
      'account_nav' => $this->nav(),
    ], $data ) );
  }

  private function nav(): array
  {
    $current = $this->route;

    return [
      [ 'label' => 'Overview', 'url' => home_url( '/account/' ), 'current' => 'account' === $current ],
      [ 'label' => 'Orders', 'url' => home_url( '/account/orders/' ), 'current' => str_starts_with( $current, 'account-order' ) ],
      [ 'label' => 'Rewards', 'url' => home_url( '/rewards/' ), 'current' => false ],
      [ 'label' => 'Sign out', 'url' => home_url( '/account/logout/' ), 'current' => false ],
    ];
  }

  /**
   * @param bool $external Shopify's sign-in and sign-out pages live on
   *                       another host, so they skip wp_safe_redirect.
   */
  private function redirect( string $url, bool $external = false ): void
  {
    if ( $external ) {
      wp_redirect( $url, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
    } else {
      wp_safe_redirect( $url, 302 );
    }

    exit;
  }
}
