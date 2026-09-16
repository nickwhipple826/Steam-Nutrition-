<?php

namespace MMM;

use MMM\FieldGroups\{HeroFieldGroup, PageContent, SeoFields, SiteSettingsFieldGroup, PropertyFields, ShopCollectionFields};
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
    $context['site'] = new Site();
    $context['menu'] = Timber::get_menu( 'primary' );
    $context['top_bar_menu'] = Timber::get_menu( 'top_bar' );
    $context['footer_links'] = Timber::get_menu( 'footer' )?->items ?? [];

    return $context;
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
    $fieldsRegistry->register( HeroFieldGroup::class );
    $fieldsRegistry->register( PageContent::class );
    $fieldsRegistry->register( SiteSettingsFieldGroup::class );
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