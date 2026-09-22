<?php

namespace MMM\Models;

use Timber\Site as TimberSite;

class Site extends TimberSite
{
  /**
   * Site Settings fields live on the ACF options page, so they have to be
   * read with `get_field(..., 'option')` — Timber's built-in `option()`/`meta()`
   * just calls WordPress's plain `get_option()`, which bypasses ACF entirely
   * (returns raw attachment IDs for images, raw row counts for repeaters, etc).
   * @param string $selector
   * @return mixed
   */
  private function acfOption( string $selector ): mixed
  {
    if ( !function_exists( 'get_field' ) ) {
      return null;
    }

    return get_field( $selector, 'option' );
  }

  // ---- Branding -------------------------------------------------------

  public function nav_logo(): string
  {
    return $this->acfOption( 'nav_logo' ) ?: '';
  }

  /**
   * Falls back to the nav logo if no footer-specific logo is set.
   */
  public function footer_logo(): string
  {
    return $this->acfOption( 'footer_logo' ) ?: $this->nav_logo();
  }

  public function wordmark(): string
  {
    return $this->acfOption( 'wordmark' ) ?: 'Steam';
  }

  public function wordmark_sub(): string
  {
    return $this->acfOption( 'wordmark_sub' ) ?? 'NUTRITION';
  }

  // ---- Header ---------------------------------------------------------

  /**
   * @return string[]
   */
  public function ticker_items(): array
  {
    $rows = $this->acfOption( 'ticker_items' ) ?: [];

    return array_values( array_filter( array_map(
      fn( array $row ) => trim( $row['line'] ?? '' ),
      $rows
    ) ) );
  }

  public function search_url(): string
  {
    return $this->acfOption( 'search_url' ) ?: home_url( '/?s=' );
  }

  public function account_url(): string
  {
    return $this->acfOption( 'account_url' ) ?: '#';
  }

  public function cart_url(): string
  {
    return $this->acfOption( 'cart_url' ) ?: '#';
  }

  // ---- Footer ---------------------------------------------------------

  public function footer_tagline(): string
  {
    return $this->acfOption( 'footer_tagline' ) ?: '';
  }

  /**
   * @return array<int, array{network: string, url: string}>
   */
  public function socials(): array
  {
    $rows = $this->acfOption( 'socials' ) ?: [];

    return array_values( array_filter(
      $rows,
      fn( array $row ) => !empty( $row['url'] )
    ) );
  }

  public function locale_label(): string
  {
    return $this->acfOption( 'locale_label' ) ?: '';
  }

  public function disclaimer(): string
  {
    return $this->acfOption( 'disclaimer' ) ?: '';
  }

  // ---- Inherited from Woonsocket -------------------------------------

  public function address(): string
  {
    return $this->acfOption( 'address' ) ?: '';
  }

  public function address_url(): string
  {
    return $this->acfOption( 'address_url' ) ?: '';
  }

  public function phone(): string
  {
    return $this->acfOption( 'phone' ) ?: '';
  }

  /**
   * Flatten the `office_hours` repeater into a plain array of strings,
   * so templates can do `{% for line in site.office_hours_lines %}`.
   * @return array
   */
  public function office_hours_lines(): array
  {
    $rows = $this->acfOption( 'office_hours' ) ?: [];

    return array_map(
      fn( array $row ) => $row['line'] ?? '',
      $rows
    );
  }
}
