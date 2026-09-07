<?php

namespace MMM\Setup;

use MMM\Traits\Singleton;

class OptionsPage {
  use Singleton;

  public const SLUG = 'site-settings';

  private function init(): void
  {
    add_action( 'acf/init', [ $this, 'register' ] );
  }

  /**
   * Register the Site Settings options page.
   * @return void
   */
  public function register(): void
  {
    if ( !function_exists( 'acf_add_options_page' ) ) {
      return;
    }

    acf_add_options_page( [
      'page_title' => 'Site Settings',
      'menu_title' => 'Site Settings',
      'menu_slug'  => self::SLUG,
      'capability' => 'edit_posts',
      'icon_url'   => 'dashicons-admin-generic',
      'position'   => 60,
    ] );
  }
}