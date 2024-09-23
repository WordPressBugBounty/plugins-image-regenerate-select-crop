<?php
/**
 * Helper functions for SIRSC placeholder.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Placeholder;

define( 'SIRSC_PLACEHOLDER_DIR', SIRSC_DIR . 'assets/placeholders' );
define( 'SIRSC_PLACEHOLDER_URL', \esc_url( SIRSC_URL . 'assets/placeholders' ) );

\add_filter( 'image_downsize', __NAMESPACE__ . '\\image_downsize_placeholder_force_global', 10, 3 );
\add_filter( 'image_downsize', __NAMESPACE__ . '\\image_downsize_placeholder_only_missing', 10, 3 );
\add_filter( 'init', __NAMESPACE__ . '\\prepare_folder', 10, 3 );

/**
 * Prepare placeholders folder.
 */
function prepare_folder() {
	if ( empty( \SIRSC::$settings['placeholders'] ) ) {
		// Fail-fast, not the expected setting.
		return;
	}

	if ( ! file_exists( SIRSC_PLACEHOLDER_DIR ) ) {
		@wp_mkdir_p( SIRSC_PLACEHOLDER_DIR ); // phpcs:ignore
	}
}

/**
 * Regenerate all placeholders.
 */
function regenerate_all_placeholders() {
	$files = glob( SIRSC_PLACEHOLDER_DIR . '/*' );
	if ( ! empty( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				\wp_delete_file( $file );
			}
		}
	}

	get_placeholder_url_for_subsize( 'full', true, true );
	$sizes = \SIRSC::get_all_image_sizes();
	if ( ! empty( $sizes ) ) {
		foreach ( $sizes as $n => $s ) {
			get_placeholder_url_for_subsize( $n, true, true );
		}
	}
}

/**
 * Replace all the front side images retrieved programmatically with wp function
 * with the placeholders instead of the full size image.
 *
 * @param string $f  The file.
 * @param int    $id The post ID.
 * @param string $s  The size slug.
 */
function image_downsize_placeholder_force_global( $f, $id, $s ) { // phpcs:ignore
	if ( \is_admin() ) {
		// Fail-fast, this is in admin.
		return $f;
	}

	if ( empty( \SIRSC::$settings['placeholders']['force_global'] ) ) {
		// Fail-fast, not the expected setting.
		return $f;
	}

	if ( is_array( $s ) ) {
		$s = implode( 'x', $s );
	}

	$url  = get_placeholder_url_for_subsize( $s );
	$size = \SIRSC::get_all_image_sizes( $s );
	return [ $url, $size['width'] ?? 0, $size['height'] ?? 0, true ];
}

/**
 * Replace the missing images sizes with the placeholders instead of the full size image. As the "image size name" is specified, we know what width and height the resulting image should have. Hence, first, the potential image width and height are matched against the entire set of image sizes defined in order to identify if there is the exact required image either an alternative file with the specific required width and height already generated for that width and height but with another "image size name" in the database or not. Basically, the first step is to identify if there is an image with the required width and height. If that is identified, it will be presented, regardless of the fact that the "image size name" is the requested one or it is not even yet defined for this specific post (due to a later definition of the image in the project development). If the image to be presented is not identified at any level, then the code is trying to identify the appropriate theme placeholder for the requested "image size name". For that we are using the placeholder function with the requested "image size name". If the placeholder exists, then this is going to be presented, else we are logging the missing placeholder alternative that can be added in the get_placeholder_url_for_subsize function.
 *
 * @param string $f  The file.
 * @param int    $id The pot ID.
 * @param string $s  The size slug.
 */
