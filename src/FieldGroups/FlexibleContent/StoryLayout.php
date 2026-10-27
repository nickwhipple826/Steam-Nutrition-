<?php

namespace MMM\FieldGroups\FlexibleContent;

/**
 * Story — a framed portrait beside long-form copy, signed off with a
 * seal and a name (About: Where the Fire Started).
 */
class StoryLayout extends BaseLayout {

  public function getName(): string
  {
    return 'story';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addImage( 'image', [
        'label' => 'Photo',
        'return_format' => 'id',
        'instructions' => 'Portrait works best — the frame is 4:5.',
      ] )
      ->addButtonGroup( 'image_side', [
        'label' => 'Photo Side',
        'choices' => [ 'left' => 'Left', 'right' => 'Right' ],
        'default_value' => 'left',
      ] )
      ->addText( 'eyebrow', [ 'label' => 'Eyebrow', 'instructions' => 'e.g. 壱 · 起源.' ] )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addWysiwyg( 'body', [
        'label' => 'Body',
        'toolbar' => 'basic',
        'media_upload' => 0,
        'tabs' => 'visual',
      ] )

      ->addTab( 'signature' )
      ->addText( 'sign_seal', [
        'label' => 'Seal',
        'maxlength' => 2,
        'instructions' => 'Optional, e.g. 創.',
      ] )
      ->addText( 'sign_name', [ 'label' => 'Name' ] )
      ->addText( 'sign_role', [ 'label' => 'Role', 'instructions' => 'e.g. Founder · Est. 2024.' ] )

      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Story';
  }
}
