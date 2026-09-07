<?php

namespace MMM\Controllers;

use Timber\Timber;

class PageController extends BaseController
{
  public function render(): void
  {
    $this->renderView(
      'pages/page.twig',
      [
        'post' => Timber::get_post(get_the_ID()),
      ]
    );
  }
}