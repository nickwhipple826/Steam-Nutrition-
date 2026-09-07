<?php

namespace MMM\FieldGroups\FlexibleContent;

class FormLayout extends BaseLayout {

  public function getName(): string
  {
    return 'form';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [
        'label' => 'Section Heading',
        'required' => true,
        'instructions' => 'Appears above the form.',
      ] )
      ->addText( 'subheading', [
        'label' => 'Section Subheading',
        'instructions' => 'Optional. Appears below the heading, above the form.',
      ] )
      ->addSelect( 'form_id', [
        'label' => 'Form',
        'choices' => $this->getGravityFormChoices(),
        'instructions' => 'Select the form to display in this section.',
        'required' => true,
      ] );
  }

  private function getGravityFormChoices(): array
  {
    $choices = [];

    if ( class_exists( 'GFAPI' ) ) {
      $forms = \GFAPI::get_forms();

      foreach ( $forms as $form ) {
        $choices[ $form['id'] ] = $form['title'];
      }
    }

    return $choices;
  }

  protected function getLabel(): string
  {
    return 'Form';
  }
}