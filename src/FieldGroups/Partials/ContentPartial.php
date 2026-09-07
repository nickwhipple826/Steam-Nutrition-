<?php

namespace MMM\FieldGroups\Partials;

use StoutLogic\AcfBuilder\FieldsBuilder;

class ContentPartial extends BasePartial {
  public static function get( string $fieldName = 'content', array $condition = [] ): FieldsBuilder
  {
    $fields = new FieldsBuilder( 'section_content' );

    $fields->addGroup( $fieldName, [
      'label' => 'Content',
      'conditional_logic' => $condition,
    ] )
      ->addText( 'heading', [
        'label' => 'Heading',
        'required' => true,
        'instructions' => 'The main heading for this section.',
      ] )
      ->addWysiwyg( 'content', [
        'label' => 'Body Text',
        'required' => true,
        'toolbar' => 'basic',
        'media_upload' => false,
        'instructions' => 'Appears below the heading.',
      ] )
      ->addRepeater( 'buttons', [
        'label' => 'Buttons',
        'instructions' => 'Optional. Buttons appear in the order listed here — the first is styled as the primary action.',
        'min' => 0,
        'max' => 2,
        'button_label' => 'Add Button',
        'layout' => 'table',
        'collapsed' => 'button',
      ] )
      ->addLink( 'button', [
        'label' => 'Button',
        'required' => false,
        'return_format' => 'array',
        'instructions' => 'Set the link text and destination. Leave the row out entirely rather than adding an empty button.',
      ] )
      ->endRepeater()
      ->endGroup();

    return $fields;
  }
}