<?php // phpcs:ignore
/**
 * SIRSC admin functionality.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Admin;

// Hook up the custom menu.
\add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );
\add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\load_assets' );

// Intitialize Gutenberg filters.
\add_action( 'init', __NAMESPACE__ . '\\sirsc_block_init' );

// Hook up the custom media settings.
\add_action( 'admin_init', __NAMESPACE__ . '\\media_settings_override' );
\add_action( 'init', __NAMESPACE__ . '\\custom_media_settings_overrides', 30, 3 );
\add_action( 'add_meta_boxes', __NAMESPACE__ . '\\register_image_meta', 10, 3 );
\add_filter( 'manage_media_columns', __NAMESPACE__ . '\\register_media_columns', 5 );
\add_action( 'manage_media_custom_column', __NAMESPACE__ . '\\media_column_value', 5, 2 );
\add_action( 'wp_enqueue_media', __NAMESPACE__ . '\\add_media_overrides' );

/**
 * Add media overrides.
 */
function add_media_overrides() { // phpcs:ignore
	if ( ! empty( \SIRSC::$settings['media_grid_buttons'] ) ) {
		\add_action( 'admin_footer-upload.php', __NAMESPACE__ . '\\override_media_templates' );
		\add_action( 'admin_footer-post.php', __NAMESPACE__ . '\\override_media_details_templates' );
		\add_action( 'admin_footer-post-new.php', __NAMESPACE__ . '\\override_media_details_templates' );
	}
}

/**
 * Media overrides.
 */
function override_media_templates() {
	include_once __DIR__ . '/parts/media-template-1.php';
	include_once __DIR__ . '/parts/media-template-3.php';
}

/**
 * Media overrides.
 */
function override_media_details_templates() {
	include_once __DIR__ . '/parts/media-template-2.php';
}

/**
 * Templates buttons.
 */
function media_template_buttons() {
	?>
	<div id="sirsc-buttons-wrapper-box<?php echo \esc_html( time() . \wp_rand( 1000, 9999 ) ); ?>-{{ data.id }}" class="sirsc-feature as-target sirsc-buttons tiny" data-id="{{ data.id }}">
		<button class="button has-icon button-primary tiny" onclick="sirscSingleDetails('{{ data.id }}')" title="{{ sirscSettings.button_options }}"><div class="dashicons dashicons-format-gallery"></div> {{ sirscSettings.button_details }}</button>
		<button class="button has-icon button-primary tiny" onclick="sirscSingleRegenerate('{{ data.id }}')" title="{{ sirscSettings.button_regenerate }}"><div class="dashicons dashicons-update"></div> {{ sirscSettings.button_regenerate }}</button>
		<button class="button has-icon button-primary tiny is-cleanup" onclick="sirscSingleCleanup('{{ data.id }}')" title="{{ sirscSettings.button_cleanup }}"><div class="dashicons dashicons-editor-removeformatting"></div> {{ sirscSettings.button_cleanup }}</button>
	</div>
	<?php
}


/**
 * Return the svg logo of the plugin.
 *
 * @param  bool $content Return as content.
 * @return string
 */
function get_sirsc_logo( $content = false ): string {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="shape-rendering:geometricPrecision;text-rendering:geometricPrecision;image-rendering:optimizeQuality;fill-rule:evenodd;clip-rule:evenodd" viewBox="0 0 2541 2541"><path fill="currentColor" d="M173 0h1399c-42 139-50 303 7 479l228 66c-13-39-25-91-33-134l-4-131c91 90 334 354 406 386 61 27 177 23 245 0l-92-92C1916 159 1786 80 1797 0h175l569 569v173c-372 172-744-159-1241-199-624-50-855 427-729 944l220 68c-3-67-22-100-22-171 108 104 300 374 556 292l-573-574 65-151 771 773C1228 2012 559 1417 0 1574V173C0 78 78 0 173 0zm2067 0h128c95 0 173 78 173 173v131L2240 0zm301 970v1398c0 95-78 173-173 173H967c42-139 50-303-8-479l-227-66c12 39 24 91 32 135l5 131c-264-262-379-478-651-387l92 93c413 415 542 493 531 573H566L0 1975v-177c371-169 743 160 1239 200 623 50 855-426 729-944l-220-67c2 66 21 99 22 170-109-104-300-374-557-291l573 574-64 150-772-772c142-114 417-68 576-25 208 55 365 142 574 180 145 26 287 45 441-3zM298 2541H173c-95 0-173-78-173-173v-127l298 300z"/></svg>';

	if ( ! empty( $content ) ) {
		return $svg;
	}
	return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore
}

/**
 * Outputs the custom plugin icon.
 */
function the_plugin_icon() {
	?>
	<img class="sirsc-icon-svg" width="32" height="32"
		src="<?php echo \esc_url( SIRSC_URL . 'assets/images/icon.svg' ); ?>"
		alt="<?php \esc_html_e( 'Image Regenerate & Select Crop', 'sirsc' ); ?>">
	<?php
}

/**
 * Add the new menu in tools section that allows to configure the image sizes restrictions.
 */
function admin_menu() {
	\add_menu_page(
		\__( 'Image Regenerate & Select Crop', 'sirsc' ),
		\__( 'Image Regenerate & Select Crop', 'sirsc' ),
		'manage_options',
		'image-regenerate-select-crop-settings',
		__NAMESPACE__ . '\\image_regenerate_select_crop_settings',
		'dashicons-admin-plugins',
		70
	);
	\add_submenu_page(
		'image-regenerate-select-crop-settings',
		\__( 'Advanced Rules', 'sirsc' ),
		\__( 'Advanced Rules', 'sirsc' ),
		'manage_options',
		'image-regenerate-select-crop-rules',
		__NAMESPACE__ . '\\sirsc_custom_rules_settings'
	);
	\add_submenu_page(
		'image-regenerate-select-crop-settings',
		\__( 'Media Settings', 'sirsc' ),
		\__( 'Media Settings', 'sirsc' ),
		'manage_options',
		\admin_url( 'options-media.php#images-custom-settings' )
	);
	\add_submenu_page(
		'image-regenerate-select-crop-settings',
		\__( 'Additional Sizes', 'sirsc' ),
		\__( 'Additional Sizes', 'sirsc' ),
		'manage_options',
		\admin_url( 'options-media.php#images-custom-subsizes' )
	);
}

