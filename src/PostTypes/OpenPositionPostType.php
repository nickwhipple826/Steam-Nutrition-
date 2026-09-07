<?php

namespace MMM\PostTypes;

class OpenPositionPostType extends BasePostType {
  protected static function slug(): string
  {
    return 'open-positions';
  }

  protected static function singular(): string
  {
    return 'Open Position';
  }

  protected static function plural(): string
  {
    return 'Open Positions';
  }

  protected static function additionalArgs(): array
  {
    return [
      'has_archive' => 'open-positions',
    ];
  }
}