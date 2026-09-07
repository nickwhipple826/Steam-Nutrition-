<?php

namespace MMM\FieldGroups\FlexibleContent;

class AccordionLayout extends BaseLayout {

  public function getName(): string
  {
    return 'accordion';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'instructions' => 'Appears above the accordion.',
      ] )
      ->addRepeater( 'accordion_items', [
        'label' => 'Accordion Items',
        'instructions' => 'Each item starts collapsed on the front end. Drag to reorder.',
        'button_label' => 'Add Item',
        'layout' => 'block',
        'collapsed' => 'title',
        'min' => 1,
      ] )
      ->addText( 'title', [
        'label' => 'Title',
        'required' => true,
        'instructions' => 'The clickable label that opens this item.',
      ] )
      ->addWysiwyg( 'content', [
        'label' => 'Content',
        'required' => true,
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'Accordion';
  }
}