<?php
/**
 * Debug functions for SIRSC.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Debug;

\add_action( 'admin_init', __NAMESPACE__ . '\\check_log_prefix' );
\add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu', 20 );
\add_filter( 'sirsc_keep_scaled', '__return_false', 9 );
\add_action( 'sirsc_action_after_image_delete', __NAMESPACE__ . '\\debug_sirsc_action_after_image_delete' );
\add_action( 'sirsc_attachment_images_ready', __NAMESPACE__ . '\\debug_sirsc_attachment_images_ready', 10, 2 );
\add_action( 'sirsc_attachment_images_processed', __NAMESPACE__ . '\\debug_sirsc_attachment_images_processed', 10, 2 );
\add_action( 'sirsc_image_file_deleted', __NAMESPACE__ . '\\debug_sirsc_image_file_deleted', 10, 2 );
\add_action( 'sirsc_image_processed', __NAMESPACE__ . '\\debug_sirsc_image_processed', 10, 2 );
\add_filter( 'sirsc_custom_upload_rule', __NAMESPACE__ . '\\debug_sirsc_custom_upload_rule', 10, 5 );
\add_filter( 'sirsc_computed_metadata_after_upload', __NAMESPACE__ . '\\debug_sirsc_computed_metadata_after_upload', 10, 2 );

/**
 * Current log prefix.
 *
 * @return string
 */
function log_prefix() {
	return \get_option( 'sirsc_logs_prefix', '' );
}

/**
 * Returns the logs path.
 *
 * @return string
 */
function log_root(): string {
	$uploads = \wp_upload_dir();
	return ! empty( $uploads['basedir'] )
		? \trailingslashit( $uploads['basedir'] ) . 'sirsc-logs'
		: SIRSC_DIR . 'log';
}

/**
 * Check the current log prefix and attempt to create the initial index file in
 * the logs folder, then rename the existing files for security of already
 * created log files.
 */
function check_log_prefix() {
	if ( \apply_filters( 'sirsc_bypass_logs', false ) ) {
		// Short-circuit the logic.
		return;
	}

	$ver = \get_option( 'sirsc_version', 0 );
	if ( empty( $ver ) || $ver < 7.32 ) {
		\update_option( 'sirsc_version', SIRSC_VER );
		ensure_index();
	}

	if ( empty( log_prefix() ) ) {
		// First time randomize.
		ensure_index();

		// Add ridiculous random string for extra randomness.
		$random = function_exists( 'random_bytes' ) ? random_bytes( 8 ) : \wp_rand( 1000, 9999 );
		$prefix = bin2hex( (string) $random ) . md5( 'sirsc_logs_prefix' . time() ) . '_';
		\update_option( 'sirsc_logs_prefix', $prefix );

		$fs   = fs();
		$path = log_root();
		if ( $fs->is_writable( $path ) ) {
			$list = $fs->dirlist( $path, false );
			if ( ! empty( $list ) ) {
				foreach ( $list as $file ) {
					if ( 'index.php' === $file['name'] || '.htaccess' === $file['name'] ) {
						continue;
					}

					if ( ! substr_count( $file['name'], $prefix ) ) {
						// Rename existing files for backward security.
						$initial = $file['name'];
						if ( substr_count( $initial, '_' ) ) {
							$name    = explode( '_', $initial );
							$initial = $name[1];
						}
						$fs->move( $path . '/' . $file['name'], $path . '/' . $prefix . $initial, true );
					}
				}
			}
		}
	}
}

/**
 * Add the debug menu.
 */
function admin_menu() {
	if ( ! empty( \SIRSC::$settings['enable_debug_log'] ) ) {
		\add_submenu_page(
			'image-regenerate-select-crop-settings',
			\__( 'Debug', 'sirsc' ),
			\__( 'Debug', 'sirsc' ),
			'manage_options',
			'sirsc-debug',
			__NAMESPACE__ . '\\sirsc_debug'
		);
	}
}

/**
 * Debug screen content.
 */
