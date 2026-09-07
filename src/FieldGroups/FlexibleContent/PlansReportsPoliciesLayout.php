<?php

namespace MMM\FieldGroups\FlexibleContent;

class PlansReportsPoliciesLayout extends BaseLayout {

  public function getName(): string
  {
    return 'plans-reports-policies';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'default_value' => 'Plans, Reports, and Policies',
      ] )
      ->addTextarea( 'intro', [
        'label' => 'Intro Text',
        'instructions' => 'Optional. Appears below the heading, above the cards.',
        'rows' => 2,
      ] )
      ->addRepeater( 'items', [
        'label' => 'Documents',
        'instructions' => 'One card per document. Drag to reorder.',
        'min' => 1,
        'button_label' => 'Add Document',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
      ->addText( 'title', [
        'label' => 'Title',
        'required' => true,
      ] )
      ->addTextarea( 'description', [
        'label' => 'Description',
        'instructions' => 'Optional. Short description of the document.',
        'rows' => 2,
      ] )
      ->addFile( 'file', [
        'label' => 'File',
        'instructions' => 'The document the Download button links to.',
        'required' => true,
        'return_format' => 'array',
      ] )
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'Plans, Reports, and Policies';
  }
}