<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SchemeSelectorPartial;

class CampaignBannerLayout extends BaseLayout {

  public function getName(): string
  {
    return 'campaign-banner';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addImage( 'background', [
        'label' => 'Background Image',
        'required' => true,
        'return_format' => 'id',
        'instructions' => 'Full-bleed and heavily darkened toward the edges, so texture works better here than a subject.',
      ] )
      ->addText( 'eyebrow', [ 'label' => 'Eyebrow' ] )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addText( 'kanji', [
        'label' => 'Kanji Line',
        'instructions' => 'Optional. Widely tracked characters under the heading, e.g. 工房.',
      ] )
      ->addTextarea( 'copy', [ 'label' => 'Copy', 'rows' => 3 ] )
      ->addLink( 'link', [ 'label' => 'Button', 'return_format' => 'array' ] )
      ->addTab( 'noren' )
      ->addTrueFalse( 'show_noren', [
        'label' => 'Hanging Panels',
        'instructions' => 'A noren — the split fabric curtain hung in a shop doorway — across the top of the band, on a brass rod.',
        'ui' => 1,
        'default_value' => 1,
      ] )
      ->addNumber( 'noren_panels', [
        'label' => 'Panel Count',
        'default_value' => 8,
        'min' => 3,
        'max' => 16,
      ] )->conditional( 'show_noren', '==', 1 );
  }

  protected function addGlobalFields(): void
  {
    $this->fields
      ->addTab( 'appearance' )
      ->addFields( SchemeSelectorPartial::get() )
      ->addTab( 'administrative' )
      ->addText( 'admin_label', [
        'label' => 'Editor Label',
        'instructions' => 'Shown only in the admin panel, to help identify this section.',
      ] )
      ->addTrueFalse( 'section_hidden', [
        'label' => 'Hide this section',
        'ui' => 1,
        'instructions' => '',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Campaign Banner';
  }
}
