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
        'instructions' => 'Leave empty to use the theme\'s own /search page, which searches the Shopify catalogue. The header search opens in place either way; this is where Enter takes the shopper.',
      ] )
      ->addUrl( 'account_url', [
        'label' => 'Account URL',
        'instructions' => 'Leave empty to use the theme\'s own /account pages.',
      ] )
      ->addUrl( 'cart_url', [
        'label' => 'Cart URL',
        'instructions' => 'Leave empty to use the theme\'s own /cart page and drawer.',
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
      ->addTab( 'welcome offer' )
      ->addMessage( 'How it works', 'Each newsletter signup is saved in Shopify as a customer subscribed to email, and gets its own one-time code (e.g. WELCOME-7K2QX9) added to the discount below. The code takes that discount\'s settings — amount, one use per customer, start date — so change the offer in Shopify, not here. Needs SHOPIFY_ADMIN_CLIENT_ID and SHOPIFY_ADMIN_CLIENT_SECRET in wp-config.php, and — if the Dev Dashboard app is not in the same Shopify organization as the store — a one-time connect under Site Settings → Shopify Connection.' )
      ->addTrueFalse( 'welcome_enabled', [
        'label' => 'Give new subscribers a welcome code',
        'ui' => 1,
        'instructions' => 'Off: Newsletter Band forms behave as before (their own Form Action URL, or nothing).',
      ] )
      ->addText( 'welcome_discount_id', [
        'label' => 'Shopify Discount',
        'instructions' => 'Open the discount in Shopify admin and paste its URL, or just the number at the end — e.g. 1612290883805.',
      ] )
        ->conditional( 'welcome_enabled', '==', '1' )
      ->addText( 'welcome_code_prefix', [
        'label' => 'Code Prefix',
        'default_value' => 'WELCOME',
        'instructions' => 'Codes come out as PREFIX-XXXXXX. Letters and numbers only.',
      ] )
        ->conditional( 'welcome_enabled', '==', '1' )
      ->addText( 'welcome_offer_label', [
        'label' => 'Offer, in words',
        'default_value' => '10% off your first order',
        'instructions' => 'Shown beside the code on screen and in the email. Keep it matching the discount.',
      ] )
        ->conditional( 'welcome_enabled', '==', '1' )
      ->addTrueFalse( 'welcome_first_order_only', [
        'label' => 'First-time customers only',
        'ui' => 1,
        'default_value' => 1,
        'instructions' => 'On: someone who has already ordered is subscribed but not given a code.',
      ] )
        ->conditional( 'welcome_enabled', '==', '1' )
      ->addText( 'welcome_email_subject', [
        'label' => 'Email Subject',
        'instructions' => 'Leave empty for "Your welcome code: WELCOME-XXXXXX".',
      ] )
        ->conditional( 'welcome_enabled', '==', '1' )
      ->addTextarea( 'welcome_email_intro', [
        'label' => 'Email Intro',
        'rows' => 3,
        'default_value' => 'Thanks for signing up. Here is your code — enter it at checkout.',
      ] )
        ->conditional( 'welcome_enabled', '==', '1' );
  }
}
