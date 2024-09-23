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
				$errors['error'][ $id ] = '<em>' . $filename . '</em> - ' . \esc_html__( 'The original filesize is too big and the server does not have enough resources to process it.', 'sirsc' );
			}
		}
	}

	if ( ! empty( $errors['error'] ) ) {
		$sep = '<b class="dashicons dashicons-dismiss"></b> ';

		if ( ! empty( $errors['initiator'] ) && 'cleanup' === $errors['initiator'] ) {
			$message = \wp_kses_post( sprintf(
				// Translators: %1$s - separator, %2$s - server side error.
				\__( '<b>Unfortunately, there was an error</b>. Some of the execution might not have been successful. This can happen when: <br>&bull; the image you were trying to delete is <b>the original</b> file,<br>&bull; the image size was pointing to the <b>the original</b> and it should not be removed,<br>&bull; the <b>file is missing</b>.%1$sSee the details: %2$s', 'sirsc' ),
				'</div><div class="info-reason sirsc-errors"><div class="sirsc-log info-title">',
				'</div><div class="sirsc-log status-error">' . $sep . implode( '</div><div class="sirsc-log status-error">' . $sep, $errors['error'] ) . '</div>'
			) );
		} else {
			$message = \wp_kses_post( sprintf(
				// Translators: %1$s - server side error.
				\__( '<b>Unfortunately, there was an error</b>. Some of the execution might not have been successful. This can happen in when: <br>&bull; the image from which the script is generating the specified image size does not have the <b>proper size</b> for resize/crop to a specific width and height,<br>&bull; the attachment <b>metadata is broken</b>,<br>&bull; the original <b>file is missing</b>,<br>&bull; the image that is processed is <b>very big</b> (rezolution or size) and the <b>allocated memory</b> on the server is not enough to handle the request,<br>&bull; the overall processing on your site is <b>too intensive</b>.%1$sSee the details: %2$s', 'sirsc' ),
				'</div><div class="info-reason sirsc-errors"><div class="sirsc-log info-title">',
				'</div><div class="sirsc-log status-error">' . $sep . implode( '</div><div class="sirsc-log  status-error">' . $sep, $errors['error'] ) . '</div>'
			) );
		}

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