/**
 * Registers the Gutenberg custom block assets.
 */
function sirsc_block_init() {
	if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
	}

	$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore

	if ( ! substr_count( $uri, 'post.php' )
		&& ! substr_count( $uri, 'post-new.php' )
		&& ! substr_count( $uri, 'upload.php' )
		&& ! substr_count( $uri, 'page=image-regenerate-select-crop-' )
		&& ! substr_count( $uri, 'page=sirsc-adon-' )
		&& ! substr_count( $uri, 'page=sirsc-debug' )
		&& ! substr_count( $uri, 'options-media.php' ) ) {

		// Fail-fast, the assets should not be loaded.
		return;
	}

	\wp_register_script(
		'sirsc-block-editor',
		SIRSC_URL . 'build/block.js',
		[
			'wp-blocks',
			'wp-editor',
			'wp-i18n',
			'wp-element',
			'wp-hooks',
		],
		\SIRSC\get_build_ver(),
		true
	);

	\register_block_type( 'sirsc/sirsc-block', [
		'editor_script' => 'sirsc-block-editor',
	] );
}

/**
 * Register custom settings for overriding the native image
 * sizes and define new custom ones.
 */
function media_settings_override() {
	// Add the custom section to media.
	\add_settings_section(
		'sirsc_override_section',
		'<a name="images-custom-settings" id="images-custom-settings"></a>',
		__NAMESPACE__ . '\\sirsc_override_section_callback',
		'media'
	);

	// Add the custom section to media.
	\add_settings_section(
		'sirsc_custom_sizes_section',
		'<a name="images-custom-subsizes" id="images-custom-subsizes"></a>',
		__NAMESPACE__ . '\\sirsc_custom_sizes_section_callback',
		'media'
	);
}

/**
 * Make preset colors tokens.
 *
 * @return string
 */
function make_preset_colors_tokens() {
	global $_wp_admin_css_colors;

	$user_id      = \get_current_user_id();
	$color_scheme = \get_user_option( 'admin_color', $user_id );

	$colors = $_wp_admin_css_colors[ $color_scheme ]->colors ?? [];
	$icons  = $_wp_admin_css_colors[ $color_scheme ]->icon_colors ?? [];
	$main   = $colors[2] ?? '#2271b1';
	$focus  = $colors[3] ?? '#72aee6';
	$icon   = $icons['base'] ?? '#a7aaad';
	if ( 'light' === $color_scheme ) {
		$main = $colors[3] ?? '#2271b1';
	} elseif ( 'modern' === $color_scheme ) {
		$main = $colors[1] ?? '#2271b1';
	} elseif ( 'blue' === $color_scheme ) {
		$main = '#e1a948';
	} elseif ( 'midnight' === $color_scheme ) {
		$main = $colors[3] ?? '#2271b1';
	}

	$style = '
	:root {
		--sirsc-color-main: ' . $main . ';
		--sirsc-color-focus: ' . $focus . ';
		--sirsc-color-icon: ' . $icon . ';
		--sirsc-color-faded: ' . $main . '25;
	}';

	// Return the minified string.
	$style = ! empty( $style ) ? trim( preg_replace( '/\s\s+/', ' ', $style ) ) : '';
	return $style;
}

/**
 * Enqueue the css and javascript files
 */
function load_assets() {
	$ver = \SIRSC\get_build_ver();
	if ( file_exists( SIRSC_DIR . 'build/admin.css' ) ) {
		\wp_enqueue_style( SIRSC_SLUG . '-admin', SIRSC_URL . 'build/admin.css', [], $ver, false );
		\wp_add_inline_style( SIRSC_SLUG . '-admin', make_preset_colors_tokens() );
	}

	$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore

	if ( ! substr_count( $uri, 'post.php' )
		&& ! substr_count( $uri, 'post-new.php' )
		&& ! substr_count( $uri, 'upload.php' )
		&& ! substr_count( $uri, 'page=image-regenerate-select-crop-' )
		&& ! substr_count( $uri, 'page=sirsc-adon-' )
		&& ! substr_count( $uri, 'page=sirsc-debug' ) ) {

		// Fail-fast, the assets should not be loaded.
		return;
	}

	if ( file_exists( SIRSC_DIR . 'build/index.js' ) ) {
		$upls = \wp_upload_dir();

		\wp_register_script( SIRSC_SLUG, SIRSC_URL . 'build/index.js', [], $ver, true );
		\wp_localize_script( SIRSC_SLUG, str_replace( '-', '', SIRSC_SLUG ) . 'Settings', [
			'ajaxUrl'                => \admin_url( 'admin-ajax.php' ),
			'verify'                 => \wp_create_nonce( 'sirsc-ajax-actions' ),
			'confirm_cleanup'        => \__( 'Cleanup all?', 'sirsc' ),
			'confirm_regenerate'     => \__( 'Regenerate all?', 'sirsc' ),
			'time_warning'           => \__( 'This operation might take a while, depending on how many images you have.', 'sirsc' ),
			'irreversible_operation' => \__( 'The operation is irreversible!', 'sirsc' ),
			'resolution'             => \__( 'Resolution', 'sirsc' ),
			'button_options'         => \__( 'Details/Options', 'sirsc' ),
			'button_details'         => \__( 'Image Details', 'sirsc' ),
			'button_regenerate'      => \__( 'Regenerate', 'sirsc' ),
			'button_cleanup'         => \__( 'Raw Cleanup', 'sirsc' ),
			'regenerate_log_title'   => \__( 'Regenerate Log', 'sirsc' ),
			'cleanup_log_title'      => \__( 'Cleanup Log', 'sirsc' ),
			'upload_root_path'       => \trailingslashit( $upls['basedir'] ),
			'display_small_buttons'  => ( ! empty( \SIRSC::$settings['listing_tiny_buttons'] ) ) ? ' tiny' : '',
			'admin_featured_size'    => \get_option( 'sirsc_admin_featured_size' ),
			'confirm_raw_cleanup'    => \__( 'This action will remove all images generated for this attachment, except for the original file. Are you sure you want proceed?', 'sirsc' ),
			'delay'                  => \SIRSC::BULK_PROCESS_DELAY,
			'settting_url'           => \admin_url( 'admin.php?page=image-regenerate-select-crop-settings' ),
		] );
		\wp_enqueue_script( SIRSC_SLUG );
	}

	if ( file_exists( SIRSC_DIR . 'build/style-view.css' ) ) {
		\wp_enqueue_style( SIRSC_SLUG, SIRSC_URL . 'build/style-view.css', [], $ver, false );
	}
}

