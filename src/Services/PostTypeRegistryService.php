<?php

namespace MMM\Services;

use MMM\PostTypes\BasePostType;
use MMM\Traits\Singleton;

class PostTypeRegistryService
{
  use Singleton;

  /** @var class-string<BasePostType>[] */
  private array $postTypes = [];

  protected function init(): void
  {
    add_action('init', [$this, 'registerPostTypes']);
  }

  public function register(string $postTypeClass): void
  {
    if (!is_subclass_of($postTypeClass, BasePostType::class)) {
      throw new \InvalidArgumentException(
        "{$postTypeClass} must extend BasePostType"
      );
    }

    $this->postTypes[] = $postTypeClass;
  }

  public function registerPostTypes(): void
  {
    foreach ($this->postTypes as $postTypeClass) {
      $postTypeClass::register();
    }
  }
}
