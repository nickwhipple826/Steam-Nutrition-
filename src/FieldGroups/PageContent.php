<?php

namespace MMM\FieldGroups;

use MMM\FieldGroups\FlexibleContent\{AccordionLayout,
  CalloutLayout,
  CtaLinksLayout,
  FormLayout,
  HighlightBoxLayout,
  MissionVisionLayout,
  NoticeHighlightsLayout,
  NoticeListing,
  StepCardsLayout,
  TeamListLayout,
  ContentMediaLayout,
  PropertyListing,
  StaffDirectoryLayout,
  OpenPositionLayout,
  PlansReportsPoliciesLayout,
  BorderedCalloutLayout,
  };
use MMM\Traits\HasFlexibleContent;

class PageContent extends BaseFieldGroup {
  use HasFlexibleContent;

  public function __construct()
  {
    $this->registerLayout( new ContentMediaLayout() );
    $this->registerLayout( new AccordionLayout() );
    $this->registerLayout( new TeamListLayout() );
    $this->registerLayout( new StepCardsLayout() );
    $this->registerLayout( new CalloutLayout() );
    $this->registerLayout( new HighlightBoxLayout() );
    $this->registerLayout( new CtaLinksLayout() );
    $this->registerLayout( new MissionVisionLayout() );
    $this->registerLayout( new NoticeHighlightsLayout() );
    $this->registerLayout( new PropertyListing() );
    $this->registerLayout( new NoticeListing() );
    $this->registerLayout( new StaffDirectoryLayout() );
    $this->registerLayout( new FormLayout() );
    $this->registerLayout( new OpenPositionLayout() );
    $this->registerLayout( new PlansReportsPoliciesLayout() );
    $this->registerLayout( new BorderedCalloutLayout() );
  }

  public function getTitle(): string
  {
    return 'Page Content';
  }

  protected function getLocation(): array
  {
    return [
      [ 'post_type', '==', 'page' ]
    ];
  }

  protected function addFields(): void
  {
    $flexibleContent = $this->fields->addFlexibleContent( 'components',
      [
        'label' => __( 'Components', 'mcguinnessmedia' ),
        'button_label' => __( 'Add Component', 'mcguinnessmedia' ),
      ] );

    foreach ( $this->layouts as $layout ) {
      $builder = $layout->build();
      $config = $layout->getConfig();

      $flexibleContent->addLayout( $builder, $config );

    }
  }
}