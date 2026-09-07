<?php

namespace MMM\FieldGroups;

class SeoFields extends BaseFieldGroup
{
  protected function getTitle(): string
  {
    return 'SEO Metadata';
  }

  protected function getLocation(): array
  {
    return [
      ['post_type', '!=', 'attachment'],
    ];
  }

  protected function addFields(): void
  {
    $this->fields
      ->addGroup('seo_metadata', [
        'label' => 'SEO Metadata',
        'layout' => 'block',
      ])
      // Core search metadata
      ->addText('title', [
        'label' => 'SEO Title',
        'instructions' => 'Recommended: 50–60 characters. Defaults to page title.',
        'maxlength' => 60,
      ])
      ->addTextarea('description', [
        'label' => 'Meta Description',
        'instructions' => 'Recommended: 150–160 characters.',
        'rows' => 3,
        'maxlength' => 160,
      ])
      ->addUrl('canonical_url', [
        'label' => 'Canonical URL',
        'instructions' => 'Leave blank to use the default permalink.',
      ])
      ->addTrueFalse('noindex', [
        'label' => 'No Index',
        'ui' => 1,
        'instructions' => 'Prevent this page from appearing in search results.',
      ])
      ->addTrueFalse('nofollow', [
        'label' => 'No Follow',
        'ui' => 1,
        'instructions' => 'Prevent search engines from following links on this page.',
      ])
      ->addTrueFalse('noimageindex', [
        'label' => 'No Image Index',
        'ui' => 1,
        'instructions' => 'Prevent images on this page from being indexed.',
      ])
      // Open Graph
      ->addAccordion('open_graph', [
        'label' => 'Social Sharing (Open Graph)',
        'open' => 0,
      ])
      ->addText('og_title', [
        'label' => 'OG Title',
        'instructions' => 'Overrides SEO title for social sharing.',
      ])
      ->addTextarea('og_description', [
        'label' => 'OG Description',
        'rows' => 2,
      ])
      ->addImage('og_image', [
        'label' => 'OG Image',
        'instructions' => 'Recommended: 1200×630px.',
        'return_format' => 'array',
      ])
      ->addSelect('og_type', [
        'label' => 'OG Type',
        'choices' => [
          'website' => 'Website',
          'article' => 'Article',
          'profile' => 'Profile',
        ],
        'default_value' => 'website',
      ])
      // Twitter metadata
      ->addAccordion('twitter', [
        'label' => 'X (Twitter) Cards',
        'open' => 0,
      ])
      ->addSelect('twitter_card', [
        'label' => 'Card Type',
        'choices' => [
          'summary' => 'Summary',
          'summary_large_image' => 'Summary Large Image',
        ],
        'default_value' => 'summary_large_image',
      ])
      ->addText('twitter_title', [
        'label' => 'X Title',
      ])
      ->addTextarea('twitter_description', [
        'label' => 'X Description',
        'rows' => 2,
      ])
      ->addImage('twitter_image', [
        'label' => 'X Image',
        'return_format' => 'array',
      ])
      // Schema
      ->addAccordion('schema', [
        'label' => 'Structured Data',
        'open' => 0,
      ])
      ->addTrueFalse('disable_schema', [
        'label' => 'Disable Schema for This Page',
        'ui' => 1,
      ])
      ->addSelect('schema_type', [
        'label' => 'Schema Page Type',
        'choices' => [
          'WebPage' => 'Web Page',
          'Article' => 'Article',
          'BlogPosting' => 'Blog Post',
          'FAQPage' => 'FAQ Page',
          'Service' => 'Service',
        ],
        'default_value' => 'WebPage',
        'conditional_logic' => [
          [
            [
              'field' => 'disable_schema',
              'operator' => '!=',
              'value' => 1,
            ],
          ],
        ],
      ])
      ->addText('schema_breadcrumb_title', [
        'label' => 'Breadcrumb Title Override',
        'instructions' => 'Used for BreadcrumbList schema.',
      ])
      ->addPostObject('schema_author', [
        'label' => 'Author Override',
        'post_type' => ['user'],
        'allow_null' => 1,
        'instructions' => 'Overrides default author for EEAT signals.',
      ])
      ->endGroup();
  }
}