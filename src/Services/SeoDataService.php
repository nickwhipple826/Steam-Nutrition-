<?php

namespace MMM\Services;

use Timber\Post;

class SeoDataService
{
  /**
   * Get SEO data for a given post.
   * @param Post $post
   * @return array
   */
  public function getSeoData(Post $post): array
  {
    $acfData = $post->acf();
    $acf = is_array($acfData) && isset($acfData['seo_metadata'])
      ? $acfData['seo_metadata']
      : [];

    return [
      'title' => $this->get($acf, 'title') ?: $post->title() . ' | ' . get_bloginfo('name'),
      'description' => $this->get($acf, 'description'),
      'canonical_url' => $this->get($acf, 'canonical_url') ?: $post->link(),
      'robots' => $this->getRobotsDirectives($acf),
      'og' => [
        'type' => $this->get($acf, 'og_type') ?: 'website',
        'title' => $this->get($acf, 'og_title') ?: $post->title(),
        'description' => $this->get($acf, 'og_description') ?: $this->get($acf, 'description'),
        'url' => $post->link(),
        'image' => $this->get($acf, 'og_image.url'),
      ],
      'twitter' => [
        'card' => $this->get($acf, 'twitter_card') ?: 'summary_large_image',
        'title' => $this->get($acf, 'twitter_title') ?: $post->title(),
        'description' => $this->get($acf, 'twitter_description') ?: $this->get($acf, 'description'),
        'image' => $this->get($acf, 'twitter_image.url'),
      ],
    ];
  }

  /**
   * Safely get a value from an array using dot notation.
   * @param array $data
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  private function get(array $data, string $key, mixed $default = ''): mixed
  {
    // Handle dot notation for nested arrays
    if (str_contains($key, '.')) {
      $keys = explode('.', $key);
      $value = $data;

      foreach ($keys as $k) {
        if (!is_array($value) || !isset($value[$k])) {
          return $default;
        }
        $value = $value[$k];
      }

      return $value ?: $default;
    }

    return $data[$key] ?? $default;
  }

  /**
   * Create a robots directive.
   * @param array $acf
   * @return array
   */
  private function getRobotsDirectives(array $acf): array
  {
    $robots = [];

    $robots[] = $this->get($acf, 'noindex') ? 'noindex' : 'index';
    $robots[] = $this->get($acf, 'nofollow') ? 'nofollow' : 'follow';

    if ($this->get($acf, 'noimageindex')) {
      $robots[] = 'noimageindex';
    }

    return $robots;
  }
}