function image_downsize_placeholder_only_missing( $f, $id, $s ) { // phpcs:ignore
	if ( \is_admin() ) {
		// Fail-fast, this is in admin.
		return $f;
	}

	if ( empty( \SIRSC::$settings['placeholders']['only_missing'] ) ) {
		// Fail-fast, not the expected setting.
		return $f;
	}

	$all_sizes = \SIRSC::get_all_image_sizes();
	if ( 'full' !== $s && is_scalar( $s ) && ! empty( $all_sizes[ $s ] ) ) {
		try {
			$execute    = false;
			$image      = \wp_get_attachment_metadata( $id );
			$filename   = \get_attached_file( $id );
			$rez_img    = \SIRSC\Helper\allow_resize_from_original( $filename, $image, $all_sizes, $s );
			$upload_dir = \wp_upload_dir();
			if ( ! empty( $rez_img['found'] ) ) {
				$url         = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $rez_img['path'] );
				$crop        = ( ! empty( $rez_img['is_crop'] ) ) ? true : false;
				$alternative = [ $url, $rez_img['width'], $rez_img['height'], $crop ];
				return $alternative;
			}
			$request_w   = (int) $all_sizes[ $s ]['width'];
			$request_h   = (int) $all_sizes[ $s ]['height'];
			$alternative = [
				'name'         => $s,
				'file'         => $f,
				'width'        => $request_w,
				'height'       => $request_h,
				'intermediate' => true,
			];
			$found_match = false;
			if ( empty( $image ) ) {
				$image = [];
			}
			$image['width']  = ( ! empty( $image['width'] ) ) ? (int) $image['width'] : 0;
			$image['height'] = ( ! empty( $image['height'] ) ) ? (int) $image['height'] : 0;
			if ( $request_w === (int) $image['width'] && $request_h === (int) $image['height'] && ! empty( $image['file'] ) ) {
				$tmp_file = str_replace( basename( $filename ), basename( $image['file'] ), $filename );
				if ( file_exists( $tmp_file ) ) {
					$folder      = str_replace( $upload_dir['basedir'], '', $filename );
					$old_file    = basename( str_replace( $upload_dir['basedir'], '', $filename ) );
					$folder      = str_replace( $old_file, '', $folder );
					$alternative = [
						'name'         => 'full',
						'file'         => $upload_dir['baseurl'] . $folder . basename( $image['file'] ),
						'width'        => (int) $image['width'],
						'height'       => (int) $image['height'],
						'intermediate' => false,
					];
					$found_match = true;
				}
			}
			if ( ! empty( $image['sizes'] ) ) {
				foreach ( $image['sizes'] as $name => $var ) {
					if ( $found_match ) {
						break;
					}
					if ( $request_w === (int) $var['width'] && $request_h === (int) $var['height'] && ! empty( $var['file'] ) ) {
						$tmp_file = str_replace( basename( $filename ), $var['file'], $filename );
						if ( file_exists( $tmp_file ) ) {
							$folder      = str_replace( $upload_dir['basedir'], '', $filename );
							$old_file    = basename( str_replace( $upload_dir['basedir'], '', $filename ) );
							$folder      = str_replace( $old_file, '', $folder );
							$alternative = [
								'name'         => $name,
								'file'         => $upload_dir['baseurl'] . $folder . $var['file'],
								'width'        => (int) $var['width'],
								'height'       => (int) $var['height'],
								'intermediate' => true,
							];
							$found_match = true;
							break;
						}
					}
				}
			}

			if ( ! empty( $alternative ) && $found_match ) {
				// Alternative found.
				return [ $alternative['file'], $alternative['width'], $alternative['height'], $alternative['intermediate'] ];
			} else {
				$url = get_placeholder_url_for_subsize( $s );
				if ( ! empty( $url ) ) {
					// Is intermediate.
					return [ $url, (int) $request_w ?? 0, (int) $request_w ?? 0, true ];
				} else {
					return;
				}
			}
		} catch ( ErrorException $e ) {
			error_log( 'sirsc exception ' . print_r( $e, 1 ) ); // phpcs:ignore
		}
	}

	// Fallback.
	$url = get_placeholder_url_for_subsize( $s, false, true );
	if ( ! empty( $url ) ) {
		return [ $url, (int) $s[0] ?? 0, (int) $s[1] ?? 0, false ];
	} else {
		return;
	}
}

/**
 * Generate a placeholder image for a specified image size name.
 *
 * @param string $size    The selected image size slug.
 * @param bool   $update  True if the update is forced, to clear the cache.
 * @param bool   $execute True if the image size should be used as such.
 */
function get_placeholder_url_for_subsize( $size, $update = false, $execute = false ) {
	if ( empty( $size ) ) {
		$size = 'full';
	}

	$alternative = \SIRSC\Helper\maybe_match_size_name_by_width_height( $size );
	if ( ! is_scalar( $size ) ) {
		if ( true === $execute ) {
			$size = implode( 'x', $size );
		} elseif ( ! empty( $alternative ) ) {
			$size = $alternative;
		} else {
			$size = implode( 'x', $size );
		}
	}

	$dest = realpath( SIRSC_PLACEHOLDER_DIR ) . '/' . $size . '.svg';
	$url  = \esc_url( SIRSC_PLACEHOLDER_URL . '/' . $size . '.svg' );
	if ( file_exists( $dest ) && ! $update ) {
		// Return the found image url.
		return $url;
	}

	if ( file_exists( $dest ) && $update ) {
		@unlink( $dest ); // phpcs:ignore
	}

	$alls = \SIRSC::get_all_image_sizes_plugin();
	return placeholder_url( $alls, $dest, $url, $size, $alternative );
}

/**
 * Compute placeholder url.
 *
 * @param  array  $sizes       All image sizes.
 * @param  string $dest        The destination path.
 * @param  string $dest_url    The destination url.
 * @param  string $selected    The intended image size.
 * @param  string $alternative The alternative image size.
 * @return string
 */
