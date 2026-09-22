<?php

namespace MMM\FieldGroups\Partials;

use StoutLogic\AcfBuilder\FieldsBuilder;

class SchemeSelectorPartial extends BasePartial {
  public static function get( string $fieldName = 'color', array $condition = [] ): FieldsBuilder
  {
    $fields = new FieldsBuilder( 'section_content' );

    $fields->addRadio( $fieldName, [
      'label' => 'Color Scheme',
      'layout' => 'horizontal',
      'instructions' => 'Alternate Iron and Soot down the page so sections read as separate plates. Brass and Parchment are for occasional bands, not runs.',
      'choices' => [
        'iron' => 'Iron',
        'soot' => 'Soot',
        'brass' => 'Brass',
        'parchment' => 'Parchment',
      ],
      'default_value' => 'iron',
      'conditional_logic' => $condition,
    ] );

    return $fields;
  }
}
