<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SectionHeadPartial;

/**
 * FAQ — questions that open in place. Plain <details>, so it works
 * with no script and every answer is in the page for search engines.
 */
class FaqLayout extends BaseLayout {

  public function getName(): string
  {
    return 'faq';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addFields( SectionHeadPartial::get() )
      ->addRepeater( 'items', [
        'label' => 'Questions',
        'min' => 1,
        'button_label' => 'Add Question',
        'layout' => 'block',
        'collapsed' => 'question',
      ] )
        ->addText( 'question', [ 'label' => 'Question', 'required' => true ] )
        ->addWysiwyg( 'answer', [
          'label' => 'Answer',
          'required' => true,
          'toolbar' => 'basic',
          'media_upload' => 0,
          'tabs' => 'visual',
        ] )
      ->endRepeater()
      ->addTrueFalse( 'open_first', [
        'label' => 'Open the first question',
        'ui' => 1,
        'default_value' => 0,
      ] )

      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'FAQ';
  }
}