function sirsc_debug() {
	if ( ! \current_user_can( 'manage_options' ) ) {
		// Verify user capabilities in order to deny the access if the user does not have the capabilities.
		\wp_die( \esc_html__( 'Action not allowed.', 'sirsc' ) );
	}

	ensure_index();
	?>
	<div class="wrap sirsc-settings-wrap sirsc-feature">
		<?php \SIRSC\Admin\show_plugin_top_info(); ?>
		<?php \SIRSC\Admin\maybe_all_features_tab(); ?>

		<div class="sirsc-tabbed-menu-content">
			<p><?php \esc_html_e( 'You will see here the information collected while executing regenerate and cleanup actions. Please reset the logs periodically.', 'sirsc' ); ?></p>

			<div class="as-row">
				<?php
				if ( should_handle_logs() ) {
					?>
					<div class="as-box bg-secondary">
						<div class="label-row">
							<button class="button has-icon tiny button-primary" onclick="refreshLog( 'bulk' )"><span class="dashicons dashicons-update-alt"></span></button>
							<h2><?php \esc_html_e( 'Bulk actions log', 'sirsc' ); ?></h2>
							<button class="button button-neutral" onclick="resetLog( 'bulk' )"><?php \esc_html_e( 'Reset log', 'sirsc' ); ?></button>
						</div>
						<p><?php \esc_html_e( 'The bulk actions execution results can be seen below, the most recent actions are shown at the top of the list.', 'sirsc' ); ?></p>
						<div id="sirsc-log-bulk" class="code">
							<ol><?php echo \wp_kses_post( log_read( 'bulk' ) ); ?></ol>
						</div>
					</div>

					<div class="as-box bg-secondary">
						<div class="label-row">
							<button class="button has-icon tiny button-primary" onclick="refreshLog( 'tracer' )"><span class="dashicons dashicons-update-alt"></span></button>
							<h2><?php \esc_html_e( 'Tracer log', 'sirsc' ); ?></h2>
							<button class="button button-neutral" onclick="resetLog( 'tracer' )"><?php \esc_html_e( 'Reset log', 'sirsc' ); ?></button>
						</div>
						<p><?php \esc_html_e( 'The tracer log can be seen below, the most recent events are shown at the top of the list.', 'sirsc' ); ?></p>
						<div id="sirsc-log-tracer" class="code">
							<ol><?php echo \wp_kses_post( log_read( 'tracer' ) ); ?></ol>
						</div>
						<p>
							<label class="label-row settings" onclick="refreshLogLevel( 'tracer' )">
								<input type="checkbox" name="sirsc[disable_verbose_log]"
									id="sirsc_disable_verbose_log"
									<?php \checked( true, ! empty( \SIRSC::$settings['disable_verbose_log'] ) ); ?>>
								<?php \esc_html_e( 'disable verbose mode', 'sirsc' ); ?>
							</label>
							<em>(<?php \esc_html_e( 'when enabling/disabling verbose mode, the current log will be reset', 'sirsc' ); ?>)</em>
						</p>
					</div>
					<?php
				} else {
					?>
					<div class="as-box bg-secondary small">
						<div class="label-row as-title">
							<h2><?php \esc_html_e( 'Actions log', 'sirsc' ); ?></h2>
						</div>
						<p class="sirsc-message warning"><?php \esc_html_e( 'The custom debug log for monitoring the events execution is not available currently, because your site configuration does not allow for writting files.', 'sirsc' ); ?></p>
					</div>
					<?php
				}
				?>

				<?php status(); ?>
			</div>
		</div>

		<?php \SIRSC\admin\show_donate_text(); ?>
	</div>
	<?php
}

/**
 * Outputs the system status.
 */
