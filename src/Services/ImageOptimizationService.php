<?php

namespace MMM\Services;

use Imagick;
use ImagickException;
use MMM\Traits\Singleton;

/**
 * ImageOptimizationService - Converts uploaded images to WebP and rewrites attachment metadata.
 *
 * ## Behaviour
 *
 * On upload, convertible images (JPEG, PNG, GIF) that exceed the minimum file-size
 * threshold are converted to WebP via Imagick. The original file is kept on disk as a
 * fallback. All WordPress attachment metadata (main URL + every registered size) is
 * rewritten to point at the `.webp` path so that `wp_get_attachment_url()`,
 * `wp_get_attachment_image_src()`, and the block editor all return WebP URLs natively.
 *
 * On success, the post meta key `_webp_converted` is set to `1` on the attachment,
 * enabling future queries to identify unconverted attachments for bulk backfill.
 *
 * ## Configuration
 *
 * Override defaults with these constants in wp-config.php (or anywhere before init):
 *
 * ```php
 * define('MMM_WEBP_QUALITY',   82);    // Imagick compression quality (0–100)
 * define('MMM_WEBP_MIN_BYTES', 20480); // Skip files smaller than this (default 20 KB)
 * ```
 *
 * ## Querying unconverted attachments
 *
 * ```php
 * $unconverted = get_posts([
 *   'post_type'      => 'attachment',
 *   'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
 *   'meta_query'     => [[
 *     'key'     => ImageOptimizationService::META_KEY,
 *     'compare' => 'NOT EXISTS',
 *   ]],
 *   'posts_per_page' => -1,
 *   'fields'         => 'ids',
 * ]);
 * ```
 *
 * ## Requirements
 *
 * - PHP Imagick extension with WebP support
 * - Write permission to the WordPress uploads directory
 *
 * @package MMM\Services
 */
class ImageOptimizationService {
  use Singleton;

  /** Post meta key written on successful conversion. Public so callers can reference it. */
  public const META_KEY = '_webp_converted';

  /** Post meta key storing the original file extension (e.g. 'jpg', 'png') before conversion. */
  public const META_KEY_ORIGINAL_EXT = '_webp_original_ext';

  /** Imagick compression quality for WebP output. */
  private int $quality;

  /** Minimum file size in bytes below which conversion is skipped. */
  private int $minBytes;

