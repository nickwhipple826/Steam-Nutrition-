<?php

namespace MMM\Services;

use MMM\Traits\Singleton;

/**
 * LaunchChecklistService
 *
 * Registers a dashboard widget containing a grouped launch checklist.
 * State is stored site-wide in a single wp_options row so that progress
 * is shared across all administrators.
 *
 * ## Usage
 *
 * Boot the service in Theme::init():
 * ```php
 * LaunchChecklistService::getInstance();
 * ```
 */
class LaunchChecklistService {
  use Singleton;

  private const OPTION_KEY = 'mmm_launch_checklist';
  private const NONCE_ACTION = 'mmm_launch_checklist_save';
  private const NONCE_NAME = 'mmm_launch_checklist_nonce';
  private const AJAX_ACTION = 'mmm_save_launch_checklist';

  /**
   * Returns the full checklist definition.
   * Each group has a label and an array of items keyed by a stable slug.
   *
   * @return array<string, array{label: string, items: array<string, string>}>
   */
  private function getChecklist(): array
  {
    return [
      'seo' => [
        'label' => 'SEO &amp; Meta',
        'items' => [
          'seo_title_set' => 'SEO title set on all key pages',
          'seo_description_set' => 'Meta descriptions written (150–160 chars)',
          'canonical_urls' => 'Canonical URLs confirmed',
          'og_image_set' => 'Open Graph images uploaded (1200×630px)',
          'robots_noindex_off' => 'noindex removed from public pages',
          'sitemap_submitted' => 'Sitemap submitted to Google Search Console',
          'schema_verified' => 'Structured data validated (Rich Results Test)',
          'analytics_connected' => 'Analytics tracking verified',
        ],
      ],
      'security' => [
        'label' => 'Security',
        'items' => [
          'wp_debug_off' => 'WP_DEBUG disabled in production',
          'strong_passwords' => 'All admin accounts using strong passwords',
          'plugins_updated' => 'All plugins and themes up to date',
          'login_protected' => 'Login protected (2FA or login limiter)',
        ],
      ],
      'performance' => [
        'label' => 'Performance &amp; Assets',
        'items' => [
          'assets_built_production' => 'Production build run (npm run build)',
          'vite_manifest_present' => 'Vite manifest present and assets fingerprinted',
          'images_optimised' => 'Images compressed and served in modern formats (WebP/AVIF)',
          'images_lazy_loaded' => 'Images below the fold use lazy loading',
          'caching_configured' => 'Server-side caching configured (object cache / page cache)',
          'cache_headers' => 'Cache-Control headers set for static assets',
          'core_web_vitals' => 'Core Web Vitals passing in PageSpeed Insights',
          'fonts_optimised' => 'Web fonts preloaded or self-hosted to avoid flash of unstyled text',
        ],
      ],
      'content' => [
        'label' => 'Content &amp; CMS',
        'items' => [
          'placeholder_content' => 'No placeholder or lorem ipsum text remains',
          'acf_fields_populated' => 'All ACF fields populated on key pages',
          'flexible_content_tested' => 'All flexible content layouts tested with real content',
          'media_alt_text' => 'Media library images have alt text set',
          'links_verified' => 'All internal and external links checked (no 404s)',
          'forms_tested' => 'Forms tested end-to-end including confirmation emails',
          '404_page' => 'Custom 404 page created and styled',
          'favicon_set' => 'Favicon and app icons uploaded',
          'staging_references' => 'No hardcoded staging URLs remain in content or ACF',
        ],
      ],
      'accessibility' => [
        'label' => 'Accessibility',
        'items' => [
          'wave_scan_passed' => 'Automated scan passed (axe / WAVE)',
          'keyboard_nav' => 'Full keyboard navigation tested',
          'focus_visible' => 'Focus indicators visible throughout',
          'skip_link' => 'Skip-to-content link present',
          'images_alt_text' => 'All images have meaningful alt text',
          'colour_contrast' => 'Colour contrast meets WCAG AA (4.5:1 text)',
          'aria_labels' => 'Interactive elements have ARIA labels',
          'form_labels' => 'All form inputs have associated labels',
          'reduced_motion' => 'prefers-reduced-motion respected',
        ],
      ],
    ];
  }

