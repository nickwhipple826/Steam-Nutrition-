<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SectionHeadPartial;

/**
 * Feature Grid — a grid of small panels. One layout covers three
 * sections of the mockups:
 *   Row   kanji box left, value right (Rewards: Ways to Earn)
 *   Card  kanji tile on top, then title and copy (About: What We Hold
 *         To; Rewards: Spend Your Points, with the value as the
 *         stamp on top)
 */
class FeatureGridLayout extends BaseLayout {

  public function getName(): string
  {
    return 'feature-grid';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addFields( SectionHeadPartial::get() )
      ->addButtonGroup( 'style', [
        'label' => 'Style',
        'choices' => [ 'row' => 'Row — icon left, value right', 'card' => 'Card — icon on top' ],
        'default_value' => 'card',
      ] )
      ->addButtonGroup( 'columns', [
        'label' => 'Columns',
        'choices' => [ '2' => '2', '3' => '3', '4' => '4' ],
        'default_value' => '4',
        'instructions' => 'On wide screens. Phones always show one, tablets two.',
      ] )
      ->addRepeater( 'items', [
        'label' => 'Items',
        'min' => 1,
        'max' => 12,
        'button_label' => 'Add Item',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
        ->addText( 'kanji', [
          'label' => 'Kanji',
          'maxlength' => 2,
          'wrapper' => [ 'width' => 20 ],
          'instructions' => 'Optional, e.g. 買.',
        ] )
        ->addText( 'title', [ 'label' => 'Title', 'required' => true, 'wrapper' => [ 'width' => 50 ] ] )
        ->addText( 'value', [
          'label' => 'Value',
          'wrapper' => [ 'width' => 30 ],
          'instructions' => 'Optional, e.g. 5 pts / $1 or 500 pts.',
        ] )
        ->addTextarea( 'body', [ 'label' => 'Body', 'rows' => 2 ] )
        ->addLink( 'link', [
          'label' => 'Link',
          'return_format' => 'array',
          'instructions' => 'Optional. Makes the whole panel a link.',
        ] )
      ->endRepeater()

      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Feature Grid';
  }
}
