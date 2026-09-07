<?php
/** @noinspection PhpAbstractStaticMethodInspection */

namespace MMM\PostTypes;

abstract class BasePostType
{
  /**
   * Provides the slug for the custom post type.
   * @return string
   */
  abstract protected static function slug(): string;

  /**
   * Provides any additional configuration the post type needs.
   * @return array
   */
  protected static function additionalArgs(): array
  {
    return [];
  }

  /**
   * Provides the singular form of the custom post type's name
   * @return string
   */
  abstract protected static function singular(): string;

  /**
   * Provides the plural form of the custom post type's name
   * @return string
   */
  abstract protected static function plural(): string;

  /**
   * Provides the icon for the custom post type.
   * Can return a dashicon or an encoded SVG.
   * If using an SVG, ensure it has fill="black", and is returning:
   * 'data:image/svg+xml;base64,' . base64_encode($svg)
   * @return string
   */
  protected static function menuIcon(): string
  {
    return 'dashicons-admin-post';
  }

  protected static function supports(): array
  {
    return ['title', 'editor', 'thumbnail'];
  }

  /**
   * Entry point to be called by the registry
   * @return void
   */
  final public static function register(): void
  {
    register_post_type(
      static::slug(),
      static::args()
    );
  }

  /**
   * Provides arguments for post type registration
   * @return array
   */
  protected static function args(): array
  {
    return array_merge([
      'labels' => static::labels(),
      'public' => true,
      'show_in_rest' => true,
      'supports' => static::supports(),
      'menu_icon' => static::menuIcon(),
    ], static::additionalArgs());
  }

  /**
   * Produces labels for the post type, to be used in args
   * @return array
   */
  protected static function labels(): array
  {
    $singular = static::singular();
    $plural = static::plural();

    return [
      'name' => __($plural, 'athena'),
      'singular_name' => __($singular, 'athena'),
      'menu_name' => __($plural, 'athena'),
      'name_admin_bar' => __($singular, 'athena'),
      'add_new' => __('Add New', 'athena'),
      'add_new_item' => sprintf(__('Add New %s', 'athena'), $singular),
      'edit_item' => sprintf(__('Edit %s', 'athena'), $singular),
      'new_item' => sprintf(__('New %s', 'athena'), $singular),
      'view_item' => sprintf(__('View %s', 'athena'), $singular),
      'view_items' => sprintf(__('View %s', 'athena'), $plural),
      'search_items' => sprintf(__('Search %s', 'athena'), $plural),
      'not_found' => sprintf(__('No %s found', 'athena'), strtolower($plural)),
      'not_found_in_trash' => sprintf(__('No %s found in Trash', 'athena'), strtolower($plural)),
      'all_items' => sprintf(__('All %s', 'athena'), $plural),
      'archives' => sprintf(__('%s Archives', 'athena'), $singular),
      'attributes' => sprintf(__('%s Attributes', 'athena'), $singular),
      'insert_into_item' => sprintf(__('Insert into %s', 'athena'), strtolower($singular)),
      'uploaded_to_this_item' => sprintf(__('Uploaded to this %s', 'athena'), strtolower($singular)),
    ];
  }
}