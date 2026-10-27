<?php

namespace MMM\FieldGroups\FlexibleContent;

/**
 * Page Hero — the opener for every page that isn't the homepage.
 *
 * The header sits transparent over it (Theme::HERO_LAYOUTS), the way
 * a full-bleed photo with a breadcrumb and title sits under the nav.
 * The photo is optional: without one it is a plain iron plate, so a
 * page with no photography yet still opens cleanly.
 *
 * The right-hand panel is optional too — a framed photo (About) or the
 * member card (Rewards).
 */
class PageHeroLayout extends BaseLayout {

  public function getName(): string
  {
    return 'page-hero';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addTrueFalse( 'show_breadcrumbs', [
        'label' => 'Breadcrumbs',
        'instructions' => 'Home › parent pages › this page, built from the page tree.',
        'ui' => 1,
        'default_value' => 1,
      ] )
      ->addText( 'eyebrow', [
        'label' => 'Eyebrow',
        'instructions' => 'Optional. e.g. REWARDS · 機関室.',
      ] )
      ->addText( 'heading', [
        'label' => 'Heading',
        'required' => true,
        'instructions' => 'The page title, e.g. The Engine Room.',
      ] )
      ->addText( 'heading_accent', [
        'label' => 'Heading — Brass Line',
        'instructions' => 'Optional. Continues the title in brass, e.g. Rewards that build pressure.',
      ] )
      ->addTextarea( 'lede', [
        'label' => 'Lede',
        'rows' => 3,
        'instructions' => 'Optional. Two sentences at most.',
      ] )
      ->addRepeater( 'buttons', [
        'label' => 'Buttons',
        'instructions' => 'The first is solid, the second outline.',
        'max' => 2,
        'button_label' => 'Add Button',
        'layout' => 'table',
      ] )
      ->addLink( 'button', [ 'label' => 'Button', 'return_format' => 'array' ] )
      ->endRepeater()
      ->addText( 'rail_text', [
        'label' => 'Gutter Kanji',
        'instructions' => 'Optional. Two characters set vertically in vermilion left of the title, e.g. 報酬. Desktop only.',
        'maxlength' => 4,
      ] )

      ->addTab( 'background' )
      ->addImage( 'background', [
        'label' => 'Background Photo',
        'return_format' => 'id',
        'instructions' => 'Optional. Full-bleed behind the title and darkened from the bottom-left so the text stays readable. Leave empty for a plain plate.',
      ] )
      ->addRange( 'scrim', [
        'label' => 'Darkening',
        'min' => 30,
        'max' => 100,
        'step' => 5,
        'default_value' => 80,
        'append' => '%',
      ] )->conditional( 'background', '!=', '' )
      ->addButtonGroup( 'height', [
        'label' => 'Height',
        'choices' => [ 'standard' => 'Standard', 'tall' => 'Tall' ],
        'default_value' => 'standard',
        'instructions' => 'Tall fills most of the screen — best with a strong photo.',
      ] )

      ->addTab( 'panel' )
      ->addButtonGroup( 'aside', [
        'label' => 'Right-Hand Panel',
        'choices' => [ 'none' => 'None', 'image' => 'Framed photo', 'card' => 'Member card' ],
        'default_value' => 'none',
      ] )
      ->addImage( 'aside_image', [
        'label' => 'Framed Photo',
        'return_format' => 'id',
      ] )->conditional( 'aside', '==', 'image' )
      ->addGroup( 'card', [ 'label' => 'Member Card', 'layout' => 'block' ] )
        ->conditional( 'aside', '==', 'card' )
        ->addText( 'label', [ 'label' => 'Label', 'default_value' => 'Member card', 'wrapper' => [ 'width' => 50 ] ] )
        ->addText( 'title', [ 'label' => 'Title', 'default_value' => 'Engine Room', 'wrapper' => [ 'width' => 50 ] ] )
        ->addText( 'seal', [ 'label' => 'Seal', 'default_value' => '職人', 'maxlength' => 3, 'wrapper' => [ 'width' => 33 ] ] )
        ->addText( 'value', [ 'label' => 'Big Value', 'default_value' => '1,250 pts', 'wrapper' => [ 'width' => 33 ] ] )
        ->addRange( 'level', [ 'label' => 'Dial', 'min' => 0, 'max' => 100, 'default_value' => 60, 'append' => '%', 'wrapper' => [ 'width' => 34 ] ] )
        ->addText( 'meta', [ 'label' => 'Rank Line', 'default_value' => 'Rank · Machinist', 'wrapper' => [ 'width' => 50 ] ] )
        ->addText( 'caption', [ 'label' => 'Caption', 'default_value' => '750 pts to Chief Engineer', 'wrapper' => [ 'width' => 50 ] ] )
        ->addText( 'foot_left', [ 'label' => 'Footer Left', 'default_value' => 'No. 000 000', 'wrapper' => [ 'width' => 50 ] ] )
        ->addText( 'foot_right', [ 'label' => 'Footer Right', 'default_value' => 'Since 2026', 'wrapper' => [ 'width' => 50 ] ] )
      ->endGroup();
  }

  protected function getLabel(): string
  {
    return 'Page Hero';
  }
}
