<?php

namespace MMM\PostTypes;

class NoticePostType extends BasePostType {
  protected static function slug(): string
  {
    return 'notices';
  }

  protected static function singular(): string
  {
    return 'Notice';
  }

  protected static function plural(): string
  {
    return 'Notices';
  }

  protected static function additionalArgs(): array
  {
    return [
      'has_archive' => 'notices',
    ];
  }
}