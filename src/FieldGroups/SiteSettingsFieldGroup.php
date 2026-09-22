<?php

namespace MMM\FieldGroups;

use MMM\Setup\OptionsPage;

class SiteSettingsFieldGroup extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'Site Settings';
  }

  protected function getLocation(): array
  {
    return [
      [ 'options_page', '==', OptionsPage::SLUG ],
    ];
  }

  protected function addFields(): void
  {
    $this->fields
      // ---------------------------------------------------------------
      ->addTab( 'branding' )
      ->addImage( 'nav_logo', [
        'label' => 'Header Logo',
        'instructions' => 'Optional. Leave empty to use the typeset wordmark and gear crest below.',
        'return_format' => 'url',
      ] )
      ->addImage( 'footer_logo', [
        'label' => 'Footer Logo',
        'instructions' => 'Optional. Falls back to the header logo, then the wordmark.',
        'return_format' => 'url',
      ] )
      ->addText( 'wordmark', [
        'label' => 'Wordmark',
        'default_value' => 'Steam',
        'instructions' => 'Set in brass woodtype beside the crest when no logo is uploaded.',
      ] )
      ->addText( 'wordmark_sub', [
        'label' => 'Wordmark Subline',
        'default_value' => 'NUTRITION',
      ] )

      // ---------------------------------------------------------------
      ->addTab( 'header' )
      ->addRepeater( 'ticker_items', [
        'label' => 'Announcement Ticker',
        'instructions' => 'Scrolls across the top of every page. Leave empty to remove the ticker entirely — the header closes up to fill the space.',
        'button_label' => 'Add Line',
        'layout' => 'table',
        'collapsed' => 'line',
      ] )
        ->addText( 'line', [ 'label' => 'Line', 'required' => true ] )
      ->endRepeater()
      ->addUrl( 'search_url', [
        'label' => 'Search URL',
        'instructions' => 'Where the search icon goes. Defaults to the WordPress search page.',
      ] )
      ->addUrl( 'account_url', [
        'label' => 'Account URL',
        'instructions' => 'Usually the Shopify customer account page.',
      ] )
      ->addUrl( 'cart_url', [
        'label' => 'Cart URL',
        'instructions' => 'Usually the Shopify cart.',
      ] )

      // ---------------------------------------------------------------
      ->addTab( 'footer' )
      ->addTextarea( 'footer_tagline', [
        'label' => 'Tagline',
        'rows' => 2,
        'default_value' => '蒸気栄養 — supplements and workwear made in small runs, tested in public.',
      ] )
      ->addRepeater( 'socials', [
        'label' => 'Social Links',
        'button_label' => 'Add Network',
        'layout' => 'table',
      ] )
        ->addSelect( 'network', [
          'label' => 'Network',
          'choices' => [
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'facebook' => 'Facebook',
            'x' => 'X',
            'other' => 'Other',
          ],
        ] )
        ->addUrl( 'url', [ 'label' => 'URL', 'required' => true ] )
      ->endRepeater()
      ->addText( 'locale_label', [
        'label' => 'Locale Label',
        'default_value' => 'United States (USD)',
        'instructions' => 'Display only — this is not a currency switcher.',
      ] )
      ->addTextarea( 'disclaimer', [
        'label' => 'Legal Disclaimer',
        'rows' => 3,
        'default_value' => 'These statements have not been evaluated by the Food and Drug Administration. These products are not intended to diagnose, treat, cure or prevent any disease.',
        'instructions' => 'The FDA DSHEA disclaimer. Required on any page that makes a structure/function claim about a supplement, so it lives in the footer where it covers every page.',
      ] )

      // ---------------------------------------------------------------
      // Inherited from Woonsocket. Nothing on the Steam templates reads
      // these any more; kept so the stored values are not orphaned.
      ->addTab( 'contact' )
      ->addText( 'address', [ 'label' => 'Address' ] )
      ->addUrl( 'address_url', [
        'label' => 'Address URL',
        'instructions' => 'Link for the address (e.g. a Google Maps link).',
      ] )
      ->addText( 'phone', [ 'label' => 'Phone' ] )
      ->addRepeater( 'office_hours', [
        'label' => 'Office Hours',
        'button_label' => 'Add Line',
        'layout' => 'table',
      ] )
        ->addText( 'line', [
          'label' => 'Line',
          'instructions' => 'e.g. "Mon–Fri: 9am–5pm"',
        ] )
      ->endRepeater();
  }
}
