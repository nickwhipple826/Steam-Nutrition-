<?php

namespace MMM\FieldGroups;

class PropertyFields extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'Property Details';
  }

  protected function getLocation(): array
  {
    return [
      ['post_type', '==', 'properties'],
    ];
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab('location', ['label' => 'Location'])
      ->addText('address', ['label' => 'Address']);
  }
}