<?php

namespace MMM\FieldGroups;

/**
 * Fields on individual menu items, edited under Appearance > Menus by
 * expanding an item.
 *
 * - kanji: shown beside a mega-nav column heading or footer column
 *   heading, e.g. 品目 next to SHOP.
 * - mega_cards: image cards on the right of a top-level item's mega
 *   panel. Only meaningful on top-level Primary items, but ACF cannot
 *   target menu depth, so it shows on every item and is ignored below
 *   the top level.
 */
class MenuItemFields extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'Menu Item';
  }

  protected function getLocation(): array
  {
    return [
      [ 'nav_menu_item', '==', 'all' ],
    ];
  }

  protected function addFields(): void
  {
    $this->fields
      ->addText( 'kanji', [
        'label' => 'Kanji',
        'instructions' => 'Optional. Shown beside the heading when this item is a mega-nav or footer column, e.g. 品目.',
        'wrapper' => [ 'width' => '30' ],
      ] )
      ->addRepeater( 'mega_cards', [
        'label' => 'Mega Panel Cards',
        'instructions' => 'Top-level Primary items only. Up to three image cards on the right of the dropdown.',
        'max' => 3,
        'button_label' => 'Add Card',
        'layout' => 'block',
        'collapsed' => 'label',
      ] )
        ->addImage( 'image', [ 'label' => 'Image', 'return_format' => 'id' ] )
        ->addText( 'label', [ 'label' => 'Label' ] )
        ->addLink( 'link', [ 'label' => 'Link', 'return_format' => 'array' ] )
      ->endRepeater();
  }
}
