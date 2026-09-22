<?php

namespace MMM\FieldGroups\FlexibleContent;

class WorkshopFactsLayout extends BaseLayout {

  public function getName(): string
  {
    return 'workshop-facts';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addImage( 'image', [
        'label' => 'Image',
        'return_format' => 'id',
        'instructions' => 'Rendered in a 4:5 box and cropped to fill.',
      ] )
      ->addButtonGroup( 'image_side', [
        'label' => 'Image Side',
        'choices' => [ 'left' => 'Left', 'right' => 'Right' ],
        'default_value' => 'left',
      ] )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addTextarea( 'lede', [ 'label' => 'Lede', 'rows' => 3 ] )
      ->addRepeater( 'facts', [
        'label' => 'Facts',
        'instructions' => 'A rule-separated list, each with a kanji marker in the left column.',
        'min' => 1,
        'max' => 6,
        'button_label' => 'Add Fact',
        'layout' => 'block',
        'collapsed' => 'title',
      ] )
      ->addText( 'kanji', [ 'label' => 'Marker', 'instructions' => 'One character, e.g. 試.' ] )
      ->addText( 'title', [ 'label' => 'Title', 'required' => true ] )
      ->addTextarea( 'body', [ 'label' => 'Body', 'rows' => 3, 'required' => true ] )
      ->endRepeater()
      ->addTab( 'gutter' )
      ->addText( 'rail_text', [ 'label' => 'Gutter Text', 'instructions' => 'e.g. 工房. Desktop only.' ] );
  }

  protected function getLabel(): string
  {
    return 'Workshop Facts';
  }
}
