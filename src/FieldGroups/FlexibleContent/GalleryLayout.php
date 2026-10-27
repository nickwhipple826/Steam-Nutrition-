<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SectionHeadPartial;

/**
 * Gallery — a grid of photos with mono captions (About: Inside the
 * Workshop). Captions come from each image's Caption in the Media
 * Library, so they are written once and reused anywhere.
 */
class GalleryLayout extends BaseLayout {

  public function getName(): string
  {
    return 'gallery';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addFields( SectionHeadPartial::get() )
      ->addGallery( 'images', [
        'label' => 'Photos',
        'return_format' => 'id',
        'min' => 1,
        'max' => 12,
        'instructions' => 'Four or eight fill the grid evenly. Set each photo\'s Caption in the Media Library to label it.',
      ] )
      ->addTrueFalse( 'feature_first', [
        'label' => 'Make the first two photos large',
        'ui' => 1,
        'default_value' => 1,
      ] )

      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Gallery';
  }
}