  /** MIME types eligible for WebP conversion. */
  private const CONVERTIBLE_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
  ];

  // -------------------------------------------------------------------------
  // Initialisation
  // -------------------------------------------------------------------------

  /** @noinspection PhpUndefinedConstantInspection */
  private function init(): void
  {
    $this->quality = defined( 'MMM_WEBP_QUALITY' ) ? (int)MMM_WEBP_QUALITY : 82;
    $this->minBytes = defined( 'MMM_WEBP_MIN_BYTES' ) ? (int)MMM_WEBP_MIN_BYTES : 20480;

    if ( !$this->imagickSupportsWebP() ) {
      add_action( 'admin_notices', [ $this, 'renderImagickNotice' ] );
      return;
    }

    // Convert the full-size image immediately after upload.
    add_filter( 'wp_handle_upload', [ $this, 'convertOnUpload' ], 10, 1 );

    // After WordPress generates all registered sizes, convert each one and
    // write the success flag to post meta.
    add_filter( 'wp_generate_attachment_metadata', [ $this, 'convertSizes' ], 10, 2 );

    // Rewrite the stored attachment URL in post meta to the WebP path.
    add_filter( 'wp_update_attachment_metadata', [ $this, 'rewriteMetadataUrls' ], 10, 2 );
  }

  // -------------------------------------------------------------------------
  // Hook callbacks (public so WordPress can call them)
  // -------------------------------------------------------------------------

  /**
   * Called by the `wp_handle_upload` filter.
   *
   * Converts the freshly-uploaded file to WebP and returns updated upload data
   * so WordPress stores the WebP path/URL as the attachment's primary source.
   *
   * @param array $upload {
   * @type string $file Absolute path to the uploaded file.
   * @type string $url Public URL of the uploaded file.
   * @type string $type MIME type.
   * }
   * @return array Modified upload data pointing at the WebP file.
   */
  public function convertOnUpload( array $upload ): array
  {
    if ( !$this->shouldConvert( $upload['file'], $upload['type'] ?? '' ) ) {
      return $upload;
    }

    $webpPath = $this->deriveWebpPath( $upload['file'] );
    $webpUrl = $this->deriveWebpPath( $upload['url'] );

    if ( !$this->convertFile( $upload['file'], $webpPath ) ) {
      return $upload;
    }

    $upload['file'] = $webpPath;
    $upload['url'] = $webpUrl;
    $upload['type'] = 'image/webp';

    return $upload;
  }

  /**
   * Called by the `wp_generate_attachment_metadata` filter.
   *
   * Uses `get_attached_file()` to resolve the absolute base directory reliably,
   * avoiding the fragile manual string-join against `wp_upload_dir()` that can
   * produce wrong paths on custom upload directory configurations.
   *
   * Iterates every registered image size WordPress has generated, converts each
   * eligible one to WebP, and updates the metadata filenames in-place. If all
   * attempted conversions succeed, writes the `_webp_converted` post meta flag
   * so this attachment can be excluded from future bulk-backfill operations.
   *
   * @param array $metadata Attachment metadata (contains 'sizes' sub-array).
   * @param int $attachmentId WordPress attachment post ID.
   * @return array Updated metadata with WebP filenames for each size.
   */
  public function convertSizes( array $metadata, int $attachmentId ): array
  {
    if ( empty( $metadata['sizes'] ) ) {
      return $metadata;
    }

    // get_attached_file() returns the absolute path to the full-size file
    // regardless of custom upload directory configuration — far more reliable
    // than reconstructing from wp_upload_dir() + metadata['file'].
    $primaryFile = get_attached_file( $attachmentId );

    if ( !$primaryFile ) {
      error_log( '[ImageOptimizationService] Could not resolve attached file path for attachment ' . $attachmentId );
      return $metadata;
    }

    // All registered sizes live in the same directory as the primary file.
    $baseDir = trailingslashit( dirname( $primaryFile ) );
    $allConverted = true;

    foreach ( $metadata['sizes'] as &$sizeData ) {
      $originalFilename = $sizeData['file'];

      // Skip sizes already in WebP (e.g. converted by a third-party plugin).
      if ( str_ends_with( strtolower( $originalFilename ), '.webp' ) ) {
        continue;
      }

      $absolutePath = $baseDir . $originalFilename;

      if ( !$this->shouldConvert( $absolutePath, $sizeData['mime-type'] ?? '' ) ) {
        // A size that legitimately can't be converted (too small, wrong type)
        // should not prevent the flag from being written for the others.
        continue;
      }

      $webpAbsolute = $this->deriveWebpPath( $absolutePath );
      $webpFilename = basename( $webpAbsolute );

      if ( !$this->convertFile( $absolutePath, $webpAbsolute ) ) {
        $allConverted = false;
        continue;
      }

      $sizeData['file'] = $webpFilename;
      $sizeData['mime-type'] = 'image/webp';
    }
    unset( $sizeData );

    // Only write the flag when every attempted conversion succeeded, so the
    // flag remains meaningful as a "fully converted" signal for backfill queries.
    if ( $allConverted ) {
      update_post_meta( $attachmentId, self::META_KEY, 1 );

      // Derive the original extension from the primary file path. By this point
      // the primary file on disk is already .webp (rewritten by convertOnUpload),
      // so we infer the original from the attachment's recorded MIME type instead.
      $mime = get_post_mime_type( $attachmentId );
      $originalExt = $this->mimeToExtension( $mime ) ?? 'jpg';
      update_post_meta( $attachmentId, self::META_KEY_ORIGINAL_EXT, $originalExt );
    }

    return $metadata;
  }

  /**
   * Called by the `wp_update_attachment_metadata` filter.
   *
   * Ensures the primary `file` entry in stored metadata ends in `.webp` so
   * path-based lookups remain consistent after the upload pipeline completes.
   *
   * @param array $data The metadata array about to be saved.
   * @param int $attachmentId WordPress attachment post ID.
   * @return array Possibly-modified metadata.
   */
  public function rewriteMetadataUrls( array $data, int $attachmentId ): array
  {
    if ( empty( $data['file'] ) ) {
      return $data;
    }

    $mime = get_post_mime_type( $attachmentId );

    // Only rewrite if the attachment is already recorded as WebP (i.e. our
    // convertOnUpload hook ran successfully for this upload).
    if ( $mime === 'image/webp' ) {
      $data['file'] = $this->deriveWebpPath( $data['file'] );
    }

    return $data;
  }

  /**
   * Renders an admin notice when Imagick or its WebP codec is unavailable.
   */
  public function renderImagickNotice(): void
  {
    echo '<div class="notice notice-warning"><p>'
      . '<strong>Image Optimization:</strong> '
      . 'The Imagick PHP extension with WebP support is required but was not found. '
      . 'WebP conversion has been disabled.'
      . '</p></div>';
  }

  // -------------------------------------------------------------------------
  // Conversion helpers
  // -------------------------------------------------------------------------

  /**
   * Convert a single image file to WebP using Imagick.
   *
   * The original file is left in place as a fallback. If a WebP file already
   * exists at the destination path it is overwritten so re-uploads stay fresh.
   *
   * @param string $sourcePath Absolute path to the source image.
   * @param string $webpPath Absolute path where the WebP file should be written.
   * @return bool True on success, false on any failure.
   */
  private function convertFile( string $sourcePath, string $webpPath ): bool
  {
    try {
      $imagick = new Imagick( $sourcePath );

      // Flatten layered images (e.g. PNGs with transparency, animated GIFs).
      // For GIFs we take only the first frame; for PNGs we preserve alpha.
      if ( $imagick->getNumberImages() > 1 ) {
        $imagick = $imagick->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
      }

      $imagick->setImageFormat( 'webp' );
      $imagick->setImageCompressionQuality( $this->quality );

      // Strip unnecessary metadata (EXIF, XMP) to keep file sizes lean.
      $imagick->stripImage();

      $result = $imagick->writeImage( $webpPath );
      $imagick->clear();

      return $result;
    } catch ( ImagickException $e ) {
      error_log( '[ImageOptimizationService] Imagick error converting ' . $sourcePath . ': ' . $e->getMessage() );
      return false;
    }
  }

  // -------------------------------------------------------------------------
  // Guard helpers
  // -------------------------------------------------------------------------

  /**
   * Determine whether a given file should be converted.
   *
   * Skips: already-WebP files, non-convertible MIME types, files below the
   * minimum byte threshold, and files that do not exist on disk.
   *
   * @param string $filePath Absolute path to the file.
   * @param string $mimeType MIME type of the file.
   * @return bool
   */
  private function shouldConvert( string $filePath, string $mimeType ): bool
  {
    if ( $mimeType === 'image/webp' ) {
      return false;
    }

    if ( !in_array( $mimeType, self::CONVERTIBLE_TYPES, true ) ) {
      return false;
    }

    if ( !is_readable( $filePath ) ) {
      return false;
    }

    if ( filesize( $filePath ) < $this->minBytes ) {
      return false;
    }

    return true;
  }

  /**
   * Map a MIME type to its canonical file extension.
   *
   * @param string $mime
   * @return string|null Extension without leading dot, or null if unrecognised.
   */
  private function mimeToExtension( string $mime ): ?string
  {
    return match ($mime) {
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/webp' => 'webp',
      default => null,
    };
  }

  /**
   * Derive the WebP counterpart path for a given file path or URL.
   *
   * Replaces the extension (jpeg, jpg, png, gif) with `.webp`. Paths that
   * already end in `.webp` are returned unchanged.
   *
   * @param string $path File path or URL.
   * @return string
   */
  private function deriveWebpPath( string $path ): string
  {
    return (string)preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $path );
  }

  // -------------------------------------------------------------------------
  // Environment checks
  // -------------------------------------------------------------------------

  /**
   * Check that Imagick is loaded and that its WebP delegate is available.
   *
   * @return bool
   */
  private function imagickSupportsWebP(): bool
  {
    if ( !extension_loaded( 'imagick' ) || !class_exists( Imagick::class ) ) {
      return false;
    }

    $formats = array_map( 'strtoupper', Imagick::queryFormats() );

    return in_array( 'WEBP', $formats, true );
  }
}