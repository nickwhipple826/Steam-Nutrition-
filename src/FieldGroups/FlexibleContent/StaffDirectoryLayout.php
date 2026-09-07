<?php

namespace MMM\FieldGroups\FlexibleContent;

class StaffDirectoryLayout extends BaseLayout {

  public function getName(): string
  {
    return 'staff-directory';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'instructions' => 'Appears above the staff directory.',
      ] )
      ->addRepeater( 'staff_members', [
        'label' => 'Staff Members',
        'instructions' => 'Listed in the order shown here. Drag to reorder.',
        'min' => 1,
        'button_label' => 'Add Staff Member',
        'layout' => 'block',
        'collapsed' => 'name',
      ] )
      ->addImage( 'photo', [
        'label' => 'Photo',
        'instructions' => 'Optional. Card displays without a photo if left empty.',
        'return_format' => 'id',
        'preview_size' => 'medium',
      ] )
      ->addText( 'name', [
        'label' => 'Name',
        'required' => true,
      ] )
      ->addText( 'position', [
        'label' => 'Job Title',
        'required' => true,
      ] )
      ->addText( 'phone', [
        'label' => 'Phone',
        'instructions' => 'Optional. Hidden if left empty.',
      ] )
      ->addEmail( 'email', [
        'label' => 'Email',
        'instructions' => 'Optional. Hidden if left empty.',
      ] )
      ->addWysiwyg( 'bio', [
        'label' => 'Full Bio',
        'toolbar' => 'basic',
        'media_upload' => false,
        'instructions' => 'Shown in the "More Info" modal popup. Optional — button is hidden if left empty.',
      ] )
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'Staff Directory';
  }
}