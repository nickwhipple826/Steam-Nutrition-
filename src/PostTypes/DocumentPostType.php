<?php

namespace MMM\PostTypes;

class DocumentPostType extends BasePostType {

  protected static function slug(): string
  {
    return 'documents';
  }

  protected static function singular(): string
  {
    return 'Document';
  }

  protected static function plural(): string
  {
    return 'Documents';
  }

  protected static function additionalArgs(): array
  {
    return [
      'has_archive' => 'documents',
    ];
  }
}