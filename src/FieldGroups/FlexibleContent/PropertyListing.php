<?php

namespace MMM\FieldGroups\FlexibleContent;

class PropertyListing extends BaseLayout {

  public function getName(): string
  {
    return 'property-listing';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'instructions' => 'Appears above the property listings.',
      ] )
      ->addTextarea( 'description', [
        'label' => 'Section Description',
        'instructions' => 'Optional intro copy shown below the heading.',
        'rows' => 3,
      ] )
      ->addNumber( 'count', [
        'label' => 'Number of Properties',
        'instructions' => 'Automatically pulls the most recently added properties.',
        'default_value' => 3,
        'min' => 1,
        'max' => 12,
      ] )
      ->addLink( 'button', [
        'label' => 'Button',
        'instructions' => 'Optional. e.g. "View All Properties", linking to the Properties archive.',
        'return_format' => 'array',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Property Listing';
  }
}