  private function init(): void
  {
    add_action( 'wp_dashboard_setup', [ $this, 'registerWidget' ] );
    add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
    add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handleAjaxSave' ] );

    add_action( 'wp_dashboard_setup', [ $this, 'removeDashboardWidgets' ] );
    add_action('user_register', function (int $userId) {
      update_user_meta($userId, 'show_welcome_panel', 0);
    });
  }

  /**
   * Register the checklist dashboard widget
   * @return void
   */
  public function registerWidget(): void
  {
    wp_add_dashboard_widget(
      'mmm_launch_checklist',
      'Launch Checklist',
      [ $this, 'renderWidget' ],
    );
  }

  public function removeDashboardWidgets(): void
  {
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_php_nags', 'dashboard', 'normal' );
  }

  /**
   * Enqueues inline styles and scripts on the admin panel, index only
   * @param string $hookSuffix
   * @return void
   */
  public function enqueueAssets( string $hookSuffix ): void
  {
    if ( $hookSuffix !== 'index.php' ) {
      return;
    }

    wp_add_inline_style( 'wp-admin', $this->getInlineStyles() );
    wp_add_inline_script( 'jquery', $this->getInlineScript() );
  }

  /**
   * Renders the dashboard widget
   * @return void
   */
  public function renderWidget(): void
  {
    $saved = $this->getSavedState();
    $checklist = $this->getChecklist();
    $progress = $this->computeProgress( $checklist, $saved );

    $nonce = wp_create_nonce( self::NONCE_ACTION );

    echo '<div class="mmm-checklist" data-nonce="' . esc_attr( $nonce ) . '">';

    // Progress bar
    $pct = $progress['total'] > 0
      ? (int)(($progress['checked'] / $progress['total']) * 100)
      : 0;

    $barColor = match (true) {
      $pct === 100 => '#00a32a',
      $pct >= 60 => '#dba617',
      default => '#d63638',
    };

    echo '<div class="mmm-checklist__progress-wrap">';
    echo '<div class="mmm-checklist__progress-bar" style="--pct:' . $pct . '%; --color:' . esc_attr( $barColor ) . '">';
    echo '<span>' . $progress['checked'] . ' / ' . $progress['total'] . ' complete</span>';
    echo '</div>';
    echo '</div>';

    // Groups
    foreach ( $checklist as $groupKey => $group ) {
      $groupChecked = 0;
      foreach ( array_keys( $group['items'] ) as $itemKey ) {
        if ( !empty( $saved[$groupKey][$itemKey] ) ) {
          $groupChecked++;
        }
      }
      $groupTotal = count( $group['items'] );

      echo '<details class="mmm-checklist__group" ' . ($groupChecked < $groupTotal ? 'open' : '') . '>';
      echo '<summary class="mmm-checklist__group-summary">';
      echo '<span class="mmm-checklist__group-label">' . wp_kses_post( $group['label'] ) . '</span>';
      echo '<span class="mmm-checklist__group-count">' . $groupChecked . '/' . $groupTotal . '</span>';
      echo '</summary>';

      echo '<ul class="mmm-checklist__items">';
      foreach ( $group['items'] as $itemKey => $itemLabel ) {
        $checked = !empty( $saved[$groupKey][$itemKey] );
        $inputId = 'mmm-item-' . esc_attr( $groupKey ) . '-' . esc_attr( $itemKey );

        echo '<li class="mmm-checklist__item' . ($checked ? ' is-checked' : '') . '">';
        echo '<label for="' . $inputId . '">';
        echo '<input
          type="checkbox"
          id="' . $inputId . '"
          name="' . esc_attr( $groupKey ) . '[' . esc_attr( $itemKey ) . ']"
          data-group="' . esc_attr( $groupKey ) . '"
          data-item="' . esc_attr( $itemKey ) . '"
          ' . checked( $checked, true, false ) . '
        />';
        echo esc_html( $itemLabel );
        echo '</label>';
        echo '</li>';
      }
      echo '</ul>';
      echo '</details>';
    }

    // Save button + status message
    echo '<div class="mmm-checklist__footer">';
    echo '<button class="button button-primary mmm-checklist__save">Save progress</button>';
    echo '<span class="mmm-checklist__status" aria-live="polite"></span>';
    echo '</div>';

    echo '</div>'; // .mmm-checklist
  }

