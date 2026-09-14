<?php
/**
 * Sub-size sharpening for SIRSC.
 *
 * Core applies a single, fixed unsharp mask - `unsharpMaskImage( 0.25, 0.25,
 * 8, 0.065 )` - to every JPEG sub-size, but only when Imagick produced it
 * (see `WP_Image_Editor_Imagick::_resize()`). GD sub-sizes, and every
 * non-JPEG mime type on either editor, get no sharpening at all from core.
 *
 * When the `enable_sharpen` setting is on, this closes that gap using the
 * exact same recipe core uses, so the result never varies by editor or mime
 * type: a size core already sharpened is left alone, and everything else
 * (GD output, or PNG/WEBP on either editor) gets the identical unsharp mask,
 * applied via Imagick.
 *
 * Without Imagick there is no faithful way to reproduce this specific
 * radius/sigma/amount/threshold curve - GD has no unsharp-mask primitive to
 * match it against - so this deliberately does nothing rather than guess
 * with a different algorithm. An earlier revision here used a hand-rolled GD
 * convolution kernel and a user-tunable "amount" scaled well past core's
 * conservative values; that combination produced visible ringing/haloing,
 * especially once compounded with the JPEG generation loss from re-opening
 * and re-saving an already-compressed file. This version fixes both: no
 * more approximated GD sharpening, and no more amount past what core itself
 * uses.
 *
 * This runs as a discrete step *after* a sub-size file has already been
 * resized and saved, rather than by swapping in a custom `WP_Image_Editor`
 * subclass mid-resize. Other plugins (image optimizers in particular) also
 * filter `wp_image_editors` to install their own editor, and there is no
 * reliable way to win that priority race without silently skipping whatever
 * that other editor does during resize/save. Re-opening the already-correct
 * sub-size file and sharpening its pixels sidesteps that entirely: it works
 * no matter which editor produced the file.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Sharpen;

// phpcs:disable WordPress.WP.I18n.TextDomainMismatch

\defined( 'ABSPATH' ) || exit;

/**
 * Core's fixed unsharp-mask recipe, taken verbatim from
 * `WP_Image_Editor_Imagick::_resize()`, and applied only where core itself
 * did not already apply it.
 */
const RADIUS    = 0.25;
const SIGMA     = 0.25;
const AMOUNT    = 8;
const THRESHOLD = 0.065;

\add_filter( 'wp_generate_attachment_metadata', __NAMESPACE__ . '\\maybe_sharpen_new_metadata', 20, 2 );

/**
 * After core (or another plugin) finishes generating every sub-size for a
 * newly uploaded attachment, sharpen the ones core left untouched, if the
 * setting is enabled.
 *
 * @param  array $metadata      Attachment metadata, including the `sizes` map.
 * @param  int   $attachment_id Attachment ID.
 * @return array
 */
function maybe_sharpen_new_metadata( $metadata, $attachment_id ) {
	if ( empty( $metadata['sizes'] ) || ! \class_exists( '\SIRSC' ) || ! \SIRSC::has_enable_sharpen() ) {
		return $metadata;
	}

	$original = \get_attached_file( $attachment_id );
	if ( empty( $original ) ) {
		return $metadata;
	}

	$base_dir = \trailingslashit( \dirname( $original ) );

	foreach ( $metadata['sizes'] as $name => $size ) {
		if ( empty( $size['file'] ) ) {
			continue;
		}

		$info    = \SIRSC::get_subsizes_info( $name );
		$quality = ! empty( $info['quality'] ) ? (int) $info['quality'] : 0;
		sharpen_saved_file( $base_dir . $size['file'], $size['mime-type'] ?? '', $quality );
	}

	return $metadata;
}

/**
 * Sharpen an already-saved image file in place, using core's exact unsharp
 * mask recipe - but only where core has not already applied it.
 *
 * Used both by the upload-time hook above and directly by
 * `SIRSC\Editor\process_subsize_for_file()` right after its own
 * `$editor->save()` call, so every SIRSC-driven regenerate/crop goes through
 * the same code path.
 *
 * @param  string $path      Absolute path to the saved sub-size file.
 * @param  string $mime_type The file's mime type.
 * @param  int    $quality   Optional. Save quality, falls back to the plugin default.
 * @return bool True if the file was re-saved, false otherwise (nothing to
 *              do, core already sharpened it, or sharpening failed).
 */
