<?php

namespace MMM\FieldGroups\Partials;

use StoutLogic\AcfBuilder\FieldNameCollisionException;
use StoutLogic\AcfBuilder\FieldsBuilder;

abstract class BasePartial
{
  /**
   * @param array<int, array<int, array{field: string, operator: string, value: string|int|bool}>> $condition
   *
   * @return FieldsBuilder
   * @throws FieldNameCollisionException
   */
  abstract public static function get(string $fieldName, array $condition = []): FieldsBuilder;
}