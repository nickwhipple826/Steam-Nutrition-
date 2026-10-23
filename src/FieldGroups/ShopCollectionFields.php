<?php

namespace MMM\FieldGroups;

use MMM\Shopify\ShopifyCollectionQuery;

/**
 * Settings for a page using the Shop Collection template —
 * Supplements, Apparel, Merch.
 */
class ShopCollectionFields extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'Shop Collection Settings';
  }

  protected function getLocation(): array
  {
    // The template file is templates/shop-collections.php — plural.
    // This read shop-collection.php before, so the rule never matched
    // and none of these fields appeared on the page.
    return [
      [ 'page_template', '==', 'templates/shop-collections.php' ],
    ];
  }

  protected function addFields(): void
  {
    $this->fields->addTab( 'collection' );

    $this->addHandleField();

    $this->fields
      ->addSelect( 'shop_default_sort', [
        'label' => 'Default Sort',
        'instructions' => 'What the page opens on. A visitor can change it, and their choice ends up in the URL.',
        'choices' => array_map(
          fn( array $sort ) => $sort['label'],
          ShopifyCollectionQuery::SORT_MAP
        ),
        'default_value' => 'BEST_SELLING',
      ] )
      ->addTab( 'header' )
      ->addText( 'shop_eyebrow', [
        'label' => 'Eyebrow',
        'instructions' => 'Optional. One short line above the title, with a seal dot before it — e.g. "Subscribe & save 20%".',
      ] )
      ->addText( 'shop_heading', [
        'label' => 'Heading',
        'instructions' => 'Leave empty to use the page title.',
      ] )
      ->addTextarea( 'shop_intro', [
        'label' => 'Intro',
        'rows' => 3,
        'instructions' => 'Optional. Sits under the heading. Leave empty to use the collection description from Shopify, if it has one.',
      ] )
      ->addText( 'shop_rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker in the left gutter, e.g. 品目 for supplements, 衣料 for apparel, 道具 for merch. Desktop only.',
      ] )
      ->addTab( 'categories' )
      ->addRepeater( 'category_nav', [
        'label' => 'Category Row',
        'instructions' => 'The row of pills above the grid, as on the reference site. These are links to sibling pages or filtered URLs — they are not Shopify facets, which build themselves from the products.',
        'min' => 0,
        'button_label' => 'Add Category',
        'layout' => 'table',
        'collapsed' => 'label',
      ] )
        ->addText( 'label', [ 'label' => 'Label', 'required' => true ] )
        ->addLink( 'link', [ 'label' => 'Link', 'required' => true, 'return_format' => 'array' ] )
      ->endRepeater();
  }

  /**
   * A dropdown once Shopify answers, a plain text box until then.
   *
   * The choices come from a live API call, so before the store is
   * connected the select would be empty and the page impossible to
   * configure. The field name is the same either way, so a handle
   * typed in now is still there when the dropdown appears.
   */
  private function addHandleField(): void
  {
    $choices = ShopifyCollectionQuery::collectionChoices();

    if ( $choices ) {
      $this->fields->addSelect( 'shopify_collection_handle', [
        'label' => 'Shopify Collection',
        'instructions' => 'Which Shopify collection fills this page. Pulled live from the store and cached for an hour.',
        'choices' => $choices,
        'ui' => 1,
        'allow_null' => false,
      ] );

      return;
    }

    $this->fields->addText( 'shopify_collection_handle', [
      'label' => 'Shopify Collection Handle',
      'instructions' => 'Shopify is not connected yet, so this is a free text box — type the handle you intend to use, e.g. supplements, apparel, merch. Once SHOPIFY_STORE_DOMAIN and SHOPIFY_STOREFRONT_TOKEN are in wp-config.php this becomes a dropdown of the real collections and whatever you typed stays selected.',
      'placeholder' => 'supplements',
    ] );
  }
}