  // -------------------------------------------------------------------------
  // AJAX handler
  // -------------------------------------------------------------------------

  public function handleAjaxSave(): void
  {
    if ( !check_ajax_referer( self::NONCE_ACTION, self::NONCE_NAME, false ) ) {
      wp_send_json_error( [ 'message' => 'Invalid nonce.' ], 403 );
    }

    if ( !current_user_can( 'manage_options' ) ) {
      wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }

    $raw = $_POST['checklist'] ?? [];
    $checklist = $this->getChecklist();
    $sanitized = [];

    // Only persist known groups and items — never trust raw POST keys.
    foreach ( $checklist as $groupKey => $group ) {
      foreach ( array_keys( $group['items'] ) as $itemKey ) {
        $sanitized[$groupKey][$itemKey] = !empty( $raw[$groupKey][$itemKey] );
      }
    }

    update_option( self::OPTION_KEY, $sanitized, false );

    $progress = $this->computeProgress( $checklist, $sanitized );

    wp_send_json_success( [
      'message' => 'Progress saved.',
      'checked' => $progress['checked'],
      'total' => $progress['total'],
    ] );
  }

  // -------------------------------------------------------------------------
  // Helpers
  // -------------------------------------------------------------------------

  /**
   * Load saved state from the database.
   * @return array<string, array<string, bool>>
   */
  private function getSavedState(): array
  {
    $option = get_option( self::OPTION_KEY, [] );
    return is_array( $option ) ? $option : [];
  }

  /**
   * Count total and checked items across all groups.
   * @param array $checklist
   * @param array $saved
   * @return array{checked: int, total: int}
   */
  private function computeProgress( array $checklist, array $saved ): array
  {
    $total = 0;
    $checked = 0;

    foreach ( $checklist as $groupKey => $group ) {
      foreach ( array_keys( $group['items'] ) as $itemKey ) {
        $total++;
        if ( !empty( $saved[$groupKey][$itemKey] ) ) {
          $checked++;
        }
      }
    }

    return [ 'checked' => $checked, 'total' => $total ];
  }

  /**
   * Gets inline styles for the widget
   * @return string The inline styles
   */
  private function getInlineStyles(): string
  {
    return <<<CSS
    /* MMM Launch Checklist Widget */
    .mmm-checklist {
      font-size: 13px;
    }

    .mmm-checklist__progress-wrap {
      margin-bottom: 16px;
    }

    .mmm-checklist__progress-bar {
      position: relative;
      background: #ddd;
      border-radius: 4px;
      height: 28px;
      overflow: hidden;
    }

    .mmm-checklist__progress-bar::before {
      content: '';
      display: block;
      position: absolute;
      inset: 0 auto 0 0;
      width: var(--pct);
      background: var(--color);
      border-radius: 4px;
      transition: width 0.4s ease, background 0.4s ease;
    }

    .mmm-checklist__progress-bar span {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      height: 100%;
      padding: 0 10px;
      font-weight: 600;
      color: #1d2327;
      white-space: nowrap;
    }

    .mmm-checklist__group {
      border: 1px solid #dcdcde;
      border-radius: 4px;
      margin-bottom: 8px;
      overflow: hidden;
    }

    .mmm-checklist__group-summary {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      background: #f6f7f7;
      cursor: pointer;
      user-select: none;
      font-weight: 600;
      list-style: none;
    }

    .mmm-checklist__group-summary::-webkit-details-marker {
      display: none;
    }

    .mmm-checklist__group-summary::before {
      content: '▶';
      font-size: 10px;
      transition: transform 0.2s ease;
      margin-right: 2px;
    }

    .mmm-checklist__group[open] .mmm-checklist__group-summary::before {
      transform: rotate(90deg);
    }

    .mmm-checklist__group-label {
      flex: 1;
    }

    .mmm-checklist__group-count {
      font-size: 11px;
      color: #646970;
      font-weight: 400;
      background: #e0e0e0;
      padding: 1px 6px;
      border-radius: 10px;
    }

    .mmm-checklist__items {
      margin: 0;
      padding: 8px 12px;
      list-style: none;
    }

    .mmm-checklist__item {
      padding: 5px 0;
      border-bottom: 1px solid #f0f0f1;
      transition: opacity 0.15s ease;
    }

    .mmm-checklist__item:last-child {
      border-bottom: none;
    }

    .mmm-checklist__item label {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }

    .mmm-checklist__item input[type="checkbox"] {
      flex-shrink: 0;
      width: 16px;
      height: 16px;
      cursor: pointer;
    }

    .mmm-checklist__item.is-checked label {
      color: #646970;
      text-decoration: line-through;
    }

    .mmm-checklist__footer {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #dcdcde;
    }

    .mmm-checklist__status {
      font-size: 12px;
      color: #646970;
    }

    .mmm-checklist__status.is-success {
      color: #00a32a;
    }

    .mmm-checklist__status.is-error {
      color: #d63638;
    }
    CSS;
  }

