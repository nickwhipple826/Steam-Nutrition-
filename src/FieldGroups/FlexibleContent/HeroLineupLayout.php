<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SchemeSelectorPartial;

class HeroLineupLayout extends BaseLayout {

  public function getName(): string
  {
    return 'hero-lineup';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addImage( 'background', [
        'label' => 'Background Image',
        'required' => true,
        'return_format' => 'id',
        'instructions' => 'Full-bleed. The copy sits over the left third, so keep the subject right of centre.',
      ] )
      ->addText( 'eyebrow', [
        'label' => 'Eyebrow',
        'instructions' => 'Optional. One short line above the headline, with a seal dot before it.',
      ] )
      ->addText( 'heading', [
        'label' => 'Headline',
        'required' => true,
        'instructions' => 'Set in brass foil at display size. Two or three words carries best.',
      ] )
      ->addText( 'subheading', [
        'label' => 'Subheading',
        'instructions' => 'Optional. Small tracked line under the headline, e.g. TRAIN · DRIVE · POWER.',
      ] )
      ->addTextarea( 'lede', [
        'label' => 'Lede',
        'rows' => 3,
        'instructions' => 'Optional. Two sentences at most.',
      ] )
      ->addRepeater( 'buttons', [
        'label' => 'Buttons',
        'instructions' => 'The first is styled solid, the rest outline.',
        'min' => 0,
        'max' => 2,
        'button_label' => 'Add Button',
        'layout' => 'table',
        'collapsed' => 'button',
      ] )
      ->addLink( 'button', [ 'label' => 'Button', 'return_format' => 'array' ] )
      ->endRepeater()
      ->addRepeater( 'stats', [
        'label' => 'Stat Strip',
        'instructions' => 'Three reads best — the strip is a three-column grid and a fourth wraps awkwardly.',
        'min' => 0,
        'max' => 3,
        'button_label' => 'Add Stat',
        'layout' => 'table',
        'collapsed' => 'value',
      ] )
      ->addText( 'value', [ 'label' => 'Value', 'required' => true ] )
      ->addText( 'label', [ 'label' => 'Label', 'required' => true ] )
      ->endRepeater()
      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Japanese characters set vertically in the left gutter, e.g. 蒸気栄養. Desktop only. Leave empty to hide.',
        'default_value' => '蒸気栄養',
      ] );
  }

  protected function addGlobalFields(): void
  {
    $this->fields
      ->addTab( 'appearance' )
      ->addFields( SchemeSelectorPartial::get() )
      ->addRange( 'scrim', [
        'label' => 'Copy Scrim',
        'instructions' => 'How hard the left side of the image is darkened behind the copy. Raise it if the lede is fighting the photograph.',
        'min' => 40,
        'max' => 100,
        'step' => 5,
        'default_value' => 90,
        'append' => '%',
      ] )
      ->addTab( 'administrative' )
      ->addText( 'admin_label', [
        'label' => 'Editor Label',
        'instructions' => 'Shown only in the admin panel, to help identify this section.',
      ] )
      ->addTrueFalse( 'section_hidden', [
        'label' => 'Hide this section',
        'ui' => 1,
        'instructions' => '',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Hero — Lineup';
  }

  protected function getMax(): string
  {
    return '1';
  }
}
