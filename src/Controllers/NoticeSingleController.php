<?php

namespace MMM\Controllers;

use Timber\Timber;

class NoticeSingleController extends BaseController
{
  public function render(): void
  {
    $this->renderView(
      'pages/notice-listing-single.twig',
      [
        'post' => Timber::get_post(get_the_ID()),
      ]
    );
  }
}