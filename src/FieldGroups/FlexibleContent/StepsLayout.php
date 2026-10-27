<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SectionHeadPartial;

/**
 * Steps — a numbered sequence. Two looks from one set of fields:
 *   Cards     joined panels with a kanji tile (Rewards: How It Works)
 *   Timeline  a brass line with a node per station (About: raw
 *             material to tub)
 * Numbers are generated, so reordering rows renumbers them.
 */
class StepsLayout extends BaseLayout {

  public function getName(): string
  {
    return 'steps';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addFields( SectionHeadPartial::get() )
      ->addButtonGroup( 'style', [
        'label' => 'Style',
        'choices' => [ 'cards' => 'Cards', 'timeline' => 'Timeline' ],
        'default_value' => 'cards',
      ] )
      ->addText( 'number_label', [
        'label' => 'Number Label',
        'default_value' => 'Step',
        'instructions' => 'Word before each number on the cards, e.g. Step → STEP 01. Timeline shows the number alone.',
      ] )->conditional( 'style', '==', 'cards' )
      ->addRepeater( 'steps', [
        'label' => 'Steps',
        'min' => 2,
        'max' => 6,
        'button_label' => 'Add Step',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
        ->addText( 'kanji', [
          'label' => 'Kanji',
          'maxlength' => 2,
          'wrapper' => [ 'width' => 20 ],
          'instructions' => 'One or two characters, e.g. 入.',
        ] )
        ->addText( 'title', [ 'label' => 'Title', 'required' => true, 'wrapper' => [ 'width' => 80 ] ] )
        ->addTextarea( 'body', [ 'label' => 'Body', 'rows' => 2 ] )
      ->endRepeater()

      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Steps';
  }
}