  /**
   * Gets the inline script for the widget
   * @return string The inline script
   */
  private function getInlineScript(): string
  {
    $ajaxUrl = esc_js( admin_url( 'admin-ajax.php' ) );
    $ajaxAction = self::AJAX_ACTION;
    $nonceName = self::NONCE_NAME;

    return <<<JS
    document.addEventListener('DOMContentLoaded', function () {
      var widget = document.querySelector('.mmm-checklist');
      if (!widget) return;

      var saveBtn    = widget.querySelector('.mmm-checklist__save');
      var statusEl   = widget.querySelector('.mmm-checklist__status');
      var progressEl = widget.querySelector('.mmm-checklist__progress-bar span');
      var barEl      = widget.querySelector('.mmm-checklist__progress-bar');
      var nonce      = widget.dataset.nonce;

      // Live strikethrough on checkbox change
      widget.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
        cb.addEventListener('change', function () {
          cb.closest('.mmm-checklist__item').classList.toggle('is-checked', cb.checked);
          updateGroupCount(cb.closest('.mmm-checklist__group'));
        });
      });

      function updateGroupCount(group) {
        if (!group) return;
        var all     = group.querySelectorAll('input[type="checkbox"]');
        var checked = group.querySelectorAll('input[type="checkbox"]:checked');
        var count   = group.querySelector('.mmm-checklist__group-count');
        if (count) count.textContent = checked.length + '/' + all.length;
      }

      saveBtn.addEventListener('click', function () {
        saveBtn.disabled = true;
        statusEl.textContent = 'Saving\u2026';
        statusEl.className = 'mmm-checklist__status';

        // Collect checked state
        var data = new FormData();
        data.append('action', '{$ajaxAction}');
        data.append('{$nonceName}', nonce);

        widget.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
          if (cb.checked) {
            data.append(
              'checklist[' + cb.dataset.group + '][' + cb.dataset.item + ']',
              '1'
            );
          }
        });

        fetch('{$ajaxUrl}', { method: 'POST', body: data, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            if (json.success) {
              statusEl.textContent = '\u2713 Saved';
              statusEl.className = 'mmm-checklist__status is-success';

              // Update progress bar
              var pct   = Math.round((json.data.checked / json.data.total) * 100);
              var color = pct === 100 ? '#00a32a' : pct >= 60 ? '#dba617' : '#d63638';
              barEl.style.setProperty('--pct', pct + '%');
              barEl.style.setProperty('--color', color);
              if (progressEl) progressEl.textContent = json.data.checked + ' / ' + json.data.total + ' complete';
            } else {
              statusEl.textContent = '\u26a0 ' + (json.data.message || 'Save failed.');
              statusEl.className = 'mmm-checklist__status is-error';
            }
          })
          .catch(function () {
            statusEl.textContent = '\u26a0 Network error.';
            statusEl.className = 'mmm-checklist__status is-error';
          })
          .finally(function () {
            saveBtn.disabled = false;
          });
      });
    });
    JS;
  }
}