function status() {
	if ( ! class_exists( 'WP_Debug_Data' ) && file_exists( ABSPATH . 'wp-admin/includes/class-wp-debug-data.php' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
	}

	if ( class_exists( 'WP_Debug_Data' ) ) {
		$info = \WP_Debug_Data::debug_data();
	}
	$allow = [
		'wp-core'           => [ 'version', 'site_language', 'timezone', 'home_url', 'site_url', 'permalink', 'https_status', 'multisite', 'environment_type', 'dotorg_communication' ],
		'wp-paths-sizes'    => [ 'wordpress_path', 'uploads_path', 'themes_path', 'plugins_path' ],
		'wp-active-theme'   => [ 'name', 'version', 'author', 'author_website', 'parent_theme', 'theme_features', 'theme_path', 'auto_update' ],
		'wp-parent-theme'   => [ 'name', 'version' ],
		'wp-plugins-active' => '*',
		'wp-media'          => '*',
		'wp-server'         => '*',
		'wp-database'       => [ 'extension', 'server_version', 'client_version' ],
		'wp-constants'      => '*',
		'wp-filesystem'     => '*',
	];

	$details = '';
	if ( ! empty( $info ) ) {
		foreach ( $info as $section => $item ) {
			if ( ! empty( $allow[ $section ] ) && ! empty( $item['fields'] ) ) {
				$details .= PHP_EOL . '### ' . \esc_html( $item['label'] );
				if ( '*' === $allow[ $section ] ) {
					$keys = array_keys( $item['fields'] );
				} else {
					$keys = $allow[ $section ];
				}
				foreach ( $keys as $key ) {
					if ( isset( $item['fields'][ $key ]['label'] ) ) {
						$details .= PHP_EOL . '- '
						. \esc_html( $item['fields'][ $key ]['label'] )
						. ': ';
						if ( is_scalar( $item['fields'][ $key ]['value'] ) ) {
							$details .= \esc_html( $item['fields'][ $key ]['value'] );
						} else {
							$details .= \esc_html( print_r( $item['fields'][ $key ]['value'], true ) ); // phpcs:ignore
						}
					}
				}
				$details .= PHP_EOL;
			}
		}

		if ( isset( $info['wp-paths-sizes'] ) ) {
			$details = str_replace( $info['wp-paths-sizes']['fields']['wordpress_path']['value'], '{{ROOT}}', $details );
		}
	}

	if ( ! empty( $details ) ) {
		?>
		<div class="as-box bg-secondary">
			<div class="label-row as-title">
				<h2><?php \esc_html_e( 'Status/Debug', 'sirsc' ); ?></h2>
			</div>
			<p><?php \esc_html_e( 'Here are some details about your current instance and the services versions. These are useful for troubleshooting.', 'sirsc' ); ?></p>
			<textarea id="sirsc-sistem-status" class="code"><?php echo $details; // phpcs:ignore ?></textarea>
		</div>
		<?php
	}
}

/**
 * Debug action.
 *
 * @param int $id Attachment id.
 */
function debug_sirsc_action_after_image_delete( $id ) { // phpcs:ignore
	tracer_log_write( 'DO ACTION <b>sirsc_action_after_image_delete</b> ( ' . (int) $id . ' )' );
}

/**
 * Debug action.
 *
 * @param array $meta Attachment meta.
 * @param int   $id   Attachment ID.
 */
function debug_sirsc_attachment_images_ready( $meta, $id ) { // phpcs:ignore
	tracer_log_write( 'DO ACTION <b>sirsc_attachment_images_ready</b> ( ' . \wp_json_encode( [
		'meta' => '...',
		'id'   => $id,
	], true ) . ' )' );
}

/**
 * Debug action.
 *
 * @param array $meta Attachment meta.
 * @param int   $id   Attachment ID.
 */
function debug_sirsc_attachment_images_processed( $meta, $id ) { // phpcs:ignore
	tracer_log_write( 'DO ACTION <b>sirsc_attachment_images_processed</b> ( ' . \wp_json_encode( [
		'meta' => '...',
		'id'   => $id,
	], true ) . ' )' );
}

/**
 * Debug action.
 *
 * @param mixed $extra Extra info.
 */
function debug_sirsc_doing_sirsc( $extra ) { // phpcs:ignore
	if ( ! empty( $extra ) ) {
		tracer_log_write( 'DO ACTION <b>sirsc_doing_sirsc</b> ( ' . \wp_json_encode( $extra ) . ' )' );
	} else {
		tracer_log_write( 'DO ACTION <b>sirsc_doing_sirsc</b>' );
	}
}

/**
 * Debug action.
 *
 * @param int    $id   Attachment ID.
 * @param string $file Attachment file.
 */
function debug_sirsc_image_file_deleted( $id, $file ) { // phpcs:ignore
	tracer_log_write( 'DO ACTION <b>sirsc_image_file_deleted</b> ( ' . \wp_json_encode( [
		'id'   => $id,
		'file' => $file,
	], true ) . ' )' );
}

/**
 * Debug action.
 *
 * @param int    $id        Attachment ID.
 * @param string $size_name Image size name.
 */
function debug_sirsc_image_processed( $id, $size_name ) { // phpcs:ignore
	tracer_log_write( 'DO ACTION <b>sirsc_image_processed</b> ( ' . \wp_json_encode( [
		'id'        => $id,
		'size_name' => $size_name,
	], true ) . ' )' );
}

/**
 * Debug filter.
 *
 * @param  array  $settings    Custom settings.
 * @param  int    $id          Attachment ID.
 * @param  string $type        Post type.
 * @param  int    $parent_id   Parent ID.
 * @param  string $parent_type Parent type.
 * @return array
 */
function debug_sirsc_custom_upload_rule( $settings, $id, $type, $parent_id, $parent_type ) { // phpcs:ignore
	tracer_log_write( 'APPLY FILTER <strong>sirsc_custom_upload_rule</strong> ( ' . \wp_json_encode( [
		'settings'    => '...',
		'id'          => $id,
		'type'        => $type,
		'parent_id'   => $parent_id,
		'parent_type' => $parent_type,
	], true ) . ' )' );

	return $settings;
}

/**
 * Debug filter.
 *
 * @param  array $meta Image meta.
 * @param  int   $id   Attachment ID.
 * @return array
 */
function debug_sirsc_computed_metadata_after_upload( $meta, $id ) { // phpcs:ignore
	tracer_log_write( 'APPLY FILTER <strong>sirsc_computed_metadata_after_upload</strong> ( ' . \wp_json_encode( [
		'meta' => '...',
		'id'   => $id,
	], true ) . ' )' );
	return $meta;
}

/**
 * File system instance.
 *
 * @return object
 */
function fs() { // phpcs:ignore
	global $wp_filesystem;
	require_once ABSPATH . '/wp-admin/includes/file.php';
	\WP_Filesystem();
	return $wp_filesystem;
}

/**
 * Returns true if the logs can be handle.
 *
 * @return bool
 */
function should_handle_logs() {
	if ( \apply_filters( 'sirsc_bypass_logs', false ) ) {
		// Short-circuit the logic.
		return false;
	}

	if ( defined( 'FS_METHOD' ) && \FS_METHOD !== 'direct' ) {
		return false;
	}

	return true;
}

/**
 * Attempt to create the index file in the logs folder.
 */
function ensure_index() {
	if ( ! should_handle_logs() ) {
		return;
	}

	$fs   = fs();
	$path = log_root();

	// Attempt to create the index file.
	$file = $path . '/index.php';
	if ( ! is_file( $file ) ) {
		$fs->mkdir( $path );
		if ( $fs->is_writable( $path ) ) {
			$fs->touch( $file );
			if ( is_file( $file ) ) {
				$fs->put_contents( $file, '<?php' . PHP_EOL . '/**' . PHP_EOL . ' * Silence is golden.' . PHP_EOL . ' *' . PHP_EOL . ' * @package ic-devops' . PHP_EOL . ' */' . PHP_EOL );
			}
		}
	}

	// Attempt to create the htaccess file.
	$file = $path . '/.htaccess';
	if ( ! is_file( $file ) ) {
		$fs->mkdir( $path );
		if ( $fs->is_writable( $path ) ) {
			$fs->touch( $file );
			if ( is_file( $file ) ) {
				$fs->put_contents( $file, 'deny from all' );
			}
		}
	}

	if ( SIRSC_DIR . 'log' !== $path && $fs->is_dir( SIRSC_DIR . 'log' ) ) {
		// Remove the legacy log folder.
		$fs->rmdir( SIRSC_DIR . 'log', true );
	}
}

/**
 * Init a log file.
 *
 * @param  string $name Log type.
 */
function log_init( string $name = 'tracer' ): void {
	$fs     = fs();
	$prefix = log_prefix();
	$path   = log_root();

	$file = $path . '/' . \esc_attr( $prefix . $name ) . '.log';
	if ( ! is_file( $file ) ) {
		$fs->mkdir( $path );
		if ( $fs->is_writable( $path ) ) {
			$fs->touch( $file );
		}
	}

	$file = $path . '/' . \esc_attr( $prefix . $name ) . '-last.log';
	if ( ! is_file( $file ) ) {
		$fs->mkdir( $path );
		if ( $fs->is_writable( $path ) ) {
			$fs->touch( $file );
		}
	}
}

/**
 * Get a log file content.
 *
 * @param  string $name Log type.
 * @return string
 */
function log_read( string $name = 'tracer' ): string {
	$fs     = fs();
	$prefix = log_prefix();
	$path   = log_root();

	$file = $path . '/' . \esc_attr( $prefix . $name ) . '.log';
	if ( is_file( $file ) ) {
		$content = $fs->get_contents( $file );
		if ( 'tracer' === $name || 'bulk' === $name ) {
			$count = substr_count( $content, PHP_EOL );
			if ( $count >= 5000 ) {
				$lines   = explode( PHP_EOL, $content );
				$lines   = array_slice( $lines, 0, 2500 );
				$content = implode( PHP_EOL, $lines );
			}
		}
		return $content;
	}
	return '';
}

/**
 * Log read last trace.
 *
 * @param  string $log Log name.
 * @return string
 */
function log_read_last( string $log = 'bulk' ): string {
	$fs     = fs();
	$prefix = log_prefix();

	$file = \trailingslashit( log_root() ) . \esc_attr( $prefix . $log ) . '-last.log';
	if ( is_file( $file ) ) {
		return $fs->get_contents( $file );
	}
	return '';
}

/**
 * Log write last trace.
 *
 * @param  string $log  Log name.
 * @param  string $text Text to log.
 */
function log_write_last( string $log = 'bulk', string $text = '' ) {
	if ( ! empty( $text ) ) {
		log_init( $log );
		$fs     = fs();
		$prefix = log_prefix();
		$fs->put_contents( \trailingslashit( log_root() ) . $prefix . $log . '-last.log', $text );
	}
}

/**
 * Generic log write.
 *
 * @param string $log     Log name.
 * @param mixed  $ob      Item to trace.
 * @param bol    $prepend Prepend to the log file.
 */
function generic_log_write( $log = 'bulk', $ob = '', $prepend = true ) {
	if ( ! should_handle_logs() ) {
		// Short-circuit the logic.
		return;
	}

	if ( empty( \SIRSC::$settings['enable_debug_log'] ) ) {
		// Short-circuit the logic.
		return;
	}

	if ( ! empty( $ob ) ) {
		$prefix = log_prefix();
		log_init( $log );

		$text = log_prepare_content( $ob, 'seo-images' === $log );
		if ( skip_write_log( $text, $log ) ) {
			return;
		}

		$fs = fs();
		if ( true === $prepend ) {
			$fs->put_contents( \trailingslashit( log_root() ) . $prefix . $log . '.log', $text . log_read( $log ) );
		} else {
			$fs->put_contents( \trailingslashit( log_root() ) . $prefix . $log . '.log', log_read( $log ) . $text );
		}

		log_write_last( $log, $text );
	}
}

/**
 * Delete a log file content.
 *
 * @param string $name Log type.
 */
function log_delete( string $name = 'tracer' ) {
	$fs     = fs();
	$prefix = log_prefix();
	$path   = log_root();

	$file = $path . '/' . \esc_attr( $prefix . $name ) . '.log';
	if ( is_file( $file ) ) {
		$fs->delete( $file );
	}

	$file = $path . '/' . \esc_attr( $prefix . $name ) . '-last.log';
	if ( is_file( $file ) ) {
		$fs->delete( $file );
	}
}

/**
 * Prepare content to be put to log.
 *
 * @param  mixed $ob         Content to be put to log.
 * @param  bool  $no_changes Prepare or not the string.
 * @return string
 */
function log_prepare_content( $ob, bool $no_changes = false ) { // phpcs:ignore
	if ( ! empty( $ob ) ) {
		$upl_dir = \wp_upload_dir();
		$path    = $upl_dir['basedir'];
		$path    = str_replace( chr( 93 ), '/', $path );
		if ( true === $no_changes ) {
			$ob_text = $ob . PHP_EOL;
		} else {
			$ob_text  = '<li><em>' . gmdate( 'Y-m-d H:i:s' ) . '</em>' . PHP_EOL;
			$ob_text .= ( ! is_scalar( $ob ) ) ? \wp_json_encode( $ob ) : $ob; // phpcs:ignore
			$ob_text .= '</li>' . PHP_EOL;
			$ob_text  = str_replace( '|', ' | ', $ob_text );
			$ob_text  = str_replace( '\/', '/', $ob_text );
			$ob_text  = str_replace( $path, '{{UPLOADS}}', $ob_text );
		}

		return $ob_text;
	}
	return '';
}

/**
 * Write content to log.
 *
 * @param mixed $ob Content to be put to log.
 */
function main_log_write( $ob ) { // phpcs:ignore
	generic_log_write( 'main', $ob );
}

/**
 * Write content to log.
 *
 * @param mixed $ob Content to be put to log.
 */
function bulk_log_write( $ob ) { // phpcs:ignore
	generic_log_write( 'bulk', $ob );
}

/**
 * Write content to log.
 *
 * @param mixed $ob Content to be put to log.
 */
function tracer_log_write( $ob ) { // phpcs:ignore
	generic_log_write( 'tracer', $ob );
}

/**
 * Write content to log.
 *
 * @param mixed $ob Content to be put to log.
 */
function bulk_rename_log_write( $ob ) { // phpcs:ignore
	generic_log_write( 'seo-images', $ob, false );
}

/**
 * Write content to log.
 *
 * @param  string $text Content to be put to log.
 * @param  string $log  Log name.
 * @return bool
 */
function skip_write_log( $text, $log = 'bulk' ) {
	if ( 'tracer' !== $log ) {
		// Fail-fast, not the tracer log.
		return false;
	}

	$settings   = \get_option( 'sirsc_settings' );
	$is_verbose = empty( $settings['debug_log_verbose'] );
	$last       = log_read_last( 'tracer' );

	if ( $last === $text ) {
		// Avoid redundant lines.
		return true;
	}

	if ( substr_count( $text, 'DO ACTION ' ) ) {
		return false;
	}

	if ( $is_verbose ) {
		return false;
	}

	return true;
}
