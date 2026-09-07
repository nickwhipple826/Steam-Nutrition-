<?php

namespace MMM\FieldGroups\FlexibleContent;

class BorderedCalloutLayout extends BaseLayout {

  public function getName(): string
  {
    return 'bordered-callout';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Heading',
        'required' => true,
        'default_value' => 'Reporting Emergencies',
      ] )
      ->addTextarea( 'intro', [
        'label' => 'Intro Text',
        'instructions' => 'Optional. Appears below the heading.',
        'rows' => 2,
      ] )
      ->addText( 'list_heading', [
        'label' => 'List Heading',
        'instructions' => 'Optional. e.g. "Examples of Emergencies:".',
      ] )
      ->addRepeater( 'list_items', [
        'label' => 'List Items',
        'instructions' => 'Optional. One row per bullet point.',
        'min' => 0,
        'button_label' => 'Add Item',
        'layout' => 'table',
        'collapsed' => 'item',
      ] )
      ->addText( 'item', [ 'label' => 'Item' ] )
      ->endRepeater()
      ->addLink( 'emphasis_button', [
        'label' => 'Highlighted Button',
        'instructions' => 'Optional. Shown as a highlighted button, e.g. "Call 911 immediately in any emergency."',
        'return_format' => 'array',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Bordered Callout';
  }
}