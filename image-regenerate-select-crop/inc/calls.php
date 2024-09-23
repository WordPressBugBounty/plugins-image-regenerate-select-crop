<?php
/**
 * AJAX handlers for SIRSC actions.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\AJAX;

\add_action( 'wp_ajax_sirsc_single_details', __NAMESPACE__ . '\\single_details' );
\add_action( 'wp_ajax_sirsc_single_cleanup', __NAMESPACE__ . '\\single_cleanup' );
\add_action( 'wp_ajax_sirsc_single_regenerate', __NAMESPACE__ . '\\single_regenerate' );
\add_action( 'wp_ajax_sirsc_crop_position', __NAMESPACE__ . '\\single_regenerate' );
\add_action( 'wp_ajax_sirsc_start_delete', __NAMESPACE__ . '\\delete_image_size' );
\add_action( 'wp_ajax_sirsc_refresh_summary', __NAMESPACE__ . '\\refresh_summary' );
\add_action( 'wp_ajax_sirsc_start_delete_file', __NAMESPACE__ . '\\delete_image_file' );
\add_action( 'wp_ajax_sirsc_show_image_size_info', __NAMESPACE__ . '\\get_image_single_size_info' );
\add_action( 'wp_ajax_sirsc_start_regenerate_size', __NAMESPACE__ . '\\bulk_regenerate' );
\add_action( 'wp_ajax_sirsc_start_cleanup_size', __NAMESPACE__ . '\\bulk_cleanup' );
\add_action( 'wp_ajax_sirsc_start_raw_cleanup', __NAMESPACE__ . '\\bulk_raw_cleanup' );
\add_action( 'wp_ajax_sirsc_refresh_log', __NAMESPACE__ . '\\refresh_log' );
\add_action( 'wp_ajax_sirsc_reset_log', __NAMESPACE__ . '\\reset_log' );
\add_action( 'wp_ajax_sirsc_cancel_cron_task', __NAMESPACE__ . '\\cancel_cron_task' );

/**
 * End the AJAX request.
 */
function sirsc_call_end() {
	echo '<script>sirsrcAssessButtonsClicks()</script>';
	\wp_die();
	die();
}

/**
 * Verify that the AJAX call is legit for options managers.
 *
 * @param string $type      Type of request (get|post).
 * @param string $condition Type of role condition (full|user).
 * @param string $verify    Which nonce to verify.
 */
function verify_ajax_call_nonce( string $type = 'get', string $condition = 'full', string $verify = 'sirsc-ajax-actions' ) {
	$nonce = 'get' === $type
		? filter_input( INPUT_GET, 'verify', FILTER_DEFAULT )
		: filter_input( INPUT_POST, 'verify', FILTER_DEFAULT );

	if ( empty( $nonce ) || ! \is_user_logged_in()
		|| ( 'full' === $condition && ! \current_user_can( 'manage_options' ) ) ) {
		\wp_die( \esc_html__( 'Action not allowed.', 'sirsc' ), \esc_html__( 'Security Breach', 'sirsc' ) );
	}

	if ( ! \wp_verify_nonce( $nonce, $verify ) ) {
		\wp_die( \esc_html__( 'Action not allowed.', 'sirsc' ), \esc_html__( 'Security Breach', 'sirsc' ) );
	}
}

/**
 * AJAX handler for regenerating all the files for an image size.
 */
function bulk_regenerate() {
	verify_ajax_call_nonce();

	$start = filter_input( INPUT_GET, 'start', FILTER_DEFAULT );
	$size  = filter_input( INPUT_GET, 'size', FILTER_DEFAULT );
	$cpt   = filter_input( INPUT_GET, 'cpt', FILTER_DEFAULT );
	if ( ! empty( $size ) ) {
		if ( ! empty( \SIRSC::$use_cron ) ) {
			\SIRSC\Cron\assess_task( 'regenerate_image_sizes_on_request', [
				'size' => $size,
				'cpt'  => (string) $cpt,
			] );
		} else {
			\SIRSC\Helper\regenerate_image_sizes_on_request( $start, $size, $cpt );
		}
	}
	sirsc_call_end();
}

/**
 * AJAX handler for canceling the cron tasks.
 */
function cancel_cron_task() {
	verify_ajax_call_nonce();

	$hook = filter_input( INPUT_GET, 'hook', FILTER_DEFAULT );
	if ( ! empty( $hook ) ) {
		\SIRSC\Cron\cancel_task( $hook );
	}
	sirsc_call_end();
}

