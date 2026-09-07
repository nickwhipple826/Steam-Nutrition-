<?php

namespace MMM\FieldGroups;

use ReflectionClass;
use StoutLogic\AcfBuilder\FieldNameCollisionException;
use StoutLogic\AcfBuilder\FieldsBuilder;

abstract class BaseFieldGroup
{
  protected FieldsBuilder $fields;

  /**
   *
   * @return void
   * @throws FieldNameCollisionException
   */
  public function register(): void
  {
    if (function_exists('acf_add_local_field_group')) {
      acf_add_local_field_group($this->build()->build());
    }
  }

  /**
   * Creates a FieldsBuilder using the fields registered in the addFields method
   * @return FieldsBuilder
   * @throws FieldNameCollisionException
   */
  public function build(): FieldsBuilder
  {
    $this->fields = new FieldsBuilder($this->getKey());

    $this->addFields();
    $this->setLocation();

    return $this->fields;
  }

  /**
   * Creates a key for this field group
   * @return string
   */
  public function getKey(): string
  {
    $className = (new ReflectionClass($this))->getShortName();
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
  }

  /**
   * Add fields to this field group
   * @return void
   * @throws FieldNameCollisionException
   */
  abstract protected function addFields(): void;

  protected function setLocation(): void
  {
    foreach ($this->getLocation() as $location) {
      $this->fields->setLocation(...$location);
    }
  }

  /**
   * Sets where this field group appears.
   * Uses a nested array, e.g.:
   * [
   *   ['post_type', '==', 'page'],
   *   ['page', '!=', '1'],
   * ]
   * @return array
   */
  abstract protected function getLocation(): array;

  /**
   * Declares the title for this field group
   * @return string
   */
  abstract protected function getTitle(): string;
}