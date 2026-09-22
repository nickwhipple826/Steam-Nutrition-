<?php

namespace MMM\FieldGroups\FlexibleContent;

class GaugeClusterLayout extends BaseLayout {

  public function getName(): string
  {
    return 'gauge-cluster';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addTextarea( 'note', [ 'label' => 'Note', 'rows' => 3 ] )
      ->addLink( 'link', [
        'label' => 'Section Link',
        'return_format' => 'array',
        'instructions' => 'Optional. e.g. the full supplement facts panel.',
      ] )
      ->addRepeater( 'gauges', [
        'label' => 'Gauges',
        'instructions' => 'Three fits the grid cleanly. Each is a pressure dial whose needle sweeps once on load.',
        'min' => 1,
        'max' => 6,
        'button_label' => 'Add Gauge',
        'layout' => 'block',
        'collapsed' => 'value',
      ] )
      ->addText( 'value', [
        'label' => 'Value',
        'required' => true,
        'instructions' => 'The number as it should read, e.g. 250 mg or 1,200 mg.',
      ] )
      ->addText( 'label', [
        'label' => 'Label',
        'required' => true,
        'instructions' => 'What the number measures, e.g. CAFFEINE ANHYDROUS.',
      ] )
      ->addText( 'product', [
        'label' => 'Product',
        'instructions' => 'Optional. Which tub it is in, e.g. TRAIN.',
      ] )
      ->addText( 'kanji', [
        'label' => 'Kanji Mark',
        'instructions' => 'Optional. One character before the product name.',
      ] )
      ->addRange( 'level', [
        'label' => 'Needle Position',
        'instructions' => 'Where the needle lands on the dial. This is a visual weight, not the dose — set it by how big the number feels against a typical competitor, not by the number itself.',
        'min' => 0,
        'max' => 100,
        'step' => 1,
        'default_value' => 70,
        'append' => '%',
      ] )
      ->endRepeater()
      ->addTab( 'gutter' )
      ->addText( 'rail_text', [
        'label' => 'Gutter Text',
        'instructions' => 'Vertical Japanese marker, e.g. 機関. Desktop only.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Gauge Cluster';
  }
}
