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
  HeroLineupLayout,
  QuickTilesLayout,
  ProductRailLayout,
  GaugeClusterLayout,
  CampaignBannerLayout,
  DropGridLayout,
  SubscribeSaveLayout,
  WorkshopFactsLayout,
  NewsletterBandLayout,
  };
use MMM\Traits\HasFlexibleContent;

class PageContent extends BaseFieldGroup {
  use HasFlexibleContent;

  public function __construct()
  {
    // ---- Steam ----------------------------------------------------
    // Registration order is the order of the Add Component dropdown,
    // so these sit in roughly the order a homepage uses them.
    $this->registerLayout( new HeroLineupLayout() );
    $this->registerLayout( new QuickTilesLayout() );
    $this->registerLayout( new ProductRailLayout() );
    $this->registerLayout( new GaugeClusterLayout() );
    $this->registerLayout( new CampaignBannerLayout() );
    $this->registerLayout( new DropGridLayout() );
    $this->registerLayout( new SubscribeSaveLayout() );
    $this->registerLayout( new WorkshopFactsLayout() );
    $this->registerLayout( new NewsletterBandLayout() );

    // ---- Inherited from Woonsocket --------------------------------
    // Still registered so existing pages do not fatal on an orphaned
    // layout reference. Delete a line here only after you have cleared
    // that layout off every page, and drop its @forward from main.scss
    // at the same time.
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
