<?php

namespace MMM\FieldGroups\FlexibleContent;

class TeamListLayout extends BaseLayout {

  protected function getLabel(): string
  {
    return 'Team List';
  }

  public function getName(): string
  {
    return 'team-list';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addRadio( 'variant', [
        'label' => 'Layout Style',
        'instructions' => 'Full: grid with all details. Compact: condensed list. Single: one featured person with a description block.',
        'layout' => 'horizontal',
        'default_value' => 'full',
        'choices' => [
          'full' => 'Full',
          'compact' => 'Compact',
          'single' => 'Single',
        ],
      ] )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'instructions' => 'Appears above the team members.',
      ] )
      ->addWysiwyg( 'content', [
        'label' => 'Description Text',
        'instructions' => 'Optional. Appears below the heading, above the team members.',
        'toolbar' => 'basic',
        'media_upload' => false,
      ])
      ->addGroup('featured_member', [
        'label' => 'Featured Member',
        'layout' => 'block',
        'conditional_logic' => [
          [
            'field' => 'variant',
            'operator' => '==',
            'value' => 'single',
          ]
        ]
      ])
      ->addText( 'name', [
        'label' => 'Name',
        'required' => true,
      ] )
      ->addText( 'position', [
        'label' => 'Job Title',
        'required' => true,
      ] )
      ->addTextarea('description', [
        'label' => 'Description',
        'instructions' => 'Optional. Hidden if left empty.',
      ])
      ->addText( 'phone', [
        'label' => 'Phone',
        'instructions' => 'Optional. Hidden if left empty.',
      ] )
      ->addEmail( 'email', [
        'label' => 'Email',
        'instructions' => 'Optional. Hidden if left empty.',
      ] )
      ->endGroup()
      ->addRepeater( 'members', [
        'label' => 'Team Members',
        'instructions' => 'Listed in the order shown here. Drag to reorder.',
        'min' => 1,
        'button_label' => 'Add Team Member',
        'layout' => 'block',
        'collapsed' => 'name',
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
        'conditional_logic' => [
          [
            'field' => 'variant',
            'operator' => '!=',
            'value' => 'compact',
          ]
        ]
      ] )
      ->addEmail( 'email', [
        'label' => 'Email',
        'instructions' => 'Optional. Hidden if left empty.',
        'conditional_logic' => [
          [
            'field' => 'variant',
            'operator' => '!=',
            'value' => 'compact',
          ]
        ]
      ] )
      ->endRepeater();
  }
}