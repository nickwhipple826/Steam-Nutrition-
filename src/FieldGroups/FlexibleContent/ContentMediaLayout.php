<?php

namespace MMM\FieldGroups\FlexibleContent;

use MMM\FieldGroups\Partials\ContentPartial;
use MMM\FieldGroups\Partials\MediaSelectorPartial;

class ContentMediaLayout extends BaseLayout
{
  public function getName(): string
  {
    return 'content-media';
  }

  protected function getLabel(): string
  {
    return 'Content + Media';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addRadio('alignment', [
          'label' => 'Media Position',
          'instructions' => 'Which side the image/card/video appears on. The content (heading, text, buttons) fills the other side.',
          'default_value' => 'left',
          'choices' => [
            'left' => 'Media on Left',
            'right' => 'Media on Right',
          ]]
      )
      ->addFields(ContentPartial::get())
      ->addFields(MediaSelectorPartial::get());
  }
}