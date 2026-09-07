<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\SchemeSelectorPartial;
use StoutLogic\AcfBuilder\FieldNameCollisionException;
use StoutLogic\AcfBuilder\FieldsBuilder;

abstract class BaseLayout
{
  protected FieldsBuilder $fields;

  /**
   * @return FieldsBuilder
   * @throws FieldNameCollisionException
   */
  public function build(): FieldsBuilder
  {
    $this->fields = new FieldsBuilder($this->getName());
    $this->addFields();
    $this->addGlobalFields();
    return $this->fields;
  }

  abstract public function getName(): string;

  /**
   * @return void
   * @throws FieldNameCollisionException
   */
  abstract protected function addFields(): void;

  protected function addGlobalFields(): void {
    $this->fields
      ->addTab( 'appearance' )
      ->addFields(SchemeSelectorPartial::get())
      ->addTab('administrative')
      ->addText('admin_label', [
        'label' => 'Editor Label',
        'instructions' => 'Shown only in the admin panel, to help identify this section.',
      ])
      ->addTrueFalse('section_hidden', [
        'label' => 'Hide this section',
        'ui' => 1,
        'instructions' => '',
      ]);
  }

  public function getView(): string
  {
    return 'views/partials/sections/' . $this->getName() . '.twig';
  }

  public function getConfig(): array
  {
    return [
      'name' => $this->getName(),
      'label' => $this->getLabel(),
      'display' => $this->getDisplay(),
      'min' => $this->getMin(),
      'max' => $this->getMax(),
    ];
  }

  abstract protected function getLabel(): string;

  protected function getDisplay(): string
  {
    return 'block';
  }

  protected function getMin(): string
  {
    return '';
  }

  protected function getMax(): string
  {
    return '';
  }
}