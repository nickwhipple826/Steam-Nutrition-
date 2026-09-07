<?php

namespace MMM\Controllers;

use Timber\Timber;

class OpenPositionSingleController extends BaseController
{
  public function render(): void
  {
    $this->renderView(
      'pages/open-position-listing-single.twig',
      [
        'post' => Timber::get_post(get_the_ID()),
      ]
    );
  }
}