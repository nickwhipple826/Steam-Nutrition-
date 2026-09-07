<?php

namespace MMM\Controllers;

use MMM\Services\SeoDataService;
use Timber\{Post, Timber};

abstract class BaseController
{
  protected array $context = [];
  protected SeoDataService $seoService;

  public function __construct()
  {
    $this->context = Timber::context();
    $this->seoService = new SeoDataService();
  }

  protected function renderView(string $template, array $data = []): void
  {
    if (isset($data['post']) && $data['post'] instanceof Post) {
      $data['seo'] = $this->seoService->getSeoData($data['post']);
    }

    Timber::render(
      $template,
      array_merge($this->context, $data)
    );
  }

  abstract public function render(): void;
}