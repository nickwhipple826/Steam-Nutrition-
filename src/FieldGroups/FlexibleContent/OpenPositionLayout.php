<?php

namespace MMM\FieldGroups\FlexibleContent;

class OpenPositionLayout extends BaseLayout {

  public function getName(): string
  {
    return 'open-position-layout';
  }

  protected function addFields(): void
  {
   $this->fields
  ->addTab( 'content' )
  ->addText( 'heading', [
    'label' => 'Section Heading',
    'required' => true,
    'default_value' => 'Open Positions',
    'instructions' => 'Appears above the open position listings.',
  ] )
  ->addTextarea( 'description', [
    'label' => 'Section Description',
    'instructions' => 'Optional intro copy shown below the heading.',
    'rows' => 3,
  ] )
  ->addLink( 'button', [
    'label' => 'Button',
    'instructions' => 'Optional button shown below the open position listings. If left blank, no button will be shown.',
    'required' => false,
  ] );

      



    // No "count" field on purpose — the slider pulls every published
    // Open Position so it never runs out of slides to overflow with at a
    // fixed slides-per-view (see property-listing for why that matters).
  }

  protected function getLabel(): string
  {
    return 'Open Position Listing';
  }
}