/**
 * AJAX handler for cleanup all the files for an image size.
 */
function bulk_cleanup() {
	verify_ajax_call_nonce();

	$start = filter_input( INPUT_GET, 'start', FILTER_DEFAULT );
	$size  = filter_input( INPUT_GET, 'size', FILTER_DEFAULT );
	$cpt   = filter_input( INPUT_GET, 'cpt', FILTER_DEFAULT );
	if ( ! empty( $size ) ) {
		if ( ! empty( \SIRSC::$use_cron ) ) {
			\SIRSC\Cron\assess_task( 'cleanup_image_sizes_on_request', [
				'size' => $size,
				'cpt'  => (string) $cpt,
			] );
		} else {
			\SIRSC\Helper\cleanup_image_sizes_on_request( $start, $size, $cpt );
		}
	}
	sirsc_call_end();
}

/**
 * AJAX handler for cleanup all the files for an image size.
 */
function bulk_raw_cleanup() {
	verify_ajax_call_nonce();

	$start = filter_input( INPUT_GET, 'start', FILTER_DEFAULT );
	$type  = filter_input( INPUT_GET, 'type', FILTER_DEFAULT );
	$cpt   = filter_input( INPUT_GET, 'cpt', FILTER_DEFAULT );
	if ( ! empty( $type ) ) {
		if ( ! empty( \SIRSC::$use_cron ) ) {
			\SIRSC\Cron\assess_task( 'raw_cleanup_on_request', [
				'type' => $type,
				'cpt'  => (string) $cpt,
			] );
		} else {
			\SIRSC\Helper\raw_cleanup_on_request( $start, $type, $cpt );
		}
	}
	sirsc_call_end();
}

/**
 * AJAX handler for showing all image size for an attachment.
 */
function single_details() {
	verify_ajax_call_nonce( 'get', 'user' );

	$id = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	if ( ! empty( $id ) ) {
		\SIRSC\Helper\attachment_sizes_lightbox( $id );
	}
	sirsc_call_end();
}

/**
 * AJAX handler for raw cleanup of all image sizes for an attachment.
 */
function single_cleanup() {
	verify_ajax_call_nonce();

	$id = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	if ( ! empty( $id ) ) {
		\SIRSC\Helper\single_attachment_raw_cleanup( $id );
		echo \SIRSC\Helper\make_buttons( $id, true ); // phpcs:ignore
		\SIRSC\Helper\the_document_ready_js( 'sirscRefreshSummary( \'' . $id . '\' );' );
	}
	sirsc_call_end();
}

/**
 * AJAX handler for regeneration of an image size (or all) for an attachment.
 */
function single_regenerate() {
	verify_ajax_call_nonce();

	$id      = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	$pos     = filter_input( INPUT_GET, 'position', FILTER_DEFAULT );
	$size    = filter_input( INPUT_GET, 'size', FILTER_DEFAULT );
	$quality = filter_input( INPUT_GET, 'quality', FILTER_DEFAULT );
	$count   = filter_input( INPUT_GET, 'count', FILTER_VALIDATE_INT );
	if ( ! empty( $id ) ) {
		\SIRSC\Helper\process_image_sizes_on_request( $id, $size, $pos, $quality );
		if ( 'all' === $size ) {
			echo \SIRSC\Helper\make_buttons( $id, true ); // phpcs:ignore
			\SIRSC\Helper\the_document_ready_js( 'sirscRefreshSummary( \'' . $id . '\' );' );
		} else {
			\SIRSC\Helper\show_image_single_size_info( $id, $size, '', [], $count );
			\SIRSC\Helper\the_document_ready_js( 'sirscRefreshSrc( \'' . $id . '\', \'' . $size . '\' ); sirscRefreshSummary( \'' . $id . '\' );' );
		}
	}
	sirsc_call_end();
}

/**
 * AJAX handler for showing all image size for an attachment.
 *
 * @return void
 */
function refresh_summary() {
	verify_ajax_call_nonce( 'get', 'user' );

	$id   = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	$wrap = filter_input( INPUT_GET, 'wrap', FILTER_DEFAULT );
	if ( ! empty( $id ) ) {
		if ( ! empty( $wrap ) ) {
			\SIRSC\Helper\attachment_listing_summary( $id, [], $wrap );
		} else {
			\SIRSC\Helper\attachment_summary( $id );
		}
	}
	sirsc_call_end();
}

/**
 * AJAX handler for deleting an image size for an attachment.
 */
