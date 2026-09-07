<?php

namespace MMM\Services;

use InvalidArgumentException;
use MMM\FieldGroups\BaseFieldGroup;
use MMM\Traits\Singleton;

class FieldGroupRegistryService
{
  use Singleton;

  private array $fieldGroups = [];

  public function init(): void
  {
    add_action('acf/init', [$this, 'registerAll']);
  }

  /**
   * Add all fields registered in the registry to WordPress.
   * @return void
   */
  public function registerAll(): void
  {
    foreach ($this->fieldGroups as $fieldGroupClass) {
      $fieldGroup = new $fieldGroupClass();
      $fieldGroup->register();
    }
  }

  /**
   * Register the given field in the registry.
   * @param string $fieldGroupClass The class to register. Must extend BaseFieldGroup.
   * @return void
   */
  public function register(string $fieldGroupClass): void
  {
    if (!is_subclass_of($fieldGroupClass, BaseFieldGroup::class)) {
      throw new InvalidArgumentException(
        "$fieldGroupClass is not a subclass of BaseFieldGroup"
      );
    }

    $this->fieldGroups[] = $fieldGroupClass;
  }
}