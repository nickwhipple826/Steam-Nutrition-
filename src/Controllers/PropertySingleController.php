<?php

namespace MMM\Controllers;

use Timber\Timber;

class PropertySingleController extends BaseController
{
  public function render(): void
  {
    $this->renderView(
      'pages/property-listing-single.twig',
      [
        'post' => Timber::get_post(get_the_ID()),
      ]
    );
  }
}