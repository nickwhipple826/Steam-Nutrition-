<?php

namespace MMM\Services;

use MMM\Shopify\ShopifyCollectionQuery;
use MMM\Traits\Singleton;
use Timber\Timber;
use Twig\{Environment, TwigFilter, TwigFunction};

class TwigFilterService
{
  use Singleton;

  /**
   * Register custom Twig functions.
   * @param Environment $twig
   * @return Environment
   */
  public function registerFunctions(Environment $twig): Environment
  {
    // Lets a section partial pull a Shopify collection without a
    // controller in front of it, the same way recent_notices() works
    // for the post types. Responses are transient-cached in
    // ShopifyCollectionQuery, so calling this from two sections on one
    // page costs one request.
    $twig->addFunction(new TwigFunction('shopify_collection', function (
      ?string $handle,
      string $sort = 'BEST_SELLING',
      int $first = 8
    ): array {
      if (!$handle) {
        return ['title' => '', 'products' => [], 'filters' => []];
      }

      return ShopifyCollectionQuery::getCollectionSorted($handle, $sort, $first);
    }));

    // ACF fields on a nav menu item. Timber's MenuItem::meta() reads raw
    // post meta, which returns a row COUNT for a repeater rather than
    // the rows, so the header and footer read menu fields through ACF.
    $twig->addFunction(new TwigFunction('menu_item_field', function ($itemId, string $field) {
      if (!$itemId || !function_exists('get_field')) {
        return null;
      }

      return get_field($field, (int) $itemId);
    }));

    $twig->addFunction(new TwigFunction('recent_notices', function (int $count = 2): array {
      return Timber::get_posts([
        'post_type' => 'notices',
        'posts_per_page' => $count,
        'orderby' => 'date',
        'order' => 'DESC',
      ])->to_array();
    }));

    $twig->addFunction(new TwigFunction('all_notices', function (): array {
      return Timber::get_posts([
        'post_type' => 'notices',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
      ])->to_array();
    }));

    $twig->addFunction(new TwigFunction('recent_open_positions', function (int $count = 2): array {
      return Timber::get_posts([
        'post_type' => 'open-positions',
        'posts_per_page' => $count,
        'orderby' => 'date',
        'order' => 'DESC',
      ])->to_array();
    }));

    $twig->addFunction(new TwigFunction('all_open_positions', function (): array {
      return Timber::get_posts([
        'post_type' => 'open-positions',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
      ])->to_array();
    }));

    $twig->addFunction(new TwigFunction('recent_properties', function (int $count = 3): array {
      return Timber::get_posts([
        'post_type' => 'properties',
        'posts_per_page' => $count,
        'orderby' => 'date',
        'order' => 'DESC',
      ])->to_array();
    }));

    return $twig;
  }

  /**
   * Register custom Twig filters.
   * @param Environment $twig
   * @return mixed
   */
  public function registerFilters(Environment $twig): Environment
  {
    $twig->addFilter(new TwigFilter('tel', function (?string $phone): string {
      if (!$phone) {
        return '';
      }
      return preg_replace('/[^0-9+]/', '', $phone);
    }));

    $twig->addFilter(new TwigFilter('obfuscate_email', fn($e) => antispambot($e)));

    $twig->addFilter(new TwigFilter('slugify', function ($text) {
      $text = strtolower($text);
      $text = preg_replace('/[\s_]+/', '-', $text);
      $text = preg_replace('/[^a-z0-9\-]/', '', $text);
      $text = preg_replace('/-+/', '-', $text);
      return trim($text, '-');
    }));

    // Money, formatted from Shopify's decimal string. Kept as a filter
    // rather than done in the template so the currency symbol lives in
    // one place when a second storefront shows up.
    $twig->addFilter(new TwigFilter('money', function ($amount, string $currency = 'USD'): string {
      if ($amount === null || $amount === '') {
        return '';
      }

      $symbols = ['USD' => '$', 'CAD' => 'CA$', 'GBP' => '£', 'EUR' => '€'];
      $symbol = $symbols[$currency] ?? '';

      return $symbol . number_format((float) $amount, 2);
    }));

    return $twig;
  }

  private function init(): void
  {
    add_filter('timber/twig', [$this, 'registerFilters']);
    add_filter('timber/twig', [$this, 'registerFunctions']);
  }
}
