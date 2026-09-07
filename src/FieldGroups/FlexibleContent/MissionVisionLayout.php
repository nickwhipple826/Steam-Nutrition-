<?php

namespace MMM\FieldGroups\FlexibleContent;

class MissionVisionLayout extends BaseLayout {

  public function getName(): string
  {
    return 'mission-vision';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'mission_heading', [
        'label' => 'Mission Heading',
        'required' => true,
        'default_value' => 'Mission',
      ] )
      ->addWysiwyg( 'mission_content', [
        'label' => 'Mission Text',
        'required' => true,
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->addText( 'vision_heading', [
        'label' => 'Vision Heading',
        'required' => true,
        'default_value' => 'Vision',
      ] )
      ->addWysiwyg( 'vision_content', [
        'label' => 'Vision Text',
        'required' => true,
        'toolbar' => 'basic',
        'media_upload' => false,
      ] )
      ->addRepeater( 'stats', [
        'label' => 'Stat Cards',
        'instructions' => 'Displayed alongside the Mission/Vision text. Drag to reorder.',
        'min' => 0,
        'button_label' => 'Add Stat',
        'layout' => 'block',
        'collapsed' => 'label',
      ] )
      ->addField( 'icon', 'font-awesome', [
        'label' => 'Icon',
        'instructions' => 'Choose a Font Awesome icon — it always renders navy blue.',
        'save_format' => 'class',
        'allow_null' => 1,
      ] )
      ->addText( 'number', [
        'label' => 'Number',
        'required' => true,
        'instructions' => 'e.g. "1,207". Entered as text so it can include commas, a plus sign, etc.',
      ] )
      ->addText( 'label', [
        'label' => 'Label',
        'required' => true,
        'instructions' => 'e.g. "Units Owned by WHA".',
      ] )
      ->endRepeater()
      ->addLink( 'button', [
        'label' => 'Button',
        'instructions' => 'Optional. e.g. "More About WHA".',
        'return_format' => 'array',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Mission & Vision';
  }
}