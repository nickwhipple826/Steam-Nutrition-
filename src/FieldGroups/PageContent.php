<?php

namespace MMM\FieldGroups;

use MMM\FieldGroups\FlexibleContent\{
  HeroLineupLayout,
  QuickTilesLayout,
  ProductRailLayout,
  GaugeClusterLayout,
  CampaignBannerLayout,
  DropGridLayout,
  SubscribeSaveLayout,
  WorkshopFactsLayout,
  NewsletterBandLayout,
  ProductSpotlightLayout,
};
use MMM\Traits\HasFlexibleContent;

class PageContent extends BaseFieldGroup {
  use HasFlexibleContent;

  public function __construct()
  {
    // Registration order is the order of the Add Component dropdown,
    // so these sit in roughly the order a homepage uses them.
    $this->registerLayout( new HeroLineupLayout() );
    $this->registerLayout( new QuickTilesLayout() );
    $this->registerLayout( new ProductRailLayout() );
    $this->registerLayout( new GaugeClusterLayout() );
    $this->registerLayout( new ProductSpotlightLayout() );
    $this->registerLayout( new CampaignBannerLayout() );
    $this->registerLayout( new DropGridLayout() );
    $this->registerLayout( new SubscribeSaveLayout() );
    $this->registerLayout( new WorkshopFactsLayout() );
    $this->registerLayout( new NewsletterBandLayout() );
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