/**
 * Maybe all features tab.
 */
function maybe_all_features_tab() {
	$tab = filter_input( INPUT_GET, 'page', FILTER_DEFAULT );
	?>
	<div class="intro-next outside menu-wrap">
		<div class="tabs-container">
			<div class="tabs-wrap" tabindex="0">
				<?php
				foreach ( \SIRSC::$menu_items as $item ) {
					$class  = $item['slug'] === $tab ? ' button-primary on' : '';
					$class .= ! empty( $item['icon'] ) ? ' has-icon' : '';
					?>
					<a href="<?php echo \esc_url( $item['url'] ); ?>"
						class="button<?php echo \esc_attr( $class ); ?>">
						<?php echo ! empty( $item['icon'] ) ? \wp_kses_post( $item['icon'] ) : ''; ?>
						<?php echo \esc_html( $item['title'] ); ?>
					</a>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Show info icon.
 *
 * @param string $id        Element id.
 * @param string $css_class Extra class.
 */
function the_info_icon( $id, $css_class = '' ) {
	?>
	<button class="dashicons dashicons-info <?php echo \esc_attr( $css_class ); ?>" tabindex="0" title="<?php \esc_attr_e( 'Details', 'sirsc' ); ?>" data-sirsc-toggle="<?php echo \esc_attr( $id ); ?>"></button>
	<?php
}

/**
 * Show info text.
 *
 * @param string $id   Element id.
 * @param string $text Element text.
 */
function the_info_text( $id, $text = '' ) {
	?>
	<div id="<?php echo \esc_attr( $id ); ?>" class="sirsc_info_box" data-sirsc-toggle="<?php echo \esc_attr( $id ); ?>">
		<div><?php echo \wp_kses_post( $text ); ?></div>
	</div>
	<?php
}

/**
 * Show plugin top info.
 */
function show_plugin_top_info() {
	?>
	<h1 class="plugin-title">
		<?php the_plugin_icon(); ?>
		<span class="h1"><?php \esc_html_e( 'Image Regenerate & Select Crop', 'sirsc' ); ?></span>
	</h1>
	<?php
}

/**
 * The setting is readonly.
 *
 * @param string $slug Setting slug.
 */
function setting_is_readonly( $slug ) { // phpcs:ignore
	$cpt = filter_input( INPUT_GET, '_sirsc_post_types', FILTER_DEFAULT );
	if ( empty( $cpt ) ) {
		// Fail-fast.
		return;
	}

	$list = \SIRSC::common_settings();
	if ( in_array( $slug, $list['list'], true ) ) {
		echo ' readonly="readonly" disabled="disabled" ';
	}
}

/**
 * The setting has custom background.
 */
function has_custom_color() {
	$cpt = filter_input( INPUT_GET, '_sirsc_post_types', FILTER_DEFAULT );
	if ( empty( $cpt ) ) {
		// Fail-fast.
		return;
	}

	$color = \SIRSC\Helper\string2color( $cpt );
	echo ' data-custom-color="true" style="padding: 10px; border-left: 0.25rem solid ' . \esc_attr( $color ) . ' !important; background: ' . \esc_attr( $color ) . '10 !important; "'; // phpcs:ignore
}

/**
 * Functionality to manage the image regenerate & select crop settings.
 */
function image_regenerate_select_crop_settings() {
	if ( ! \current_user_can( 'manage_options' ) ) {
		// Verify user capabilities in order to deny the access if the user does not have the capabilities.
		\wp_die( \esc_html__( 'Action not allowed.', 'sirsc' ) );
	}

	$allow_html = [
		'table' => [
			'class'       => [],
			'cellspacing' => [],
			'cellpadding' => [],
			'title'       => [],
		],
		'tbody' => [],
		'tr'    => [],
		'td'    => [ 'title' => [] ],
		'label' => [],
		'input' => [
			'type'              => [],
			'name'              => [],
			'id'                => [],
			'value'             => [],
			'checked'           => [],
			'onchange'          => [],
			'onclick'           => [],
			'data-sirsc-toggle' => [],
		],
	];

	$post_types        = \SIRSC\Helper\get_all_post_types_plugin();
	$_sirsc_post_types = filter_input( INPUT_GET, '_sirsc_post_types', FILTER_DEFAULT );
	\SIRSC::$settings  = \SIRSC::prepare_settings_list();
	$settings          = \SIRSC::$settings;

	$default_plugin_settings = $settings;
	if ( ! empty( $_sirsc_post_types ) ) {
		$settings = \SIRSC::prepare_settings_list( $_sirsc_post_types );
	}
	?>
	<div class="wrap sirsc-settings-wrap sirsc-feature">
		<?php show_plugin_top_info(); ?>
		<?php maybe_all_features_tab(); ?>
		<div class="sirsc-tabbed-menu-content">
			<p>
				<?php \esc_html_e( 'Please make sure you visit and update your settings here whenever you activate a new theme or plugins, so that the new image size registered, adjusted or removed to be reflected also here, and in this way to assure the optimal behavior for the features of this plugin.', 'sirsc' ); ?> <span class="dashicons dashicons-image-crop"></span> <a href="<?php echo \esc_url( \admin_url( 'options-media.php' ) ); ?>#images-custom-settings"><?php \esc_html_e( 'Images Custom Settings', 'sirsc' ); ?></a> <span class="dashicons dashicons-format-gallery"></span> <a href="<?php echo \esc_url( \admin_url( 'options-media.php' ) ); ?>#images-custom-subsizes"><?php \esc_html_e( 'Define Custom Image Sizes', 'sirsc' ); ?></a>
			</p>

			<div class="sirsc-image-generate-functionality">
				<form id="sirsc_settings_frm" name="sirsc_settings_frm" action="" method="post">
					<?php \wp_nonce_field( '_sirsc_settings_save', '_sirsc_settings_nonce' ); ?>
					<?php include_once __DIR__ . '/parts/admin-reset.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-placeholder.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-post-types.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-general.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-cron.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-other-settings.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-cleanup.php'; ?>
					<?php include_once __DIR__ . '/parts/admin-sizes-table.php'; ?>
				</form>
			</div>
		</div>
		<?php show_donate_text(); ?>
	</div>
	<?php
}

/**
 * Compute image size readable info from settings.
 *
 * @param string $k    Image size slug.
 * @param array  $info Settings array.
 */
function get_usable_info( $k, $info ) { // phpcs:ignore
	$data = [
		'is_ignored'     => ( ! empty( $info['complete_global_ignore'] ) && in_array( $k, $info['complete_global_ignore'], true ) ) ? 1 : 0,
		'is_checked'     => ( ! empty( $info['exclude'] ) && in_array( $k, $info['exclude'], true ) ) ? 1 : 0,
		'is_unavailable' => ( ! empty( $info['unavailable'] ) && in_array( $k, $info['unavailable'], true ) ) ? 1 : 0,
		'is_forced'      => ( ! empty( $info['force_original_to'] ) && $k === $info['force_original_to'] ) ? 1 : 0,
		'has_crop'       => ( ! empty( $info['default_crop'][ $k ] ) ) ? $info['default_crop'][ $k ] : 'cc',
		'quality'        => ( ! empty( $info['default_quality'][ $k ] ) ) ? (int) $info['default_quality'][ $k ] : \SIRSC::DEFAULT_QUALITY,
		'line_class'     => '',
	];

	$data['quality']     = ( empty( $data['quality'] ) ) ? \SIRSC::DEFAULT_QUALITY : $data['quality'];
	$data['line_class'] .= ( ! empty( $data['is_ignored'] ) ) ? ' _sirsc_ignored' : '';
	$data['line_class'] .= ( ! empty( $data['is_forced'] ) ) ? ' _sirsc_force_original' : '';
	$data['line_class'] .= ( empty( $data['is_checked'] ) ) ? ' _sirsc_included' : '';
	return $data;
}

/**
 * Returns the transient id based on type, post type, sub-size, and featured.
 *
 * @param  string $type Transient type.
 * @param  string $cpt  Post type.
 * @param  string $size Sub-size.
 * @return string
 */
function get_count_trans_name( $type = 'cleanup', $cpt = '', $size = '' ) {
	$only = ! empty( \SIRSC::$settings['regenerate_only_featured'] ) ? 1 : 0;
	return 'sirsc-count-' . md5( $type . '-' . $cpt . '-' . $size . '-' . $only );
}

/**
 * Returns the number if images of "image size name" that can be clean up for a specified post type if is set, or the global number of images that can be clean up for the "image size name".
 *
 * @param string $post_type       The post type.
 * @param string $image_size_name The size slug.
 * @param int    $next_post_id    The next post to be processed.
 */
function calculate_total_to_cleanup( $post_type = '', $image_size_name = '', $next_post_id = 0 ) { // phpcs:ignore
	global $wpdb;

	$only     = ! empty( \SIRSC::$settings['regenerate_only_featured'] ) ? 1 : 0;
	$trans_id = get_count_trans_name( 'cleanup', $post_type, $image_size_name );
	$total    = \get_transient( $trans_id );
	if ( false === $total ) {
		$total = 0;
		if ( ! empty( $image_size_name ) ) {
			$cond_join  = '';
			$cond_where = '';
			if ( ! empty( $post_type ) ) {
				$cond_join  = ' INNER JOIN ' . $wpdb->posts . ' as parent ON( parent.ID = p.post_parent )';
				$cond_where = $wpdb->prepare( ' AND parent.post_type = %s ', $post_type );
			}
			if ( ! empty( \SIRSC::$settings['regenerate_only_featured'] ) ) {
				$cond_join .= ' INNER JOIN ' . $wpdb->postmeta . ' as pm2 ON (pm2.meta_value = p.ID and pm2.meta_key = \'_thumbnail_id\' ) ';
			}

			// phpcs:disable
			$tmp_query = $wpdb->prepare( '
				SELECT p.ID FROM ' . $wpdb->posts . ' as p
				INNER JOIN ' . $wpdb->postmeta . ' as pm ON(pm.post_id = p.ID and pm.meta_key like %s)
				' . $cond_join . ' WHERE pm.meta_value like %s AND p.ID > %d AND p.post_mime_type like %s
				' . $cond_where . ' order by p.ID limit 0, 1', // phpcs:ignore
				'_wp_attachment_metadata',
				'%' . $wpdb->esc_like( '"' . $image_size_name . '"' ) . '%',
				intval( $next_post_id ),
				$wpdb->esc_like( 'image/' ) . '%'
			);

			$total = $wpdb->get_var( $tmp_query ) ?? 0;
			// phpcs:enable
		}

		\set_transient( $trans_id, $total, DAY_IN_SECONDS );
	}

	return (int) $total;
}

/**
 * Set regenerate last processed id.
 *
 * @param string $name Image size name.
 * @param int    $id   Post ID.
 */
function set_regenerate_last_processed_id( $name = '', $id = 0 ) { // phpcs:ignore
	\update_option( 'sirsc_regenerate_most_recent_' . \esc_attr( $name ), $id );
}

/**
 * Remove regenerate last processed id.
 *
 * @param string $name Image size name.
 */
function remove_regenerate_last_processed_id( $name = '' ) { // phpcs:ignore
	\delete_option( 'sirsc_regenerate_most_recent_' . \esc_attr( $name ) );
}

/**
 * Donate text.
 *
 * @return string
 */
function donate_text() {
	$thanks  = \esc_html__( 'A huge thanks in advance!', 'sirsc' );
	$rating  = '<a href="' . \esc_url( SIRSC_SUPPORT . 'reviews/?rate=5#new-post' ) . '" class="rating" target="_blank" rel="noreferrer" title="' . $thanks . '">★★★★★</a>';
	$support = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . rawurlencode( SIRSC_NAME ) . ')';

	return sprintf(
		// Translators: %1$s - donate URL, %2$s - rating.
		\__( 'If you find the plugin useful and would like to support my work, please consider making a <a href="%1$s" target="_blank" rel="noreferrer">donation</a>. It would make me very happy if you would leave a %2$s rating.', 'sirsc' ),
		$support,
		$rating
	) . ' ' . $thanks;
}

/**
 * Maybe donate or rate.
 */
function show_donate_text() {
	if ( \apply_filters( 'sirsc_filter_remove_top_info', false ) ) {
		return;
	}
	?>
	<div class="donate">
		<img src="<?php echo \esc_url( SIRSC_URL . 'assets/images/icon-128x128.gif' ); ?>" width="32" height="32" alt="">
		<div><?php echo \wp_kses_post( donate_text() ); ?></div>
	</div>
	<?php
}

/**
 * Output the admin success message for email test sent.
 */
function on_settings_update_notice() {
	$class   = 'notice notice-success is-dismissible';
	$message = \__( 'The plugin settings have been updated successfully.', 'sirsc' );
	printf( '<div class="%1$s"><p>%2$s</p></div>', \esc_attr( $class ), \esc_html( $message ) );
}

/**
 * Append the image sizes generator button to the edit media page.
 */
function register_image_meta() {
	global $post;
	if ( ! empty( $post->post_type ) && 'attachment' === $post->post_type ) {
		\add_action( 'edit_form_top', __NAMESPACE__ . '\\append_image_generate_button_tiny', 10, 2 );
	}
}

/**
 * Append or display the button for generating the missing image sizes and request individual crop of images.
 *
 * @param string $content      The button content.
 * @param int    $post_id      The main post ID.
 * @param int    $thumbnail_id The attachemnt ID.
 * @param string $extra_class  The wrapper extra class.
 */
function append_image_generate_button_tiny( $content, $post_id = 0, $thumbnail_id = 0, $extra_class = '' ) { // phpcs:ignore
	return append_image_generate_button( $content, $post_id, $thumbnail_id, 'tiny' );
}

/**
 * Append or display the button for generating the missing image sizes and
 * request individual crop of images.
 *
 * @param string $content      The button content.
 * @param int    $post_id      The main post ID.
 * @param int    $thumbnail_id The attachemnt ID.
 * @param string $extra_class  The wrapper extra class.
 */
function append_image_generate_button( $content, $post_id = 0, $thumbnail_id = 0, $extra_class = '' ) { // phpcs:ignore
	$content_button    = '';
	$display           = false;
	$is_the_attachment = false;
	if ( is_object( $content ) ) {
		$thumbnail_id = $content->ID;
		$display      = ! empty( $content->post_mime_type ) && substr_count( $content->post_mime_type, 'image/' );

		$is_the_attachment = true;
	}

	if ( ! empty( $post_id ) || ! empty( $thumbnail_id ) ) {
		if ( ! empty( $thumbnail_id ) ) {
			$thumb_id = (int) $thumbnail_id;
		} else {
			$thumb_id = (int) \get_post_thumbnail_id( $post_id );
		}

		\SIRSC::load_settings_for_post_id( $thumb_id );
		if ( ! empty( $thumb_id ) ) {
			$extra_class   .= ! empty( \SIRSC::$settings['listing_tiny_buttons'] ) ? ' tiny' : '';
			$extra_class    = str_replace( 'tiny tiny', 'tiny', $extra_class );
			$content_button = '<div id="sirsc-buttons-wrapper-' . $thumb_id . '" class="sirsc-feature as-target sirsc-buttons ' . $extra_class . '">' . \SIRSC\Helper\make_buttons( $thumb_id, true ) . '</div>';
		}

		if ( ! $is_the_attachment && empty( $thumbnail_id ) ) {
			$content_button = '';
		}

		if ( ! $is_the_attachment ) {
			$content = $content_button . $content;
		}
	}

	if ( true === $display && true === $is_the_attachment ) {
		// When the button is in the attachment edit screen, we display the buttons.
		echo '<div class="sirsc_button-regenerate-wrap">' . $content_button . '</div>'; // phpcs:ignore
	}

	return $content;
}

/**
 * Describe the override settings section.
 */
function sirsc_override_section_callback() {
	?>
	<hr>
	<h2 class="title"><?php \esc_html_e( 'Images Custom Settings', 'sirsc' ); ?></h2>
	<p><?php \esc_html_e( 'You can override the default crop for the medium and large size of the images. Please note that the crop will apply to the designated image size only if it has both with and height defined (as you know, when you set 0 to one of the sizes, the image will be scaled proportionally, hence, the crop cannot be applied).', 'sirsc' ); ?></p>

	<table class="form-table" width="100%">
		<tbody>
			<tr>
				<?php
				$checked = \get_option( 'sirsc_override_medium_size' );
				$crop    = \get_option( 'medium_crop' );
				$checked = ( 1 === (int) $crop && 1 === (int) $checked ) ? 1 : 0;
				?>
				<td width="19%">
					<label>
						<input name="sirsc_override_medium_size" id="sirsc_override_medium_size" type="checkbox" value="1" class="code" <?php \checked( 1, $checked ); ?>>
						<b><?php \esc_html_e( 'Medium size crop', 'sirsc' ); ?></b>
					</label>
				</td>
				<td colspan="2"><?php \esc_html_e( 'Crop the image to exact dimensions (normally images are proportional).', 'sirsc' ); ?></td>
			</tr>
			<tr>
				<?php
				$checked = \get_option( 'sirsc_override_medium_large_size' );
				$crop    = \get_option( 'medium_large_crop' );
				$checked = ( 1 === (int) $crop && 1 === (int) $checked ) ? 1 : 0;
				?>
				<td>
					<label>
						<input name="sirsc_override_medium_large_size" id="sirsc_override_medium_large_size" type="checkbox" value="1" class="code" <?php \checked( 1, $checked ); ?>>
						<b><?php \esc_html_e( 'Medium large size crop', 'sirsc' ); ?></b>
					</label>
				</td>
				<td colspan="2"><?php \esc_html_e( 'Crop the image to exact dimensions (normally images are proportional).', 'sirsc' ); ?></td>
			</tr>
			<tr>
				<?php
				$checked = \get_option( 'sirsc_override_large_size' );
				$crop    = \get_option( 'large_crop' );
				$checked = ( 1 === (int) $crop && 1 === (int) $checked ) ? 1 : 0;
				?>
				<td>
					<label>
						<input name="sirsc_override_large_size" id="sirsc_override_large_size" type="checkbox" value="1" class="code" <?php \checked( 1, $checked ); ?>>
						<b><?php \esc_html_e( 'Large size crop', 'sirsc' ); ?></b>
					</label>
				</td>
				<td colspan="2"><?php \esc_html_e( 'Crop the image to exact dimensions (normally images are proportional).', 'sirsc' ); ?></td>
			</tr>
			<tr>
				<?php
				$checked   = \get_option( 'sirsc_admin_featured_size' );
				$all_sizes = \SIRSC::get_all_image_sizes_plugin();
				?>
				<td><b><?php \esc_html_e( 'Featured image size in meta box', 'sirsc' ); ?></b></td>
				<td>
					<select name="sirsc_admin_featured_size" id="sirsc_admin_featured_size" style="width: 100%">
						<option value=""></option>
						<?php foreach ( $all_sizes as $size => $prop ) : ?>
							<option value="<?php echo \esc_attr( $size ); ?>"<?php \selected( \esc_attr( $size ), $checked ); ?>><?php echo \esc_attr( $size ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td>
					<?php \esc_html_e( 'This setting allows you to change the post thumbnail image size that is displayed in the meta box. Leave empty if you want to use the default image size that is set by WordPress and your theme.', 'sirsc' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Expose the custom media settings.
 */
function sirsc_custom_sizes_section_callback() {
	$sizes = \maybe_unserialize( \get_option( 'sirsc_use_custom_image_sizes' ) );
	if ( empty( $sizes ) ) {
		$sizes = [
			'sizes'  => [],
			'number' => 0,
		];
	}

	\wp_nonce_field( 'sirsc_media_settings_save', 'sirsc_media_settings_nonce' );
	?>
	<hr>
	<h2 class="title"><?php \esc_html_e( 'Define Custom Image Sizes', 'sirsc' ); ?></h2>
	<p>
		<?php \esc_html_e( 'If you decided it is absolutely necessary to have new custom image sizes, you can make the setup below and these will be registered programmatically in your application if you configured these correctly (you have to input the size name and at least the width or height).', 'sirsc' ); ?>
		<b><?php \esc_html_e( 'However, please make sure you only define these below if you are sure this is really necessary, as, any additional image size registered in your application is decreasing the performance on the images upload processing and also creates extra physical files on your hosting.', 'sirsc' ); ?></b>
		<?php \esc_html_e( 'Also, please note that changing the image sizes names or width and height values is not recommended after these were defined and your application started to create images for these specifications.', 'sirsc' ); ?>
	</p>
	<p>
		<b><?php \esc_html_e( 'Use this feature wisely.', 'sirsc' ); ?></b>
		<?php \esc_html_e( 'Please consult with a front-end developer before deciding to define more image sizes below (and in general in the application), as most of the times just updating the native image sizes settings and updating the front-end code (the theme) is enough.', 'sirsc' ); ?>
	</p>
	<table class="form-table" width="100%">
		<tbody>
			<thead>
				<tr>
					<td colspan="2"><b><?php \esc_attr_e( 'Image Sizes Name', 'sirsc' ); ?></b></td>
					<td width="10%"><b><?php \esc_attr_e( 'Max Width', 'sirsc' ); ?></b></td>
					<td width="10%"><b><?php \esc_attr_e( 'Max Height', 'sirsc' ); ?></b></td>
					<td><b><?php \esc_attr_e( 'Crop', 'sirsc' ); ?></b></td>
				</tr>
			</thead>
			<?php
			$counter = 0;
			if ( ! empty( $sizes['sizes'] ) ) {
				foreach ( $sizes['sizes'] as $i => $item ) {
					$name = ( ! empty( $item['name'] ) ) ? $item['name'] : '';
					if ( empty( $name ) ) {
						continue;
					}

					++$counter;

					$width  = ( ! empty( $item['width'] ) ) ? (int) $item['width'] : 0;
					$height = ( ! empty( $item['height'] ) ) ? (int) $item['height'] : 0;
					$crop   = ( ! empty( $item['crop'] ) ) ? (int) $item['crop'] : 0;
					?>
					<tr>
						<td colspan="5" style="padding: 0;"><hr></td>
					</tr>
					<tr>
						<td data-colname="<?php \esc_attr_e( 'Image Sizes Name', 'sirsc' ); ?>">
							<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][name]"
								id="sirsc_image_size_<?php echo (int) $counter; ?>_name"
								type="text" value="<?php echo \esc_attr( $name ); ?>" style="width: 100%">
						</td>
						<td><?php \esc_html_e( '(leave empty to remove this image size)', 'sirsc' ); ?></td>
						<td data-colname="<?php \esc_attr_e( 'Max Width', 'sirsc' ); ?>">
							<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][width]"
								id="sirsc_image_size_<?php echo (int) $counter; ?>_width"
								type="number" value="<?php echo \esc_attr( $width ); ?>" class="small-text"> px
						</td>
						<td data-colname="<?php \esc_attr_e( 'Max Height', 'sirsc' ); ?>">
							<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][height]"
								id="sirsc_image_size_<?php echo (int) $counter; ?>_height"
								type="number" value="<?php echo \esc_attr( $height ); ?>" class="small-text"> px
						</td>
						<td data-colname="<?php \esc_attr_e( 'Crop', 'sirsc' ); ?>">
							<label>
								<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][crop]"
									id="sirsc_image_size_<?php echo (int) $counter; ?>_crop"
									type="checkbox" value="1" class="code" <?php \checked( 1, $crop ); ?>>
								<?php \esc_html_e( 'Crop the image to exact dimensions', 'sirsc' ); ?>.
							</label>
						</td>
					</tr>
					<?php
				}
			}

			++$counter;
			?>
			<tr>
				<td colspan="5" style="padding: 0;"><hr></td>
			</tr>
			<tr class="sirsc-message info">
				<td colspan="2" data-colname="<?php \esc_attr_e( 'Image Sizes Name', 'sirsc' ); ?>">
					<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][name]"
						id="sirsc_image_size_<?php echo (int) $counter; ?>_name"
						type="text" placeholder="<?php \esc_attr_e( 'New image size name', 'sirsc' ); ?>" value="" style="width: 100%">
				</td>
				<td data-colname="<?php \esc_attr_e( 'Max Width', 'sirsc' ); ?>">
					<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][width]"
						id="sirsc_image_size_<?php echo (int) $counter; ?>_width"
						type="number" value="" class="small-text"> px
				</td>
				<td data-colname="<?php \esc_attr_e( 'Max Height', 'sirsc' ); ?>">
					<input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][height]"
						id="sirsc_image_size_<?php echo (int) $counter; ?>_height"
						type="number" value="" class="small-text"> px
				</td>
				<td data-colname="<?php \esc_attr_e( 'Crop', 'sirsc' ); ?>">
					<label><input name="sirsc_use_custom_image_sizes[<?php echo (int) $counter; ?>][crop]" id="sirsc_image_size_<?php echo (int) $counter; ?>_crop" type="checkbox" value="1" class="code"> <?php \esc_html_e( 'Crop the image to exact dimensions', 'sirsc' ); ?> <?php \esc_html_e( '(normally images are proportional).', 'sirsc' ); ?></label>
				</td>
			</tr>
		</tbody>
	</table>
	<hr>
	<?php
}

