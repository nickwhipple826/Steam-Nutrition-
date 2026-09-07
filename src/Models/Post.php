<?php

namespace MMM\Models;

use MMM\Services\FlexibleContentRegistryService;
use Timber\Post as TimberPost;

class Post extends TimberPost
{
  /**
   * Get components with their view paths.
   * @return array
   */
  public function components(): array
  {
    $service = FlexibleContentRegistryService::getInstance();
    $components = $this->acf()['components'] ?: [];

    return array_map(function ($component) use ($service) {
      $component['view'] = $service->getViewForLayout($component['acf_fc_layout']);
      return $component;
    }, $components);
  }

  /**
   * Get ACF fields for the post.
   * @return array|null
   */
  public function acf(): ?array
  {
    $fields = get_fields($this->ID);
    return $fields ?: null;
  }

  public function getLayoutView(string $layoutName): ?string
  {
    return FlexibleContentRegistryService::getInstance()->getViewForLayout($layoutName);
  }
}