function placeholder_url( $sizes, $dest, $dest_url = '', $selected = '', $alternative = '' ) {
	if ( empty( $sizes ) && class_exists( 'SIRSC' ) ) {
		$sizes = \SIRSC::get_all_image_sizes_plugin();
	}

	$name = $selected;
	if ( 'full' === $selected ) {
		// Compute the full fallback for a width and height.
		$name = 'full';
		if ( ! empty( $alternative ) && 'full' !== $alternative ) {
			$name = $alternative;
		} elseif ( ! empty( $sizes['large'] ) ) {
			$name = 'large';
		} elseif ( ! empty( $sizes['medium_large'] ) ) {
			$name = 'medium_large';
		}
	}

	if ( ! empty( $sizes[ $name ] ) ) {
		$width  = $sizes[ $name ]['width'] ?? 0;
		$height = $sizes[ $name ]['height'] ?? 0;
	} else {
		$size   = explode( 'x', $name );
		$width  = $size[0] ?? 0;
		$height = $size[1] ?? 0;
	}

	$width  = (int) $width;
	$height = (int) $height;
	$text   = $width . 'x' . $height;

	if ( empty( $width ) ) {
		$width = ! empty( $height ) ? ceil( $height / 2 ) : 1200;
	}
	if ( empty( $height ) ) {
		$height = ! empty( $width ) ? ceil( $width / 2 ) : 1200;
	}

	if ( ! \wp_is_writable( SIRSC_PLACEHOLDER_DIR ) ) {
		// By default set the dummy, the folder is not writtable.
		return make_placeholder_dummy( $dest, $name, $text, $width, $height );
	}

	return make_placeholder_svg( $dest, $name, $text, $width, $height );
}

/**
 * Make placeholder url.
 *
 * @param  string $dest   Images destination.
 * @param  string $name   Subsize name.
 * @param  string $text   Placeholder text.
 * @param  int    $width  Image width.
 * @param  int    $height Image height.
 * @return string
 */
function make_placeholder_dummy( $dest , $name = '', $text = '', $width = 0, $height = 0 ) { // phpcs:ignore
	$color      = \SIRSC\Helper\string2color( $name . $text );
	$is_light   = \SIRSC\Helper\is_light_color( $color );
	$color      = str_replace( '#', '', $color );
	$text_color = $is_light ? '000' : 'fff';

	if ( $name !== $text ) {
		$text = $name . ' (' . $text . ')';
	}

	return 'https://dummyimage.com/' . $width . 'x' . $height . '/' . $color . '/' . $text_color . '/jpeg?text=' . rawurlencode( '  ' . trim( $text ) . ' ' );
}

/**
 * Make placeholder svg and returns the URL.
 *
 * @param  string $dest   Images destination.
 * @param  string $name   Subsize name.
 * @param  string $text   Placeholder text.
 * @param  int    $width  Image width.
 * @param  int    $height Image height.
 * @return string
 */
function make_placeholder_svg( $dest, $name = '', $text = '', $width = 0, $height = 0 ) {
	$font_size1 = 18;
	$font_size2 = 14;
	if ( (int) $width <= 200 ) {
		$font_size1 = 10;
		if ( strlen( $name ) > 20 ) {
			$font_size1 = 5;
		}
	}

	$middle     = $height / 2;
	$color      = \SIRSC\Helper\string2color( $name . $text . $font_size1 );
	$is_light   = \SIRSC\Helper\is_light_color( $color );
	$text_color = $is_light ? '#000' : '#fff';
	$content    = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="' . $color . '"/><text x="50%" y="' . ( $middle - 10 ) . '" dominant-baseline="middle" text-anchor="middle" fill="' . $text_color . '" font-size="' . $font_size1 . '" font-family="system-ui">' . $name . '</text><text x="50%" y="' . ( $middle + 10 ) . '" dominant-baseline="middle" text-anchor="middle" fill="' . $text_color . '" fill-opacity="0.6" font-size="' . ( $font_size2 ) . '" font-family="system-ui">' . $text . '</text></svg>';

	if ( file_exists( $dest ) ) {
		\wp_delete_file( $dest );
	}

	$url = str_replace( SIRSC_PLACEHOLDER_DIR, SIRSC_PLACEHOLDER_URL, $dest );
	if ( ! file_exists( $dest ) && \wp_is_writable( SIRSC_PLACEHOLDER_DIR ) ) {
		if ( @file_put_contents( $dest, $content ) ) { // phpcs:ignore
			return $url;
		}
	}

	return $url;
}

/**
 * Outputs the placeholder for preview.
 *
 * @param string $size Subsize name.
 */
function the_placeholder_preview( $size = '' ) {
	$url  = get_placeholder_url_for_subsize( $size, false, false );
	$url .= substr_count( $url, '?' ) ? '&t=' . time() : '?t=' . time();
	?>
	<a href="<?php echo \esc_url( $url ); ?>" class="sirsc-placeholder" target="_blank"><img src="<?php echo \esc_url( $url ); ?>"></a>
	<?php
}
