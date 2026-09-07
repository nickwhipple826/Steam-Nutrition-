<?php

namespace MMM\FieldGroups;

use MMM\Setup\OptionsPage;

class SiteSettingsFieldGroup extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'Site Settings';
  }

  protected function getLocation(): array
  {
    return [
      [ 'options_page', '==', OptionsPage::SLUG ],
    ];
  }

  protected function addFields(): void
  {
    $this->fields
      ->addImage( 'nav_logo', [
        'label' => 'Nav Logo',
        'instructions' => 'Logo shown in the main site header.',
        'return_format' => 'url',
        'required' => true,
      ] )
      ->addImage( 'footer_logo', [
        'label' => 'Footer Logo',
        'instructions' => 'Logo shown in the footer. Defaults to the nav logo if left blank.',
        'return_format' => 'url',
      ] )
      ->addText( 'address', [
        'label' => 'Address',
      ] )
      ->addUrl( 'address_url', [
        'label' => 'Address URL',
        'instructions' => 'Link for the address (e.g. a Google Maps link).',
      ] )
      ->addText( 'phone', [
        'label' => 'Phone',
      ] )
      ->addRepeater( 'office_hours', [
        'label' => 'Office Hours',
        'button_label' => 'Add Line',
        'layout' => 'table',
      ] )
        ->addText( 'line', [
          'label' => 'Line',
          'instructions' => 'e.g. "Mon–Fri: 9am–5pm"',
        ] )
      ->endRepeater();
  }
}