<?php

namespace MMM\Controllers;

use Timber\Timber;

class SingleController extends BaseController
{
  public function render(): void
  {
    $this->renderView(
      'pages/single.twig',
      [
        'post' => Timber::get_post(get_the_ID()),
      ]
    );
  }
}