<?php

namespace MMM\PostTypes;

class PropertyPostType extends BasePostType {

  protected static function slug(): string
  {
    return 'properties';
  }

  protected static function singular(): string
  {
    return 'Property';
  }

  protected static function plural(): string
  {
    return 'Properties';
  }

  protected static function additionalArgs(): array
  {
    return [
      'has_archive' => 'properties',
    ];
  }
}