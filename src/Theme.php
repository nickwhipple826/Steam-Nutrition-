<?php

namespace MMM;

use MMM\FieldGroups\{MenuItemFields,
  PageContent,
  SeoFields,
  SiteSettingsFieldGroup,
  PropertyFields,
  ShopCollectionFields};
use MMM\PostTypes\{DocumentPostType, NoticePostType, OpenPositionPostType, PropertyPostType};
use MMM\Models\Post;
use MMM\Models\Site;
use MMM\Services\{FieldGroupRegistryService,
  ImageOptimizationService,
  LaunchChecklistService,
  PostTypeRegistryService,
  TwigFilterService,
  ViteService};
use MMM\Setup\OptionsPage;
use MMM\Setup\Security;
use MMM\Traits\Singleton;
use Timber\Timber;

class Theme
{
  use Singleton;

  /**
   * Google Fonts request for the three brand faces. Weights are trimmed
   * to what the stylesheet actually uses — every extra weight is another
   * file the first paint waits on.
   */
  private const FONTS_URL = 'https://fonts.googleapis.com/css2'
    . '?family=Rye'
    . '&family=Shippori+Mincho+B1:wght@600;700;800'
    . '&family=Zen+Kaku+Gothic+New:wght@400;500;700'
    . '&display=swap';

  /**
   * Flexible content layouts that open a page under the fixed header.
   * A page whose first visible component is one of these gets the
   * has-hero body class and no top padding on <main>.
   */
  private const HERO_LAYOUTS = [ 'hero-lineup' ];

  private Security $security;
  private TwigFilterService $twigFilterService;

  /**
   * Initialize key theme supports and nav menus.
   * @return void
   */
  public function setup(): void
  {
    add_theme_support( 'post-thumbnails' );

    register_nav_menus( [
      'primary' => __( 'Primary Menu' ),
      'footer' => __( 'Footer Menu' ),
      'legal' => __( 'Legal Menu' ),
    ] );
  }

  /**
   * Add models to the Timber classmap.
   * For use in the `timber/post/classmap` hook.
   * @param array $classmap
   * @return array
   */
  public function classmap( array $classmap ): array
  {
    $classmap['post'] = Post::class;
    $classmap['page'] = Post::class;
    $classmap['properties'] = Post::class;

    return $classmap;
  }

  /**
   * Add site data to Timber context.
   * @param array $context
   * @return array
   */
  public function addToContext( array $context ): array
  {
    $footerMenu = Timber::get_menu( 'footer' );

    $context['site'] = new Site();
    $context['menu'] = Timber::get_menu( 'primary' );
    $context['footer_menu'] = $footerMenu;
    $context['legal_menu'] = Timber::get_menu( 'legal' );

    // Still read by anything inherited that expects the flat list.
    $context['footer_links'] = $footerMenu?->items ?? [];

    return $context;
  }

  /**
   * Adds has-hero when the page opens on a full-bleed hero, so the fixed
   * header can sit over it. Every other page gets top padding on <main>
   * instead (see layout/_header.scss).
   * @param array $classes
   * @return array
   */
  public function bodyClass( array $classes ): array
  {
    if ( !is_singular() || !function_exists( 'get_field' ) ) {
      return $classes;
    }

    $id = get_queried_object_id();

    // First component that is not hidden.
    foreach ( get_field( 'components', $id ) ?: [] as $component ) {
      if ( !empty( $component['section_hidden'] ) ) {
        continue;
      }

      if ( in_array( $component['acf_fc_layout'] ?? '', self::HERO_LAYOUTS, true ) ) {
        $classes[] = 'has-hero';
      }

      break;
    }

    return $classes;
  }

  public function enqueueFonts(): void
  {
    wp_enqueue_style( 'mmm-fonts', self::FONTS_URL, [], null );
  }

  /**
   * Preconnect to both font hosts. fonts.gstatic.com serves the files
   * cross-origin, so it needs the crossorigin flag or the early
   * connection is thrown away and opened again.
   * @param array $urls
   * @param string $relation
   * @return array
   */
  public function fontResourceHints( array $urls, string $relation ): array
  {
    if ( 'preconnect' === $relation ) {
      $urls[] = 'https://fonts.googleapis.com';
      $urls[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' ];
    }

    return $urls;
  }

  private function init(): void
  {
    // Set Timber directory
    Timber::$dirname = [ 'views' ];

    // Enqueue assets
    $this->enqueue();

    // Add WordPress security
    Security::getInstance();

    // Register the Site Settings options page
    OptionsPage::getInstance();

    // Add extra Twig filters
    TwigFilterService::getInstance();

    // Add launch checklist
    LaunchChecklistService::getInstance();

    // Handle image optimization
    ImageOptimizationService::getInstance();

    // Register hooks
    add_action( 'after_setup_theme', [ $this, 'setup' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFonts' ] );
    add_filter( 'wp_resource_hints', [ $this, 'fontResourceHints' ], 10, 2 );
    add_filter( 'body_class', [ $this, 'bodyClass' ] );
    add_filter( 'use_block_editor_for_post_type', '__return_false' );

    // Always enqueue Font Awesome's frontend assets. The ACF Font
    // Awesome plugin normally auto-detects field usage via the
    // acf/load_field filter, but that doesn't reliably fire when
    // Timber reads the field, so icons could silently fail to render.
    add_filter( 'ACFFA_always_enqueue_fa', '__return_true' );

    // Register Timber necessities
    add_filter( 'timber/context', [ $this, 'addToContext' ] );
    add_filter( 'timber/post/classmap', [ $this, 'classmap' ] );

    $this->registerPostTypes();
    $this->registerFieldGroups();
  }

  /**
   * Enqueue assets using the ViteService.
   * @return void
   */
  function enqueue(): void
  {
    $vite = ViteService::getInstance();
    $vite->enqueue( 'mmm-main', 'main' );
  }

  /**
   * Register ACF field groups using the FieldGroupRegistryService.
   * @return void
   */
  private function registerFieldGroups(): void
  {
    $fieldsRegistry = FieldGroupRegistryService::getInstance();

    // Add field groups here
    $fieldsRegistry->register( SeoFields::class );
    $fieldsRegistry->register( PageContent::class );
    $fieldsRegistry->register( SiteSettingsFieldGroup::class );
    $fieldsRegistry->register( MenuItemFields::class );
    $fieldsRegistry->register( PropertyFields::class );
    $fieldsRegistry->register( ShopCollectionFields::class );

    $fieldsRegistry->init();
  }

  /**
   * Register post types using the PostTypeRegistryService.
   * @return void
   */
  private function registerPostTypes(): void
  {
    $postTypeRegistry = PostTypeRegistryService::getInstance();

    $postTypeRegistry->register(PropertyPostType::class);
    $postTypeRegistry->register(NoticePostType::class);
    $postTypeRegistry->register(DocumentPostType::class);
    $postTypeRegistry->register(OpenPositionPostType::class);
  }
}
