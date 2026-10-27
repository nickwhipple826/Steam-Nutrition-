<?php

namespace MMM\FieldGroups\FlexibleContent;

/**
 * CTA Band — a closing plate: optional seal, heading and one line on
 * the left, buttons on the right. No photo, unlike Campaign Banner.
 * (Rewards: Fire Up Your Account; About: Fuel the Engine.)
 */
class CtaBandLayout extends BaseLayout {

  public function getName(): string
  {
    return 'cta-band';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'seal', [
        'label' => 'Seal',
        'maxlength' => 2,
        'instructions' => 'Optional vermilion stamp before the heading, e.g. 蒸.',
      ] )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addText( 'copy', [ 'label' => 'Line', 'instructions' => 'Optional. One sentence.' ] )
      ->addRepeater( 'buttons', [
        'label' => 'Buttons',
        'instructions' => 'The first is solid, the second outline.',
        'min' => 1,
        'max' => 2,
        'button_label' => 'Add Button',
        'layout' => 'table',
      ] )
        ->addLink( 'button', [ 'label' => 'Button', 'return_format' => 'array' ] )
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'CTA Band';
  }
}
