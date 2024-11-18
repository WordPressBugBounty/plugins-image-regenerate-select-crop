<?php
/**
 * Notice for SIRSC.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC;

\add_action( 'admin_notices', __NAMESPACE__ . '\\admin_notices' );
\add_action( 'wp_ajax_plugin-deactivate-notice-sirsc', __NAMESPACE__ . '\\admin_notices_cleanup' );

/**
 * Returns true if the main settings were set.
 *
 * @return bool
 */
function is_configured() {
	return ! empty( \SIRSC::$settings );
}

/**
 * Admin notices.
 */
function admin_notices() {
	maybe_upgrade();
	maybe_not_configured();
	maybe_process_info();
}

/**
 * Output not configured info.
 */
function maybe_not_configured() {
	$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore
	if ( ! substr_count( $uri, 'page=image-regenerate-select-crop-' ) ) {
		// Fail-fast, the assets should not be loaded.
		return;
	}

	if ( is_configured() ) {
		// No need to add info.
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<p><?php \esc_html_e( 'Image Regenerate & Select Crop settings are not configured yet.', 'sirsc' ); ?></p>
	</div>
	<?php
}

/**
 * Output upgrade info.
 */
function maybe_upgrade() {
	if ( \apply_filters( 'sirsc_filter_remove_update_info', false ) ) {
		// No need to add info.
		return;
	}

	include_once __DIR__ . '/parts/notice-update.php';
}

/**
 * Output images processing info.
 */
function maybe_process_info() {
	$maybe_errors = assess_collected_errors();
	if ( empty( $maybe_errors ) ) {
		// No need to add info.
		return;
	}
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo \wp_kses_post( $maybe_errors ); ?></p>
	</div>
	<?php
	\delete_option( 'sirsc_monitor_errors' );
}


/**
 * Assess the collected regenerate results and returns the errors if found.
 *
 * @return string
 */
function assess_collected_errors() { // phpcs:ignore
	$message = '';
	$errors  = \get_option( 'sirsc_monitor_errors' );
	if ( ! empty( $errors['schedule'] ) ) {
		foreach ( $errors['schedule'] as $id => $filename ) {
			if ( empty( $errors['error'][ $id ] ) ) {
				$errors['error'][ $id ] = '<em>' . $filename . '</em> - ' . \esc_html__( 'the original filesize is too big and the server does not have enough resources to process it', 'sirsc' );
			}
		}
	}

	if ( ! empty( $errors['error'] ) ) {
		$sep = '<b class="dashicons dashicons-dismiss"></b> ';

		$rsn = [];
		if ( ! empty( $errors['initiator'] ) && 'cleanup' === $errors['initiator'] ) {
			$rsns[] = \__( 'the file you were trying to delete is the original file', 'sirsc' );
			$rsns[] = \__( 'the sub-size points to the original file and that should not be removed', 'sirsc' );
			$rsns[] = \__( 'the file is missing', 'sirsc' );
		} else {
			$rsns[] = \__( 'the image from which the script is generating the specified sub-size does not have the proper size for resize/crop to a specific width and height', 'sirsc' );
			$rsns[] = \__( 'the attachment metadata is broken', 'sirsc' );
			$rsns[] = \__( 'the original file is missing', 'sirsc' );
			$rsns[] = \__( 'the image that is processed is very big (rezolution or size) and the allocated memory on the server is not enough to handle the request', 'sirsc' );
			$rsns[] = \__( 'the overall processing on your site is too intensive', 'sirsc' );
		}

		$message = \wp_kses_post( sprintf(
			// Translators: %1$s - reasons, %2$s - separator, %3$s - server side error.
			\__( '<b>Unfortunately, there was an error</b>. Some of the execution was not successful. This can happen when: %1$s. %2$sSee the details: %3$s', 'sirsc' ),
			'<br>&bull; ' . implode( ',<br>&bull; ', $rsns ),
			'</div><div class="info-reason sirsc-errors"><div class="sirsc-log info-title">',
			'</div><div class="sirsc-log status-error">' . $sep . implode( '</div><div class="sirsc-log status-error">' . $sep, $errors['error'] ) . '</div>'
		) );

		$upls = \wp_upload_dir();

		$message = str_replace( \trailingslashit( $upls['basedir'] ), '', $message );
		$message = str_replace( \trailingslashit( $upls['baseurl'] ), '', $message );
		$message = '<div class="info-message">' . $message . '</div>';
	}

	return $message;
}

/**
 * Execute notices cleanup.
 *
 * @param bool $ajax Is AJAX call.
 */
function admin_notices_cleanup( $ajax = true ) {
	// Delete transient, only display this notice once.
	\delete_transient( SIRSC_NOTICE );

	if ( true === $ajax ) {
		// No need to continue.
		\wp_die();
	}
}
