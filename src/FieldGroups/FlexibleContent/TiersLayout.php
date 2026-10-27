<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SectionHeadPartial;

/**
 * Tiers — rank cards, lowest to highest (Rewards: Climb the Ranks).
 * Each card's dial reads a little higher than the last, worked out
 * from its position, so adding a rank needs no extra setting.
 */
class TiersLayout extends BaseLayout {

  public function getName(): string
  {
    return 'tiers';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addFields( SectionHeadPartial::get() )
      ->addRepeater( 'tiers', [
        'label' => 'Tiers',
        'instructions' => 'Lowest first.',
        'min' => 2,
        'max' => 4,
        'button_label' => 'Add Tier',
        'layout' => 'block',
        'collapsed' => 'name',
      ] )
        ->addText( 'kanji', [
          'label' => 'Kanji',
          'maxlength' => 3,
          'wrapper' => [ 'width' => 20 ],
          'instructions' => 'e.g. 職人.',
        ] )
        ->addText( 'name', [ 'label' => 'Name', 'required' => true, 'wrapper' => [ 'width' => 40 ] ] )
        ->addText( 'range', [
          'label' => 'Range',
          'wrapper' => [ 'width' => 40 ],
          'instructions' => 'e.g. 500 – 1,499 pts / year.',
        ] )
        ->addTextarea( 'perks', [
          'label' => 'Perks',
          'rows' => 4,
          'new_lines' => '',
          'instructions' => 'One per line.',
        ] )
        ->addText( 'flag', [
          'label' => 'Flag',
          'instructions' => 'Optional stamp on the corner, e.g. Top rank.',
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
    return 'Tiers';
  }
}