function sharpen_saved_file( $path, $mime_type, $quality = 0 ) {
	if ( empty( $path ) || ! \file_exists( $path ) ) {
		return false;
	}

	if ( ! \in_array( $mime_type, [ 'image/jpeg', 'image/png', 'image/webp' ], true ) ) {
		// Only the formats Imagick can re-encode losslessly/consistently here.
		return false;
	}

	if ( core_already_sharpened( $mime_type ) ) {
		return false;
	}

	if ( ! \extension_loaded( 'imagick' ) || ! \class_exists( '\Imagick' ) ) {
		// No faithful way to reproduce core's Imagick-specific unsharp-mask
		// curve without Imagick; guessing with a different algorithm is what
		// produced visible artifacts before.
		return false;
	}

	$quality = $quality > 0 ? $quality : ( \class_exists( '\SIRSC' ) ? \SIRSC::DEFAULT_QUALITY : 90 );

	try {
		return sharpen_with_imagick( $path, $mime_type, $quality );
	} catch ( \Throwable $e ) {
		if ( \function_exists( '\SIRSC\Helper\debug' ) ) {
			\SIRSC\Helper\debug( 'SHARPEN failed for ' . $path . ': ' . $e->getMessage(), true, true );
		}
	}

	return false;
}

/**
 * True when core's own resize step already applied this exact unsharp mask.
 *
 * Core only ever sharpens JPEG output, and only via `WP_Image_Editor_Imagick`
 * (see `_resize()` in that class) - GD sub-sizes are never touched. This asks
 * WordPress which editor it would choose for this mime type right now - the
 * same internal lookup `wp_get_image_editor()` uses, respecting any
 * `wp_image_editors` filter from other plugins - so the answer stays correct
 * even when something else has swapped the active image editor.
 *
 * @param  string $mime_type Mime type.
 * @return bool
 */
function core_already_sharpened( $mime_type ) {
	if ( 'image/jpeg' !== $mime_type || ! \function_exists( '_wp_image_editor_choose' ) ) {
		return false;
	}

	$implementation = \_wp_image_editor_choose( [ 'mime_type' => $mime_type ] );

	return \is_string( $implementation )
		&& ( 'WP_Image_Editor_Imagick' === $implementation || \is_subclass_of( $implementation, 'WP_Image_Editor_Imagick' ) );
}

/**
 * Re-encode quality for a given mime type.
 *
 * PNG ignores `$quality` (lossless), everything else uses it directly.
 *
 * @param  string $mime_type Mime type.
 * @param  int    $quality   Requested quality.
 * @return int
 */
function normalize_quality( $mime_type, $quality ) {
	return 'image/png' === $mime_type ? 9 : \max( 1, \min( 100, $quality ) );
}

/**
 * Sharpen and re-save a file through Imagick, using core's exact
 * `unsharpMaskImage()` arguments (see `WP_Image_Editor_Imagick::_resize()`).
 *
 * @param  string $path      Absolute file path, read and overwritten.
 * @param  string $mime_type Mime type.
 * @param  int    $quality   Save quality (1-100, ignored for PNG).
 * @return bool
 */
function sharpen_with_imagick( $path, $mime_type, $quality ) {
	$image = new \Imagick( $path );

	if ( ! \is_callable( [ $image, 'unsharpMaskImage' ] ) ) {
		$image->destroy();
		return false;
	}

	$image->unsharpMaskImage( RADIUS, SIGMA, AMOUNT, THRESHOLD );

	if ( 'image/png' !== $mime_type ) {
		$image->setImageCompressionQuality( normalize_quality( $mime_type, $quality ) );
	}

	$image->writeImage( $path );
	$image->destroy();

	return true;
}
