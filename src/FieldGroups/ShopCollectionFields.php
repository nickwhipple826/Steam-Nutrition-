<?php

namespace MMM\FieldGroups;

class ShopCollectionFields extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'Shop Collection Settings';
  }

  protected function getLocation(): array
  {
    return [
      [ 'page_template', '==', 'templates/shop-collection.php' ],
    ];
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'shop' )
      ->addSelect( 'shopify_collection_handle', [
        'label' => 'Shopify Collection',
        'instructions' => 'Which Shopify collection powers this page.',
        'choices' => $this->getCollectionChoices(),
        'ui' => 1,
        'allow_null' => false,
      ] )
      ->addButtonGroup( 'shop_filter_group', [
        'label' => 'Filter Set',
        'instructions' => 'Which drawer filter fields to render.',
        'choices' => [
          'supplements' => 'Supplements',
          'apparel' => 'Apparel',
        ],
        'default_value' => 'supplements',
      ] )
      ->addSelect( 'shop_default_sort', [
        'label' => 'Default Sort',
        'choices' => [
          'BEST_SELLING' => 'Best Selling',
          'PRICE_LOW_HIGH' => 'Price: Low to High',
          'PRICE_HIGH_LOW' => 'Price: High to Low',
          'TITLE' => 'Alphabetical',
          'CREATED' => 'Newest',
        ],
        'default_value' => 'BEST_SELLING',
      ] )
      ->addRepeater( 'category_nav', [
        'label' => 'Category Navigation',
        'instructions' => 'Sibling category links for the persistent left-hand list (e.g. Pre-Workout, Creatine).',
        'min' => 0,
        'button_label' => 'Add Category',
        'layout' => 'table',
      ] )
        ->addText( 'label', [ 'label' => 'Label', 'required' => true ] )
        ->addLink( 'link', [ 'label' => 'Link', 'required' => true, 'return_format' => 'array' ] )
      ->endRepeater();
  }

  /**
   * TODO: swap for whatever method your Shopify product-slider layout
   * already uses to populate its collection dropdown.
   */
  private function getCollectionChoices(): array
  {
    return [];
  }
}