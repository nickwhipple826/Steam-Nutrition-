<?php

namespace MMM\Services;

use MMM\Traits\Singleton;

/**
 * ViteService - Manages Vite-built assets for WordPress themes.
 *
 * This service reads Vite's manifest.json file and enqueues JavaScript and CSS assets
 * with proper cache busting and module type attributes.
 *
 * ## Expected Manifest Structure
 *
 * Vite generates a manifest.json file with this structure:
 * ```json
 * {
 *   "assets/src/js/main.ts": {
 *     "file": "main.min.js",
 *     "name": "main",
 *     "src": "assets/src/js/main.ts",
 *     "isEntry": true,
 *     "css": ["main.min.css"]
 *   }
 * }
 * ```
 *
 * The service uses the "name" field (derived from input names in vite.config.js)
 * to look up assets. For example:
 *
 * ```php
 * // vite.config.js has: input: { main: 'assets/src/js/main.ts' }
 * // Call with the input name:
 * $vite->enqueue('my-handle', 'main');
 * ```
 *
 * @package MMM\Services
 */
class ViteService
{
  use Singleton;

  private string $distPath;
  private string $distUri;
  private ?array $manifest = null;
  private bool $manifestLoaded = false;
  private ?string $buildVersion = null;

  public function enqueue(string $handle, string $entry, array $dependencies = []): void
  {
    if (!$this->manifestLoaded) {
      error_log('Cannot enqueue assets: Vite manifest not loaded');
      return;
    }

    $jsUrl = $this->asset($entry);
    $cssUrls = $this->css($entry);

    if (!$jsUrl) {
      return;
    }

    add_action('wp_enqueue_scripts', function () use ($handle, $dependencies, $jsUrl, $cssUrls) {
      wp_enqueue_script(
        $handle,
        $jsUrl,
        $dependencies,
        $this->buildVersion,
        ['in_footer' => true, 'strategy' => 'defer']
      );
      wp_script_add_data($handle, 'type', 'module');

      foreach ($cssUrls as $index => $cssUrl) {
        wp_enqueue_style($handle . '-css-' . $index, $cssUrl, [], $this->buildVersion);
      }
    });
  }

  /**
   * Get the URL for a Vite asset by its entry name.
   *
   * Entry name corresponds to the "name" field in Vite's manifest.json,
   * which is derived from the input name in vite.config.js.
   *
   * Example: enqueue('handle', 'main') looks for manifest entry with "name": "main"
   *
   * @param string $entry The entry name (e.g., 'main')
   * @return string|null The full URL to the asset, or null if not found
   */
  private function asset(string $entry): ?string
  {
    $manifestEntry = $this->findManifestEntry($entry);

    if (!$manifestEntry) {
      error_log('Vite asset not found in manifest: ' . $entry);
      return null;
    }

    return $this->distUri . '/' . $manifestEntry['file'];
  }

  /**
   * Find a manifest entry by its name.
   *
   * @param string $name The entry name from vite.config.js input
   * @return array|null The manifest entry or null if not found
   */
  private function findManifestEntry(string $name): ?array
  {
    if (!$this->manifest) {
      return null;
    }

    foreach ($this->manifest as $entry) {
      if (isset($entry['name']) && $entry['name'] === $name) {
        return $entry;
      }
    }

    return null;
  }

  /**
   * Get CSS URLs associated with a Vite entry.
   *
   * @param string $entry The entry name (e.g., 'main')
   * @return array Array of CSS file URLs
   */
  private function css(string $entry): array
  {
    $manifestEntry = $this->findManifestEntry($entry);

    if (!$manifestEntry || !isset($manifestEntry['css'])) {
      return [];
    }

    $cssFiles = [];
    foreach ($manifestEntry['css'] as $cssFile) {
      $cssFiles[] = $this->distUri . '/' . $cssFile;
    }

    return $cssFiles;
  }

  private function init(): void
  {
    $this->distPath = get_template_directory() . '/assets/dist';
    $this->distUri = get_template_directory_uri() . '/assets/dist';
    $this->loadManifest();
  }

  private function loadManifest(): void
  {
    $manifestPath = $this->distPath . '/.vite/manifest.json';

    if (!file_exists($manifestPath)) {
      add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>'
          . 'Vite manifest not found. Run <code>npm run build</code>.'
          . '</p></div>';
      });
      return;
    }

    $this->buildVersion = (string)filemtime($manifestPath);

    $manifestContent = file_get_contents($manifestPath);
    $this->manifest = json_decode($manifestContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('Failed to parse Vite manifest: ' . json_last_error_msg());
      return;
    }

    $this->manifestLoaded = true;
  }
}