<?php

namespace MMM\Controllers;

use Timber\Timber;

class NotFoundController extends BaseController
{
  public function render(): void
  {
    status_header(404);

    $this->renderView(
      'pages/404.twig',
      [
        'title' => '404 - Page Not Found',
        'menu' => Timber::get_menu('primary'),
      ]
    );
  }
}