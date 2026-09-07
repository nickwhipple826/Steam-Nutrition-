<?php

namespace MMM\FieldGroups\FlexibleContent;

class HighlightBoxLayout extends BaseLayout {

  public function getName(): string
  {
    return 'highlight-box';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Heading',
        'required' => true,
      ] )
      ->addWysiwyg( 'content', [
        'label' => 'Text',
        'required' => false,
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->addLink( 'button', [
        'label' => 'Button',
        'instructions' => 'Optional. e.g. "Apply for Public Housing".',
        'required' => false,
        'return_format' => 'array',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Highlight Box';
  }
}