<?php

namespace MMM\FieldGroups;

class HeroFieldGroup extends BaseFieldGroup {

  protected function addFields(): void
  {
    $this->fields
      ->addGroup( 'hero' )
      ->addText( 'subheading' )
      ->addText( 'heading', [ 'required' => true ] )
      ->addLink( 'button', [ 'required' => false ] )
      ->addImage( 'background', [ 'required' => true, 'return_format' => 'id' ] )
      ->endGroup();
  }

  protected function getLocation(): array
  {
    return [
      [ 'post_type', '==', 'page' ]
    ];
  }

  protected function getTitle(): string
  {
    return 'Hero';
  }
}