<?php

namespace MMM\Traits;

use MMM\FieldGroups\FlexibleContent\BaseLayout;
use MMM\Services\FlexibleContentRegistryService;

trait HasFlexibleContent
{
  protected array $layouts = [];

  /**
   * Register a layout for use within this field group.
   * @param BaseLayout $layout The layout to be added.
   * @return void
   */
  public function registerLayout(BaseLayout $layout): void
  {
    $this->layouts[] = $layout;
    FlexibleContentRegistryService::getInstance()->register($layout);
  }
}