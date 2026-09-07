<?php

namespace MMM\FieldGroups\FlexibleContent;

class NoticeHighlightsLayout extends BaseLayout {

  public function getName(): string
  {
    return 'notice-highlights';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'default_value' => 'Public Notices',
      ] )
      ->addNumber( 'count', [
        'label' => 'Number of Notices to Show',
        'default_value' => 2,
        'min' => 1,
        'max' => 6,
        'instructions' => 'Pulls in the most recently published Notices automatically.',
      ] )
      ->addLink( 'button', [
        'label' => 'Button',
        'instructions' => 'e.g. "View All Notices", linking to the Notices archive.',
        'return_format' => 'array',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Notice Highlights';
  }
}