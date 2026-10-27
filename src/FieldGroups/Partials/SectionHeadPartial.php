<?php

namespace MMM\FieldGroups\Partials;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * The heading row most sections open with: eyebrow, heading, a short
 * note under it, and an optional link on the right. Rendered by
 * components/section-head.twig.
 */
class SectionHeadPartial extends BasePartial {
  public static function get( string $fieldName = 'head', array $condition = [] ): FieldsBuilder
  {
    $fields = new FieldsBuilder( 'section_head' );

    $fields
      ->addText( 'eyebrow', [
        'label' => 'Eyebrow',
        'instructions' => 'Short mono label above the heading, e.g. 壱 · 仕組み.',
      ] )
      ->addText( 'heading', [
        'label' => 'Heading',
        'required' => true,
      ] )
      ->addTextarea( 'note', [
        'label' => 'Note',
        'rows' => 2,
        'instructions' => 'Optional. One line under the heading.',
      ] )
      ->addText( 'aside_text', [
        'label' => 'Right-Side Label',
        'instructions' => 'Optional. Small mono text on the right of the heading row, e.g. POINT VALUES SET IN SMILE.',
      ] )
      ->addLink( 'aside_link', [
        'label' => 'Right-Side Link',
        'return_format' => 'array',
        'instructions' => 'Optional. Turns the right-side label into a link, e.g. Follow on Instagram →.',
      ] );

    return $fields;
  }
}
