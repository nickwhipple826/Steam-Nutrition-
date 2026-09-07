<?php

namespace MMM\FieldGroups\FlexibleContent;

class CtaLinksLayout extends BaseLayout {

  public function getName(): string
  {
    return 'cta-links';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addWysiwyg( 'intro', [
        'label' => 'Intro Text',
        'instructions' => 'Optional. A short paragraph above the buttons.',
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->addRepeater( 'links', [
        'label' => 'Links',
        'instructions' => 'One button per audience (e.g. Applicants, Residents). Drag to reorder.',
        'min' => 1,
        'button_label' => 'Add Link',
        'layout' => 'table',
        'collapsed' => 'link',
      ] )
      ->addLink( 'link', [
        'label' => 'Link',
        'required' => true,
        'return_format' => 'array',
      ] )
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'CTA Links';
  }
}