<?php

namespace MMM\FieldGroups\FlexibleContent;

class QuickTilesLayout extends BaseLayout {

  public function getName(): string
  {
    return 'quick-tiles';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addRepeater( 'tiles', [
        'label' => 'Tiles',
        'instructions' => 'A hairline-separated row directly under the hero. Four fits the grid; more wraps to a second row.',
        'min' => 2,
        'max' => 6,
        'button_label' => 'Add Tile',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
      ->addImage( 'image', [
        'label' => 'Cutout',
        'return_format' => 'id',
        'instructions' => 'Product shot on a dark ground. Rendered at a fixed height and contained, so mismatched crops still line up.',
      ] )
      ->addText( 'kanji', [
        'label' => 'Kanji Mark',
        'instructions' => 'One character above the name, e.g. 鍛 for TRAIN. Optional.',
      ] )
      ->addText( 'title', [ 'label' => 'Title', 'required' => true ] )
      ->addText( 'subtitle', [ 'label' => 'Subtitle' ] )
      ->addLink( 'link', [ 'label' => 'Link', 'required' => true, 'return_format' => 'array' ] )
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'Quick Tiles';
  }
}
