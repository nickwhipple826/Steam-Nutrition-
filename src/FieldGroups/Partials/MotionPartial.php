<?php

namespace MMM\FieldGroups\Partials;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Per-section override for the site-wide scroll reveal.
 *
 * Off is the normal state: every section already fades up as it comes
 * into view without anyone setting anything. This tab exists for the
 * two or three places where that default is wrong — a band that should
 * slide in from the side, a grid whose cards should arrive one at a
 * time, or a section that should not move at all.
 *
 * Read by views/pages/page.twig, which writes the values onto the
 * [data-block] wrapper for assets/src/js/ui/reveal.ts to pick up.
 */
class MotionPartial extends BasePartial {

  public static function get( string $fieldName = 'motion', array $condition = [] ): FieldsBuilder
  {
    $fields = new FieldsBuilder( 'motion_settings' );

    $fields->addGroup( $fieldName, [
      'label' => 'Motion',
      'conditional_logic' => $condition,
    ] )
      ->addTrueFalse( 'enable_animation', [
        'label' => 'Override the default',
        'instructions' => 'Off is a soft fade up, which is what almost every section wants. Turn this on only to pick something else — or None.',
        'ui' => 1,
      ] )
      ->addSelect( 'animation_type', [
        'label' => 'Effect',
        'instructions' => 'Stagger leaves the section still and brings its cards in one after another; it only does anything in a section built from a list, like the product rail or a grid.',
        'choices' => [
          'fade-up' => 'Fade up (the default)',
          'fade' => 'Fade only',
          'slide-up' => 'Slide up',
          'slide-left' => 'Slide in from the right',
          'slide-right' => 'Slide in from the left',
          'scale' => 'Scale up',
          'stagger' => 'Stagger the cards',
          'none' => 'None',
        ],
        'default_value' => 'fade-up',
      ] )
      ->conditional( 'enable_animation', '==', 1 )
      ->addNumber( 'delay', [
        'label' => 'Delay',
        'instructions' => 'Milliseconds to wait after the section comes into view. Leave at 0 unless you are deliberately offsetting it against the section above.',
        'default_value' => 0,
        'min' => 0,
        'max' => 2000,
        'step' => 50,
        'append' => 'ms',
      ] )
      ->conditional( 'enable_animation', '==', 1 )
      ->addNumber( 'duration', [
        'label' => 'Duration',
        'instructions' => 'Milliseconds the move takes. Below about 300 it reads as a flicker; above about 1200 the visitor is waiting on it.',
        'default_value' => 800,
        'min' => 100,
        'max' => 2000,
        'step' => 50,
        'append' => 'ms',
      ] )
      ->conditional( 'enable_animation', '==', 1 )
      ->endGroup();

    return $fields;
  }
}