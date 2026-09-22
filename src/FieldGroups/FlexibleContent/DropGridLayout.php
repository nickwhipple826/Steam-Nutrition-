<?php

namespace MMM\FieldGroups\FlexibleContent;

class DropGridLayout extends BaseLayout {

  public function getName(): string
  {
    return 'drop-grid';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addTextarea( 'note', [ 'label' => 'Note', 'rows' => 2 ] )
      ->addLink( 'link', [ 'label' => 'Section Link', 'return_format' => 'array' ] )
      ->addRepeater( 'tiles', [
        'label' => 'Tiles',
        'instructions' => 'Riveted plates with an asanoha ground. Written for apparel and gear, where there is no product photography yet.',
        'min' => 1,
        'max' => 8,
        'button_label' => 'Add Tile',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
      ->addText( 'seal', [
        'label' => 'Seal Character',
        'instructions' => 'One character inside the vermilion stamp, e.g. 工. Optional.',
      ] )
      ->addText( 'status', [
        'label' => 'Status',
        'instructions' => 'Optional, top right, e.g. 2 LEFT or RESTOCKED. Stock language only — this is not a badge slot.',
      ] )
      ->addText( 'title', [ 'label' => 'Title', 'required' => true ] )
      ->addText( 'subtitle', [ 'label' => 'Subtitle' ] )
      ->addText( 'price', [ 'label' => 'Price', 'instructions' => 'Written as it should display, e.g. $68.' ] )
      ->addLink( 'link', [ 'label' => 'Link', 'return_format' => 'array' ] )
      ->endRepeater()
      ->addTab( 'gutter' )
      ->addText( 'rail_text', [ 'label' => 'Gutter Text', 'instructions' => 'e.g. 入荷. Desktop only.' ] );
  }

  protected function getLabel(): string
  {
    return 'Drop Grid';
  }
}
