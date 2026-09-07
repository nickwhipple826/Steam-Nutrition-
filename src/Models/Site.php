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

  public function nav_logo(): string
  {
    return $this->acfOption( 'nav_logo' ) ?: '';
  }

  /**
   * Falls back to the nav logo if no footer-specific logo is set.
   * @return string
   */
  public function footer_logo(): string
  {
    return $this->acfOption( 'footer_logo' ) ?: $this->nav_logo();
  }

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