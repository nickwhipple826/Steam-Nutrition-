<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SchemeSelectorPartial;

class StepCardsLayout extends BaseLayout {

  public function getName(): string
  {
    return 'step-cards';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'instructions' => 'Appears above the steps. Keep it short — a few words works best.',
      ] )
      ->addWysiwyg( 'intro', [
        'label' => 'Intro Text',
        'instructions' => 'Optional. A short paragraph between the heading and the first step.',
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->addRepeater( 'steps', [
        'label' => 'Steps',
        'instructions' => 'Steps are numbered automatically in the order listed here. Drag to reorder.',
        'min' => 1,
        'button_label' => 'Add Step',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
      ->addText( 'title', [
        'label' => 'Step Title',
        'required' => true,
        'instructions' => 'Shown as the label for this step when collapsed.',
      ] )
      ->addText( 'subtitle', [
        'label' => 'Subtitle',
        'instructions' => 'Optional. Displays under the step title.',
      ] )
      ->addWysiwyg( 'content', [
        'label' => 'Step Content',
        'required' => true,
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->endRepeater()
      ->addWysiwyg( 'outro', [
        'label' => 'Outro Text',
        'instructions' => 'Optional. Appears below the last step — good for a closing note or call to action.',
        'toolbar' => 'basic',
        'media_upload' => false,
      ] );
  }

  protected function addGlobalFields(): void {
    $this->fields
      ->addTab( 'appearance' )
      ->addFields(SchemeSelectorPartial::get())
      ->addFields(SchemeSelectorPartial::get('card_color'))
      ->addTab('administrative')
      ->addText('admin_label', [
        'label' => 'Editor Label',
        'instructions' => 'Shown only in the admin panel, to help identify this section.',
      ])
      ->addTrueFalse('section_hidden', [
        'label' => 'Hide this section',
        'ui' => 1,
        'instructions' => '',
      ]);
  }

  protected function getLabel(): string
  {
    return 'Step Cards';
  }
}