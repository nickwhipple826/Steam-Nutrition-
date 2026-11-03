<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SchemeSelectorPartial;

/**
 * Hero — Lineup
 *
 * A crossfading slider. Each slide carries its own background, copy and
 * stat strip; the gutter kanji and the slider controls sit outside the
 * slides and stay put while the slides change underneath them.
 *
 * One slide renders as a static hero — no controls, no autoplay.
 *
 * Content saved before this became a slider (top-level background /
 * heading / lede / buttons / stats) still renders as slide one, see the
 * fallback at the top of hero-lineup.twig. Re-entering it as a slide
 * and saving clears that path.
 */
class HeroLineupLayout extends BaseLayout {

  public function getName(): string
  {
    return 'hero-lineup';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'slides' )
      ->addRepeater( 'slides', [
        'label' => 'Slides',
        'instructions' => 'One slide renders as a static hero. The first slide\'s headline is the page H1.',
        'min' => 1,
        'max' => 5,
        'button_label' => 'Add Slide',
        'layout' => 'block',
        'collapsed' => 'heading',
      ] )
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
          'instructions' => 'Optional. Changes with the slide. Three reads best — the strip is a three-column grid and a fourth wraps awkwardly.',
          'min' => 0,
          'max' => 3,
          'button_label' => 'Add Stat',
          'layout' => 'table',
          'collapsed' => 'value',
        ] )
          ->addText( 'value', [ 'label' => 'Value', 'required' => true ] )
          ->addText( 'label', [ 'label' => 'Label', 'required' => true ] )
        ->endRepeater()
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
      ->addTab( 'slider' )
      ->addTrueFalse( 'autoplay', [
        'label' => 'Autoplay',
        'ui' => 1,
        'default_value' => 1,
        'instructions' => 'Advances on its own, pausing on hover and keyboard focus. Visitors who ask for reduced motion never get autoplay.',
      ] )
      ->addNumber( 'interval', [
        'label' => 'Seconds per Slide',
        'min' => 4,
        'max' => 20,
        'step' => 1,
        'default_value' => 7,
        'append' => 's',
      ] )
      ->conditional( 'autoplay', '==', '1' )

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
