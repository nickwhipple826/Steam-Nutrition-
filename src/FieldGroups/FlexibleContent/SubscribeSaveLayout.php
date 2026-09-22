<?php

namespace MMM\FieldGroups\FlexibleContent;

class SubscribeSaveLayout extends BaseLayout {

  public function getName(): string
  {
    return 'subscribe-save';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addTextarea( 'note', [ 'label' => 'Note', 'rows' => 3 ] )
      ->addImage( 'image', [
        'label' => 'Image',
        'return_format' => 'id',
        'instructions' => 'Rendered in a 4:5 box and cropped to fill. A tall narrow shot such as a shaker will lose its middle — use a tub.',
      ] )
      ->addText( 'seal', [
        'label' => 'Seal Characters',
        'instructions' => 'Stamped on the corner of the image, e.g. 定期. Two characters, stacked.',
      ] )
      ->addTab( 'pricing' )
      ->addNumber( 'base_price', [
        'label' => 'Base Price',
        'required' => true,
        'instructions' => 'The one-time price, before any subscription discount. Numbers only.',
        'step' => '0.01',
        'prepend' => '$',
      ] )
      ->addRepeater( 'plans', [
        'label' => 'Delivery Plans',
        'instructions' => 'Rendered as a radio group. The first row is selected on load and the price readout follows the selection.',
        'min' => 1,
        'max' => 4,
        'button_label' => 'Add Plan',
        'layout' => 'table',
        'collapsed' => 'label',
      ] )
      ->addText( 'label', [ 'label' => 'Label', 'required' => true, 'instructions' => 'e.g. Every 30 days' ] )
      ->addNumber( 'days', [ 'label' => 'Days', 'required' => true, 'default_value' => 30 ] )
      ->addNumber( 'discount', [
        'label' => 'Discount',
        'required' => true,
        'default_value' => 15,
        'min' => 0,
        'max' => 90,
        'append' => '%',
      ] )
      ->endRepeater()
      ->addRepeater( 'perks', [
        'label' => 'Perks',
        'min' => 0,
        'max' => 6,
        'button_label' => 'Add Perk',
        'layout' => 'table',
        'collapsed' => 'text',
      ] )
      ->addText( 'kanji', [ 'label' => 'Marker', 'instructions' => 'Optional, e.g. 一 二 三.' ] )
      ->addText( 'text', [ 'label' => 'Text', 'required' => true ] )
      ->endRepeater()
      ->addLink( 'link', [ 'label' => 'Button', 'return_format' => 'array' ] )
      ->addTab( 'gutter' )
      ->addText( 'rail_text', [ 'label' => 'Gutter Text', 'instructions' => 'e.g. 定期. Desktop only.' ] );
  }

  protected function getLabel(): string
  {
    return 'Subscribe & Save';
  }
}