/**
 * Update the settings as expected.
 *
 * @param string $option    Option name.
 * @param string $old_value Option old value.
 * @param string $value     Option new value.
 */
function custom_media_settings_overrides( $option = '', $old_value = '', $value = '' ) { // phpcs:ignore
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = filter_input( INPUT_POST, 'sirsc_media_settings_nonce', FILTER_DEFAULT );
	if ( empty( $nonce ) ) {
		return;
	}

	if ( ! \wp_verify_nonce( $nonce, 'sirsc_media_settings_save' ) ) {
		\wp_die( \esc_html__( 'Action not allowed.', 'sirsc' ), \esc_html__( 'Security Breach', 'sirsc' ) );
	}

	// Override medium size.
	$value = filter_input( INPUT_POST, 'sirsc_override_medium_size', FILTER_DEFAULT );
	\update_option( 'sirsc_override_medium_size', ! empty( $value ) ? 1 : 0 );
	\update_option( 'medium_crop', ! empty( $value ) ? 1 : 0 );

	// Override medium large size.
	$value = filter_input( INPUT_POST, 'sirsc_override_medium_large_size', FILTER_DEFAULT );
	\update_option( 'sirsc_override_medium_large_size', ! empty( $value ) ? 1 : 0 );
	\update_option( 'medium_large_crop', ! empty( $value ) ? 1 : 0 );

	// Override large size.
	$value = filter_input( INPUT_POST, 'sirsc_override_large_size', FILTER_DEFAULT );
	\update_option( 'sirsc_override_large_size', ! empty( $value ) ? 1 : 0 );
	\update_option( 'large_crop', ! empty( $value ) ? 1 : 0 );

	// Override featured size.
	$value = filter_input( INPUT_POST, 'sirsc_admin_featured_size', FILTER_DEFAULT );
	\update_option( 'sirsc_admin_featured_size', $value );

	// Save custom image sizes changes.
	$native = \SIRSC::get_native_image_sizes();
	if ( empty( $native ) || ! is_array( $native ) ) {
		// Fail-fast.
		return;
	}
	$native = array_merge( $native, [ 'full', 'original', 'original_image', '1536x1536', '2048x2048' ] );
	$value  = filter_input( INPUT_POST, 'sirsc_use_custom_image_sizes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$sizes  = [];
	if ( ! empty( $value ) ) {
		foreach ( $value as $k => $item ) {
			if ( empty( $item['name'] ) ) {
				// The item will be removed.
				continue;
			}

			$width  = abs( (int) $item['width'] );
			$height = abs( (int) $item['height'] );
			$crop   = ( ! empty( $item['crop'] ) ) ? 1 : 0;
			if ( empty( $width ) && empty( $height ) ) {
				// No width or height provided, not recording this size.
				continue;
			}

			$name = str_replace( '-', '_', \sanitize_title( $item['name'] ) );
			$name = strtolower( $name );
			$name = str_replace( ' ', '_', $name );
			$name = str_replace( '-', '_', $name );

			if ( in_array( $name, $native, true ) || isset( $sizes[ $name ] ) ) {
				// Same name as another size, not recording this size.
				continue;
			}

			if ( ! empty( $crop ) ) {
				// One final check for the crop size.
				if ( empty( $width ) ) {
					$width = $height;
				} elseif ( empty( $height ) ) {
					$height = $width;
				}
			}

			// If we got this far, the image size seems legit.
			$sizes[] = [
				'name'   => $name,
				'width'  => $width,
				'height' => $height,
				'crop'   => $crop,
			];
		}
	}
	$updates = [
		'sizes'  => $sizes,
		'number' => count( $sizes ),
	];
	\update_option( 'sirsc_use_custom_image_sizes', $updates );
}

/**
 * Functionality to manage the image regenerate & select crop settings.
 */
function sirsc_custom_rules_settings() {
	if ( ! \current_user_can( 'manage_options' ) ) {
		// Verify user capabilities in order to deny the access if the user does not have the capabilities.
		\wp_die( \esc_html__( 'Action not allowed.', 'sirsc' ) );
	}

	$post_types              = \SIRSC\Helper\get_all_post_types_plugin();
	$_sirsc_post_types       = filter_input( INPUT_GET, '_sirsc_post_types', FILTER_DEFAULT );
	$settings                = \maybe_unserialize( \get_option( 'sirsc_settings' ) );
	$default_plugin_settings = $settings;
	if ( ! empty( $_sirsc_post_types ) ) {
		$settings = \maybe_unserialize( \get_option( 'sirsc_settings_' . $_sirsc_post_types ) );
	}

	$all_sizes = \SIRSC::get_all_image_sizes();
	?>

	<div class="wrap sirsc-settings-wrap sirsc-feature">
		<?php show_plugin_top_info(); ?>
		<?php maybe_all_features_tab(); ?>

		<div class="sirsc-tabbed-menu-content">
			<div class="sirsc-image-generate-functionality">
				<form id="sirsc_settings_frm" name="sirsc_settings_frm" action="" method="post">
					<?php \wp_nonce_field( '_sirsc_settings_save', '_sirsc_settings_nonce' ); ?>
					<?php include_once __DIR__ . '/parts/admin-advanced.php'; ?>
				</form>
			</div>
		</div>

		<?php show_donate_text(); ?>
	</div>
	<?php
}

/**
 * Add the custom column.
 *
 * @param  array $columns The defined columns.
 * @return array
 */
function register_media_columns( $columns ) { // phpcs:ignore
	if ( ! empty( $columns ) ) {
		$before  = array_slice( $columns, 0, 2, true );
		$after   = array_slice( $columns, 2, count( $columns ) - 1, true );
		$columns = array_merge( $before, [ 'sirsc_buttons' => \esc_html__( 'Details/Options', 'sirsc' ) ], $after );
	}
	return $columns;
}

/**
 * Output the custom column value.
 *
 * @param  string $column The current column.
 * @param  int    $value  The current column value.
 * @return void
 */
function media_column_value( $column, $value ) { // phpcs:ignore
	if ( 'sirsc_buttons' === $column ) {
		global $post, $sirsc_column_summary;
		if ( ! empty( \SIRSC::$settings['listing_show_summary'] ) ) {
			$sirsc_column_summary = true;
		}
		if ( ! empty( $post ) && ! empty( $post->post_mime_type )
			&& substr_count( $post->post_mime_type, 'image/' ) ) {
			$extra_class = ( ! empty( \SIRSC::$settings['listing_tiny_buttons'] ) ) ? 'tiny' : '';
			echo append_image_generate_button( '', '', $post->ID, $extra_class ); // phpcs:ignore
			if ( ! empty( \SIRSC::$settings['listing_show_summary'] ) ) {
				\SIRSC\Helper\attachment_listing_summary( $post->ID );
			}
		}
	}
}

/**
 * Adon intro.
 *
 * @param string $title Addon title.
 * @param string $desc  Addon description.
 * @param string $image Addon image.
 */
function addon_intro( $title, $desc = '', $image = '' ) {
	?>
	<div class="as-row a-middle">
		<img src="<?php echo \esc_url( SIRSC_URL . 'assets/images/' . $image ); ?>" loading="lazy" width="48" style="width: 48px; flex: 0 0 48px !important">
		<div>
			<div class="label-row"><h2><?php echo \esc_html( $title ); ?></h2></div>
			<p><?php echo \wp_kses_post( $desc ); ?></p>
		</div>
	</div>
	<?php
}
