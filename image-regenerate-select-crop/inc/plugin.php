<?php
/**
 * Notice for SIRSC.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC;

\add_filter( 'plugin_action_links_' . \plugin_basename( SIRSC_FILE ), __NAMESPACE__ . '\\plugin_action_links' );
\add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_textdomain' );
\add_action( 'plugins_loaded', __NAMESPACE__ . '\\check_version' );
\register_activation_hook( SIRSC_FILE, __NAMESPACE__ . '\\plugin_activation' );
\register_deactivation_hook( SIRSC_FILE, __NAMESPACE__ . '\\plugin_deactivation' );

/**
 * Add the plugin settings and plugin URL links.
 *
 * @param array $links The plugin links.
 */
function plugin_action_links( $links ) {
	array_unshift( $links, '<a href="https://iuliacazan.ro/image-regenerate-select-crop">' . \esc_html__( 'Plugin URL', 'sirsc' ) . '</a>' );
	array_unshift( $links, '<a href="' . \esc_url( 'admin.php?page=' . SIRSC_PAGE ) . '">' . \esc_html__( 'Settings', 'sirsc' ) . '</a>' );

	return $links;
}

/**
 * Load text domain for internalization.
 */
function load_textdomain() {
	\load_plugin_textdomain( 'sirsc', false, basename( SIRSC_DIR ) . '/langs/' );
}

/**
 * Returns the build version to be used with assets.
 *
 * @return string
 */
function get_build_ver() {
	$build_time = \get_option( 'sisrsc_build_time', 0 );
	if ( empty( $build_time ) ) {
		$build_time = time() . '.' . \wp_rand( 1000, 9999 );
		\update_option( 'sisrsc_build_time', $build_time );
	}

	return $build_time;
}

/**
 * The actions to be executed when the plugin is updated.
 */
function check_version() {
	$build_time = get_build_ver();
	$db_version = \get_option( 'sirsc_db_version', 0 );
	if ( SIRSC_VER !== (float) $db_version ) {
		\update_option( 'sirsc_db_version', SIRSC_VER );
		plugin_activation();
	}
}

/**
 * The actions to be executed when the plugin is activated.
 */
function plugin_activation() {
	\set_transient( SIRSC_NOTICE, true );
	\set_transient( SIRSC_NOTICE . '_adons_notice', true );
	\delete_option( 'sirsc-iterator-buttons' );

	if ( function_exists( '\SIRSC\Debug\check_log_prefix' ) ) {
		\SIRSC\Debug\check_log_prefix();
	}
}

/**
 * The actions to be executed when the plugin is deactivated.
 */
function plugin_deactivation() {
	global $wpdb;
	\delete_option( 'sirsc_version' );

	if ( ! empty( \SIRSC::$settings['leave_settings_behind'] ) ) {
		// Cleanup only the notifications.
		admin_notices_cleanup( false );
		return;
	}

	\delete_option( 'sirsc_override_medium_size' );
	\delete_option( 'sirsc_override_large_size' );
	\delete_option( 'sirsc_admin_featured_size' );
	\delete_option( 'medium_crop' );
	\delete_option( 'medium_large_crop' );
	\delete_option( 'large_crop' );
	\delete_option( 'sirsc_use_custom_image_sizes' );
	\delete_option( 'sirsc_monitor_errors' );
	\delete_option( 'sirsc_logs_prefix' );

	$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
		'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name like %s OR option_name like %s OR option_name like %s ',
		$wpdb->esc_like( 'sirsc_settings' ) . '%',
		$wpdb->esc_like( 'sirsc_types' ) . '%',
		$wpdb->esc_like( 'sirsc_user_custom_rules' ) . '%'
	) );

	if ( ! empty( $rows ) && is_array( $rows ) ) {
		foreach ( $rows as $v ) {
			\delete_option( $v->option_name );
		}
	}

	admin_notices_cleanup( false );
}

/**
 * Mark updated settings.
 */
function mark_updated_settings() {
	\update_option( 'sirsc_settings_updated', time() );
}
