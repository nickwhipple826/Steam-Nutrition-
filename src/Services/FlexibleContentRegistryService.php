<?php

namespace MMM\Services;

use MMM\FieldGroups\FlexibleContent\BaseLayout;
use MMM\Traits\Singleton;

class FlexibleContentRegistryService
{
  use Singleton;

  private array $layoutRegistry = [];

  /**
   * Register a layout instance.
   * @param BaseLayout $layout
   * @return void
   */
  public function register(BaseLayout $layout): void
  {
    $this->layoutRegistry[$layout->getName()] = $layout;
  }

  /**
   * Get all registered layouts.
   * @return array
   */
  public function getLayouts(): array
  {
    return $this->layoutRegistry;
  }

  /**
   * Check if a layout is registered.
   * @param string $name
   * @return bool
   */
  public function hasLayout(string $name): bool
  {
    return isset($this->layoutRegistry[$name]);
  }

  /**
   * Get the view for a layout.
   * @param string $layoutName
   * @return string|null
   */
  public function getViewForLayout(string $layoutName): ?string
  {
    if (!isset($this->layoutRegistry[$layoutName])) {
      return null;
    }

    return $this->layoutRegistry[$layoutName]->getView();
  }

  private function init(): void
  {
    // Intentionally blank - no constructor needed currently
  }
}