function delete_image_size() {
	verify_ajax_call_nonce();

	$id    = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	$size  = filter_input( INPUT_GET, 'size', FILTER_DEFAULT );
	$count = filter_input( INPUT_GET, 'count', FILTER_VALIDATE_INT );
	if ( ! empty( $id ) ) {
		\SIRSC\Helper\delete_image_sizes_on_request( $id, $size );
		\SIRSC\Helper\show_image_single_size_info( $id, $size, '', [], $count );
		\SIRSC\Helper\the_document_ready_js( 'sirscRefreshSummary( \'' . $id . '\' );' );
	}

	sirsc_call_end();
}

/**
 * AJAX handler for deleting an image size for an attachment.
 */
function delete_image_file() {
	verify_ajax_call_nonce();

	$id       = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	$size     = filter_input( INPUT_GET, 'size', FILTER_DEFAULT );
	$filename = filter_input( INPUT_GET, 'filename', FILTER_DEFAULT );
	$wrap     = filter_input( INPUT_GET, 'wrap', FILTER_DEFAULT );
	$count    = filter_input( INPUT_GET, 'count', FILTER_VALIDATE_INT );
	if ( ! empty( $id ) ) {
		\SIRSC\Helper\delete_image_file_on_request( $id, $filename, $size, $wrap );
		if ( substr_count( $size, ',' ) ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscExecuteGetRequest( \'action=sirsc_single_details&post-id=' . $id . '\', \'sirsc-lightbox\' );' );
		} else {
			\SIRSC\Helper\show_image_single_size_info( $id, $size, '', [], $count );
			\SIRSC\Helper\the_document_ready_js( 'sirscRefreshSummary( \'' . $id . '\', \'' . $wrap . '\' );', true );
		}
	}

	sirsc_call_end();
}

/**
 * AJAX handler for showing an image size for an attachment.
 */
function get_image_single_size_info() {
	verify_ajax_call_nonce( 'get', 'user' );

	$id    = filter_input( INPUT_GET, 'post-id', FILTER_VALIDATE_INT );
	$size  = filter_input( INPUT_GET, 'size', FILTER_DEFAULT );
	$count = filter_input( INPUT_GET, 'count', FILTER_VALIDATE_INT );

	if ( ! empty( $id ) && ! empty( $size ) ) {
		\SIRSC\Helper\show_image_single_size_info( $id, $size, '', [], $count );
	} else {
		\SIRSC\Helper\the_document_ready_js( 'sirscExecuteGetRequest( \'action=sirsc_single_details&post-id=' . $id . '\', \'sirsc-lightbox\' );' );
	}

	if ( ! empty( \SIRSC\Helper\sirsc_analyse_error() ) ) {
		\SIRSC\Helper\the_document_ready_js( 'sirscExecuteGetRequest( \'action=sirsc_single_details&post-id=' . $id . '\', \'sirsc-lightbox\' );' );

		\SIRSC\Helper\the_document_ready_js( 'sirscScrollToFreshSummary( \'' . $id . '\', \'sirsc-lightbox\' );', true );
	}

	sirsc_call_end();
}

/**
 * AJAX handler for real time logs view.
 */
function refresh_log() {
	verify_ajax_call_nonce();

	$type  = filter_input( INPUT_GET, 'type', FILTER_DEFAULT );
	$level = filter_input( INPUT_GET, 'level', FILTER_DEFAULT );
	if ( ! empty( $level ) ) {
		$settings = \get_option( 'sirsc_settings' );
		if ( 'on' === $level ) {
			$settings['disable_verbose_log'] = true;
		} else {
			$settings['disable_verbose_log'] = false;
		}
		\update_option( 'sirsc_settings', $settings ); // phpcs:ignore
		\Sirsc\mark_updated_settings();
		\SIRSC\Debug\log_delete( $type );
	}

	if ( ! empty( $type ) ) {
		if ( 'seo-images' === $type ) {
			echo \wp_kses_post( nl2br( \SIRSC\Debug\log_read( 'seo-images' ) ) );
		} else {
			echo \wp_kses_post( '<ol>' . \SIRSC\Debug\log_read( $type ) . '<ol>' );
		}
	}

	sirsc_call_end();
}

/**
 * AJAX handler for resetting the logs.
 */
function reset_log() {
	verify_ajax_call_nonce();

	$type = filter_input( INPUT_GET, 'type', FILTER_DEFAULT );
	if ( ! empty( $type ) ) {
		\SIRSC\Debug\log_delete( $type );
	}
	sirsc_call_end();
}
