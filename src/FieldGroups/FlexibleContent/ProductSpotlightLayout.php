<?php

namespace MMM\FieldGroups\FlexibleContent;

/**
 * Product Spotlight — one product, two columns: a framed image on one
 * side, the pitch on the other. The "DRIVE, pocket-sized." band from
 * Concept A.
 *
 * Price and availability come from Shopify when a product handle is
 * set, so the band never shows a price the store does not charge.
 */
class ProductSpotlightLayout extends BaseLayout {

  public function getName(): string
  {
    return 'product-spotlight';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addImage( 'image', [
        'label' => 'Image',
        'return_format' => 'id',
        'required' => true,
        'instructions' => 'Shown whole inside the brass frame — a landscape box shot works best.',
      ] )
      ->addButtonGroup( 'image_side', [
        'label' => 'Image Side',
        'choices' => [ 'left' => 'Left', 'right' => 'Right' ],
        'default_value' => 'left',
      ] )
      ->addText( 'eyebrow', [
        'label' => 'Eyebrow',
        'instructions' => 'Short mono label above the heading, e.g. 四 · 携帯用.',
      ] )
      ->addText( 'heading', [
        'label' => 'Heading',
        'required' => true,
        'instructions' => 'First line, e.g. DRIVE,',
      ] )
      ->addText( 'heading_accent', [
        'label' => 'Heading — Second Line',
        'instructions' => 'Optional. Set on its own line in brass, e.g. pocket-sized.',
      ] )
      ->addTextarea( 'body', [ 'label' => 'Body', 'rows' => 3 ] )
      ->addRepeater( 'features', [
        'label' => 'Features',
        'instructions' => 'Short labels in a two-column grid. Four reads best.',
        'max' => 6,
        'button_label' => 'Add Feature',
        'layout' => 'table',
      ] )
      ->addText( 'label', [ 'label' => 'Label', 'required' => true ] )
      ->endRepeater()

      ->addTab( 'product' )
      ->addText( 'product_handle', [
        'label' => 'Shopify Product Handle',
        'instructions' => 'Optional. e.g. drive-stick-packs. Pulls the live price and sold-out state, and links the button to the product page.',
      ] )
      ->addLink( 'button', [
        'label' => 'Button',
        'return_format' => 'array',
        'instructions' => 'Optional when a handle is set — the button then reads "Shop now" and goes to the product page. Fill this to override the label or link.',
      ] )
      ->addText( 'price_note', [
        'label' => 'Price Note',
        'instructions' => 'Optional. Follows the price, e.g. / 30 packs.',
      ] )

      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker, e.g. 携帯. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Product Spotlight';
  }
}