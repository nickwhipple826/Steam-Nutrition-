<?php

namespace MMM\FieldGroups\FlexibleContent;

class NewsletterBandLayout extends BaseLayout {

  public function getName(): string
  {
    return 'newsletter-band';
  }

  protected function addFields(): void
  {
    $this->fields
      ->addTab( 'content' )
      ->addText( 'seal', [
        'label' => 'Seal Characters',
        'instructions' => 'Stacked above the heading, e.g. 便り. Optional.',
      ] )
      ->addText( 'heading', [ 'label' => 'Heading', 'required' => true ] )
      ->addTextarea( 'note', [ 'label' => 'Note', 'rows' => 2 ] )
      ->addText( 'placeholder', [
        'label' => 'Field Placeholder',
        'default_value' => 'you@example.com',
      ] )
      ->addText( 'button_label', [
        'label' => 'Button Label',
        'default_value' => 'Sign up',
      ] )
      ->addText( 'success_message', [
        'label' => 'Success Message',
        'default_value' => 'You are on the list. Check your inbox to confirm.',
      ] )
      ->addTextarea( 'fine_print', [ 'label' => 'Fine Print', 'rows' => 2 ] )
      ->addTab( 'form' )
      ->addText( 'action', [
        'label' => 'Form Action URL',
        'instructions' => 'Leave empty to use the welcome offer (Site Settings → Welcome Offer): signups go to Shopify and each one gets a one-time code. Fill in only to send this form to Klaviyo/Mailchimp instead. With the welcome offer off AND this empty, the form shows the success message without sending anywhere.',
      ] )
      ->addText( 'field_name', [
        'label' => 'Email Field Name',
        'default_value' => 'email',
        'instructions' => 'The name attribute your provider expects on the email input.',
      ] );
  }

  protected function getLabel(): string
  {
    return 'Newsletter Band';
  }
}
