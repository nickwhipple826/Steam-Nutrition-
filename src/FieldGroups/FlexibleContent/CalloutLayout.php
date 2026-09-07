<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SchemeSelectorPartial;

class CalloutLayout extends BaseLayout {

  public function getName(): string
  {
    return 'callout';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab('content')
      ->addSelect('variant', [
        'required' => false,
        'choices' => [
          'default' => 'Default',
          'card' => 'Card',
        ],
        'default_value' => 'default',
      ])
      ->addFields(SchemeSelectorPartial::get(
        'card_color', [
          [
            'field' => 'variant',
            'operator' => '==',
            'value' => 'card',
          ],
      ]))
      ->addText('heading', ['required' => true])
      ->addWysiwyg('content', [
        'required' => false,
        'toolbar' => 'basic',
        'media_upload' => false,
      ])
      ->addRepeater('buttons', [
        'label' => 'Buttons',
        'min' => 0,
        'button_label' => 'Add Button',
        'layout' => 'table',
        'collapsed' => 'link',
      ])
      ->addLink('link', [
        'label' => 'Link',
        'required' => false,
        'return_format' => 'array',
      ])
      ->endRepeater();
  }

  protected function getLabel(): string
  {
    return 'Callout';
  }
}