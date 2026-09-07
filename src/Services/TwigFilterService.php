<?php

namespace MMM\Services;

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

    return $twig;
  }

  private function init(): void
  {
    add_filter('timber/twig', [$this, 'registerFilters']);
    add_filter('timber/twig', [$this, 'registerFunctions']);
  }
}