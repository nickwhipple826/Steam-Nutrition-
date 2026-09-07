<?php

namespace MMM\FieldGroups\Partials;

use MMM\FieldGroups\Partials\BasePartial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class SchemeSelectorPartial extends BasePartial {
  public static function get( string $fieldName = 'color', array $condition = [] ): FieldsBuilder
  {
    $fields = new FieldsBuilder( 'section_content' );

    $fields->addRadio( $fieldName, [
      'label' => 'Color Scheme',
      'layout' => 'horizontal',
      'choices' => [
        'white' => 'White',
        'navy' => 'Navy',
        'maroon' => 'Maroon',
        'gold' => 'Gold',
      ],
      'default_value' => 'white',
      'conditional_logic' => $condition,
    ] );

    return $fields;
  }
}