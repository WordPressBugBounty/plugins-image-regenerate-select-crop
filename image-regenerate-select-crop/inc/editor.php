<?php
/**
 * Editor functions for SIRSC.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Editor;

/**
 * Process single subsize for file.
 *
 * @param  int    $post_id      Attachemnt ID.
 * @param  string $name         Subsize name.
 * @param  int    $file_w       Source image width.
 * @param  int    $file_h       Source image height.
 * @param  string $pick_crop    Explicit crop position.
 * @param  int    $pick_quality Explicit quality.
 * @return array|bool
 */
function process_subsize_for_file( $post_id, $name, $file_w, $file_h, $pick_crop = 'cc', $pick_quality = 0 ) {
	global $sirsc_editor_overrides;

	$info = \SIRSC::get_subsizes_info( $name );
	if ( empty( $info ) ) {
		return false;
	}

	// Assess the original file info and predicted subsize.
	$assessed = predict_subsize( $name, $file_w, $file_h );
	$position = \SIRSC\Helper\crop_string_to_array( $pick_crop ?? $info['position'] );
	$quality  = ! empty( $pick_quality ) ? (int) $pick_quality : $info['quality'];
	$filename = \get_attached_file( $post_id );

	if ( false === \apply_filters( 'sirsc_keep_scaled', false ) ) {
		if ( substr_count( $filename, '-scaled.' ) || substr_count( $filename, '-scaled-' ) ) {
			$initial  = $filename;
			$filename = str_replace( '-scaled.', '.', $filename );
			$filename = str_replace( '-scaled-', '-', $filename );

			\update_attached_file( $post_id, $filename );
		}
	}

	$editor = \wp_get_image_editor( $filename );
	if ( ! \is_wp_error( $editor ) ) {
		if ( ! empty( $assessed['upscale']['width'] ) ) {
			$sirsc_editor_overrides = $assessed;
			\SIRSC\Helper\debug( 'CROP failed, attempt to UPSCALE', true, true );

			// Make the editor upscale the loaded resource.
			\add_filter( 'image_resize_dimensions', __NAMESPACE__ . '\\dimensions_up', 10 );
			$editor->resize( $assessed['upscale']['width'], $assessed['upscale']['height'], false );
			\remove_filter( 'image_resize_dimensions', __NAMESPACE__ . '\\dimensions_up', 10 );
		}

		// Make the editor crop the resource to intended position.
		$position = ! empty( $info['crop'] ) ? $position : false;

		$editor->resize( (int) $info['width'], (int) $info['height'], $position );

		$tracer = 'SCALE ' . $info['width'] . 'x' . $info['height'];
		if ( ! empty( $info['crop'] ) ) {
			$tracer = 'CROP ' . $info['width'] . 'x' . $info['height'] . '|' . implode( ',', $position );
		}
		\SIRSC\Helper\debug( $tracer, true, true );

		// Maybe set some custom quality.
		if ( ! empty( $quality ) ) {
			$editor->set_quality( (int) $quality );
		}

		// Finally, let's store the image.
		$saved = $editor->save();

		// Reset custom global variable.
		$sirsc_editor_overrides = [];

		clean_attachment_cache( $post_id );

		return $saved;
	}

	return false;
}

/**
 * Calculate dimensions up.
 *
 * @param  int $ow Original width.
 * @param  int $oh Original height.
 * @param  int $ew Expected width.
 * @param  int $eh Expected height.
 * @return array
 */
function calculate_dimensions_up( $ow, $oh, $ew, $eh ) {
	$size = [
		'width'  => 0,
		'height' => 0,
	];

	if ( empty( $ew ) && empty( $eh ) ) {
		return $size;
	}

	if ( empty( $ew ) ) {
		// Scale based only on height.
		$factor = ! empty( $oh ) ? $eh / $oh : 0;
	} elseif ( empty( $eh ) ) {
		// Scale based only on width.
		$factor = ! empty( $ow ) ? $ew / $ow : 0;
	} else {
		// Scaling factors needed to reach the crop size.
		$sw = ! empty( $ow ) ? $ew / $ow : 0;
		$sh = ! empty( $oh ) ? $eh / $oh : 0;

		// Use the larger scale factor to ensure the image covers the crop dimensions.
		$factor = max( $sw, $sh );
	}

	// Calculate the new dimensions.
	$size['width']  = ceil( $ow * $factor );
	$size['height'] = ceil( $oh * $factor );

	return $size;
}

/**
 * Predict subsize result.
 *
 * @param  string $name   Subsize name.
 * @param  int    $file_w Source image width.
 * @param  int    $file_h Source image height.
 * @return bool|array
 */
function predict_subsize( $name, $file_w, $file_h ) {
	$info = \SIRSC::get_subsizes_info( $name );
	if ( empty( $info ) ) {
		return false;
	}

	$info['width']  = ! empty( $info['width'] ) ? (int) $info['width'] : 0;
	$info['height'] = ! empty( $info['height'] ) ? (int) $info['height'] : 0;

	// Assess the original file info.
	$upscale = [];
	$predict = \wp_constrain_dimensions(
		$file_w ?? 0, // Original width.
		$file_h ?? 0, // Original height.
		$info['width'] ?? 0, // Subsize expected width.
		$info['height'] ?? 0 // Subsize expected height.
	);

	$assess = [
		'info'    => $info,
		'source'  => [
			'width'  => $file_w,
			'height' => $file_h,
		],
		'predict' => [
			'width'  => $predict[0],
			'height' => $predict[1],
		],
		'upscale' => $upscale,
	];

	if ( ! \SIRSC::has_enable_perfect() ) {
		return $assess;
	}

	if ( \SIRSC::has_enable_upscale() ) {
		// This must result in a 1:1 final image width and height with the requirement.
		$upscale = calculate_dimensions_up( $file_w, $file_h, $info['width'], $info['height'] );
		if ( ! empty( $upscale['width'] ) ) {
			if ( $upscale['width'] < $file_w && $upscale['height'] < $file_h ) {
				// No upscale required.
				$upscale = [];
			}
		}
	}

	$assess['upscale'] = $upscale;
	return $assess;
}

/**
 * As per core documentation, this can short-circuit the computation if the
 * positioning of the crop area is sent (matches parameters for the
 * `imagecopyresampled` function).
 *
 * @return null|array
 */
function dimensions_up() {
	global $sirsc_editor_overrides;
	if ( empty( $sirsc_editor_overrides ) ) {
		// Fallback to core.
		return null;
	}

	// Send the expected resample size.
	return [ 0, 0, 0, 0, (int) $sirsc_editor_overrides['upscale']['width'], (int) $sirsc_editor_overrides['upscale']['height'], (int) $sirsc_editor_overrides['source']['width'], (int) $sirsc_editor_overrides['source']['height'] ];
}
