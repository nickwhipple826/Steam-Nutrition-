<?php

namespace MMM\FieldGroups\Partials;

use StoutLogic\AcfBuilder\FieldsBuilder;

class MediaSelectorPartial extends BasePartial {
  public static function get( string $fieldName = 'media', array $condition = [] ): FieldsBuilder
  {
    $fields = new FieldsBuilder( 'media_selector' );

    $fields->addGroup( $fieldName, [ 'conditional_logic' => $condition ] )
      ->addRadio( 'type', [
        'label' => 'Media Type',
        'choices' => [
          'image' => 'Image',
          'slider' => 'Slider',
          'embed' => 'Embedded Video',
          'upload' => 'Uploaded Video',
          'card' => 'Info Card',
        ]
      ] )
      ->addImage( 'image', [ 'required' => true ] )->conditional( 'type', '==', 'image' )
      ->addGallery( 'gallery' )->conditional( 'type', '==', 'slider' )
      ->addOembed( 'embed', [
        'label' => 'Video URL',
        'instructions' => 'Paste a YouTube, Vimeo, or other oEmbed-compatible URL',
        'required' => true
      ] )->conditional( 'type', '==', 'embed' )
      ->addRepeater( 'upload' )->conditional( 'type', '==', 'upload' )
      ->addFile( 'file', [
        'label' => 'Video File',
        'required' => true,
        'mime_types' => 'mp4,webm,ogv',
        'return_format' => 'array',
      ] )
      ->addText( 'media_query', [
        'label' => 'Media Query',
        'instructions' => 'e.g. (min-width: 1024px) for desktop-only sources',
        'placeholder' => '(min-width: 768px)',
      ] )
      ->addText( 'label', [
        'label' => 'Source Label',
        'instructions' => 'Helpful label like "Desktop HD" or "Mobile',
        'placeholder' => 'Desktop HD'
      ] )
      ->endRepeater()
      ->addImage( 'upload_poster', [
        'label' => 'Video Poster',
        'instructions' => 'Thumbnail shown before video plays',
      ] )->conditional( 'type', '==', 'upload' )
      ->addText( 'card_heading', [
        'label' => 'Card Heading',
        'instructions' => 'e.g. "Pay Your Rent Online"',
        'required' => true,
      ] )->conditional( 'type', '==', 'card' )
      ->addTextarea( 'card_intro', [
        'label' => 'Card Intro Text',
        'instructions' => 'Optional. e.g. "Through the Tenant Portal, you can:"',
        'rows' => 2,
      ] )->conditional( 'type', '==', 'card' )
      ->addRepeater( 'card_list', [
        'label' => 'Card Bullet List',
        'instructions' => 'Optional. One row per bullet point.',
        'min' => 0,
        'button_label' => 'Add Bullet',
        'layout' => 'table',
        'collapsed' => 'item',
      ] )->conditional( 'type', '==', 'card' )
      ->addText( 'item', [ 'label' => 'Item' ] )
      ->endRepeater()
      ->addLink( 'card_button', [
        'label' => 'Card Button',
        'instructions' => 'Optional. e.g. "Tenant Portal".',
        'return_format' => 'array',
      ] )->conditional( 'type', '==', 'card' )
      ->endGroup();

    return $fields;
  }
}