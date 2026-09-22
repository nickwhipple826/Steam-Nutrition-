<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\Shopify\ShopifyCollectionQuery;

class ProductRailLayout extends BaseLayout {

  public function getName(): string
  {
    return 'product-rail';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addTextarea( 'note', [
        'label' => 'Note',
        'rows' => 2,
        'instructions' => 'Optional. Sits under the heading, opposite the link.',
      ] )
      ->addLink( 'link', [
        'label' => 'Section Link',
        'return_format' => 'array',
        'instructions' => 'Optional. Rendered as an outline button on the right of the heading row.',
      ] )
      ->addTab( 'products' )
      ->addSelect( 'collection', [
        'label' => 'Shopify Collection',
        'instructions' => 'Handles are pulled live from Shopify and cached for an hour.',
        'choices' => ShopifyCollectionQuery::collectionChoices(),
        'ui' => 1,
        'allow_null' => false,
      ] )
      ->addSelect( 'sort', [
        'label' => 'Sort',
        'choices' => [
          'BEST_SELLING' => 'Best Selling',
          'CREATED' => 'Newest',
          'PRICE_LOW_HIGH' => 'Price: Low to High',
          'PRICE_HIGH_LOW' => 'Price: High to Low',
          'TITLE' => 'Alphabetical',
        ],
        'default_value' => 'BEST_SELLING',
      ] )
      ->addNumber( 'limit', [
        'label' => 'Number of Products',
        'default_value' => 8,
        'min' => 2,
        'max' => 24,
        'instructions' => 'The rail scrolls horizontally, so this is how far it scrolls, not how much fits on screen.',
      ] )
      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker in the left gutter, e.g. 品目. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Product Rail';
  }
}
