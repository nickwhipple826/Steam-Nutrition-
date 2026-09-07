<?php

namespace MMM\Controllers;

use Timber\Timber;

class ArchiveController extends BaseController
{
  public function render(): void
  {
    $this->renderView(
      'pages/archive.twig',
      [
        'posts' => Timber::get_posts(),
        'title' => post_type_archive_title( '',false ),
      ]
    );
  }
}