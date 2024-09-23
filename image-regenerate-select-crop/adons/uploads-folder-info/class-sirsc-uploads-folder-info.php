<?php
/**
 * Uploads folder info extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

/**
 * Class for Image Regenerate & Select Crop plugin adon Upload Folder Info.
 */
class SIRSC_Adons_Uploads_Folder_Info {

	const ADON_PAGE_SLUG = 'sirsc-adon-uploads-folder-info';
	const ADON_SLUG      = 'uploads-folder-info';

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Get active object instance
	 *
	 * @return object
	 */
	public static function get_instance() { // phpcs:ignore
		if ( ! self::$instance ) {
			self::$instance = new SIRSC_Adons_Uploads_Folder_Info();
		}
		return self::$instance;
	}

	/**
	 * Class constructor. Includes constants, includes and init method.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run action and filter hooks.
	 */
	private function init() {
		if ( ! class_exists( 'SIRSC_Image_Regenerate_Select_Crop' ) ) {
			return;
		}

		if ( is_admin() ) {
			add_action( 'admin_menu', [ get_called_class(), 'adon_admin_menu' ], 20 );
			add_action( 'admin_enqueue_scripts', [ get_called_class(), 'load_assets' ] );
			add_action( 'wp_ajax_sirsc_adon_ufi_execute_refresh', [ get_called_class(), 'display_filesinfo' ] );
			add_action( 'wp_ajax_sirsc_adon_ufi_display_summary', [ get_called_class(), 'display_summary' ] );

			self::init_buttons();
		}
	}

	/**
	 * Enqueue the css and javascript files
	 */
	public static function load_assets() {
		$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore
		if ( ! substr_count( $uri, 'page=sirsc-adon-uploads-folder-info' ) ) {
			// Fail-fast, the assets should not be loaded.
			return;
		}

		$dir = SIRSC_URL . 'build/adons/uploads-folder-info/';
		$ver = \SIRSC\get_build_ver();
		wp_enqueue_script( 'sirsc-adons-ufi', $dir . 'index.js', [ 'sirsc-iterator' ], $ver, true );
		wp_enqueue_style( 'sirsc-adons-ufi', $dir . 'style.css', [], $ver, false );
	}

	/**
	 * Init the adon main buttons.
	 */
	public static function init_buttons() {
		do_action( 'sirsc/iterator/setup_buttons', 'sirsc-ufi', [
			'refresh' => [
				'icon'     => '<span class="dashicons dashicons-image-rotate"></span>',
				'text'     => __( 'Refresh', 'sirsc' ),
				'callback' => 'sirscUfiStartRefresh()',
				'class'    => '',
			],
		] );
	}

	/**
	 * Add the plugin menu.
	 */
	public static function adon_page() {
		SIRSC_Adons::check_adon_valid( self::ADON_SLUG );
		$desc = SIRSC_Adons::get_adon_details( self::ADON_SLUG, 'description' );
		?>

		<div class="wrap sirsc-settings-wrap sirsc-feature">
			<?php \SIRSC\Admin\show_plugin_top_info(); ?>
			<?php \SIRSC\Admin\maybe_all_features_tab(); ?>
			<?php \SIRSC\admin\addon_intro( __( 'Uploads Folder Info', 'sirsc' ), $desc, 'adon-uploads-folder-info-image.png' ); ?>

			<div class="as-row no-margin">
				<div class="as-box bg-secondary small">
					<div class="label-row as-title">
						<span class="dashicons as-icon dashicons-info-outline"></span>
						<h2><?php esc_html_e( 'Folder summary', 'sirsc' ); ?></h2>
					</div>
					<hr>
					<div id="sirsc-summary-wrap" class="sirsc-feature sirsc-target">
						<?php self::display_summary(); ?>
					</div>
					<?php self::folder_refresh_button(); ?>
				</div>

				<div class="as-box bg-secondary">
					<div id="sirsc-filesinfo-wrap" class="sirsc-feature sirsc-folders-info sirsc-target">
						<?php self::display_filesinfo(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Show an refresh trigger button markup.
	 */
	public static function folder_refresh_button() {
		?>
		<hr>

		<div class="label-row">
			<h2><?php esc_html_e( 'Refresh summary', 'sirsc' ); ?></h2>
		</div>

		<p>
			<?php esc_html_e( 'Click to refresh summary & folder details. This will refresh the totals and counts if something was updated in the meanwhile.', 'sirsc' ); ?>
		</p>

		<?php \SIRSC\Iterator\button_display( 'sirsc-ufi-refresh' ); ?>
		<?php
	}

	/**
	 * Display folders summary.
	 *
	 * @param array $info Computed info.
	 */
	public static function display_summary( $info = '' ) { // phpcs:ignore
		$info = ( empty( $info ) ) ? get_transient( 'sirsc_adon_uploads_folder_summary' ) : $info;
		if ( ! empty( $info ) ) {
			$root = $info[0];
			?>
			<ul class="sirsc-folders-info-wrap">
				<li>
					<?php esc_html_e( 'Upload folder', 'sirsc' ); ?>:
					<b><?php echo esc_html( $root['name'] ); ?></b>
				</li>
				<li>
					<?php esc_html_e( 'Size', 'sirsc' ); ?>:
					<b><?php echo esc_html( \SIRSC\Helper\human_filesize( $root['totals']['files_size'] ) ); ?></b>
					(<?php echo (int) $root['totals']['files_size']; ?>
					<?php esc_html_e( 'bytes', 'sirsc' ); ?>)
				</li>
				<li>
					<?php esc_html_e( 'Total folders', 'sirsc' ); ?>:
					<b><?php echo (int) $root['totals']['folders_count']; ?></b>
				</li>
				<li>
					<?php esc_html_e( 'Total files', 'sirsc' ); ?>:
					<b><?php echo (int) $root['totals']['files_count']; ?></b>
				</li>
			</ul>
			<?php
		} else {
			?>
			<div class="sirsc-folders-info-wrap">
				<?php esc_html_e( 'Currenty, there is no info.', 'sirsc' ); ?>
			</div>
			<?php
		}

		$act = filter_input( INPUT_GET, 'action', FILTER_DEFAULT );
		if ( 'sirsc_adon_ufi_display_summary' === $act ) {
			wp_die();
			die();
		}
	}

	/**
	 * Add the plugin menu.
	 */
	public static function adon_admin_menu() {
		add_submenu_page(
			'image-regenerate-select-crop-settings',
			__( 'Uploads Folder Info', 'sirsc' ),
			__( 'Uploads Folder Info', 'sirsc' ),
			'manage_options',
			self::ADON_PAGE_SLUG,
			[ get_called_class(), 'adon_page' ]
		);
	}

	/**
	 * Output folders details from info.
	 *
	 * @param array $info Folders computed info.
	 */
	public static function output_folders_details( $info ) { // phpcs:ignore
		if ( empty( $info ) ) {
			return;
		}

		$root = $info[0];
		?>
		<div class="sirsc-folders-info-wrap">
			<table class="wp-list-table striped widefat fixed pages">
				<thead>
					<td><h3 class="heading"><?php esc_html_e( 'Folder', 'sirsc' ); ?></h3></td>
					<td width="20%" class="a-right"><h3 class="heading"><?php esc_html_e( 'Total folders', 'sirsc' ); ?></h3></td>
					<td width="15%" class="a-right"><h3 class="heading"><?php esc_html_e( 'Total files', 'sirsc' ); ?></h3></td>
					<td width="15%" class="a-right"><h3 class="heading"><?php esc_html_e( 'Total size', 'sirsc' ); ?></h3></td>
					<td width="15%" class="a-right"><h3 class="heading"><?php esc_html_e( 'Total bytes', 'sirsc' ); ?></h3></td>
				</thead>

				<tbody>
					<?php
					$k = 0;
					foreach ( $info as $folder ) :
						++$k;
						$s = 2 * $folder['level'] + 2;
						?>
						<tr>
							<td class="name-wrap" style="--indent: <?php echo (int) $s; ?>rem" data-colname="<?php esc_attr_e( 'Folder', 'sirsc' ); ?>">
								<div>
									<span class="name"><b><?php echo esc_html( $folder['name'] ); ?></b></span>
								</div>
							</td>
							<td class="a-right" data-colname="<?php esc_attr_e( 'Total folders', 'sirsc' ); ?>">
								<?php if ( ! empty( $folder['totals']['folders_count'] ) ) : ?>
									<?php echo (int) $folder['totals']['folders_count']; ?>
								<?php endif; ?>
							</td>
							<td class="a-right" data-colname="<?php esc_attr_e( 'Total files', 'sirsc' ); ?>">
								<?php if ( ! empty( $folder['totals']['files_count'] ) ) : ?>
									<?php echo (int) $folder['totals']['files_count']; ?>
								<?php endif; ?>
							</td>
							<td class="a-right" data-colname="<?php esc_attr_e( 'Total size', 'sirsc' ); ?>">
								<?php if ( ! empty( $folder['totals']['files_size'] ) ) : ?>
									<b><?php echo esc_html( \SIRSC\Helper\human_filesize( $folder['totals']['files_size'] ) ); ?></b>
								<?php endif; ?>
							</td>
							<td class="a-right last" data-colname="<?php esc_attr_e( 'Total bytes', 'sirsc' ); ?>">
								<?php if ( ! empty( $folder['totals']['files_size'] ) ) : ?>
									<?php echo (int) $folder['totals']['files_size']; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
	/**
	 * Compute size.
	 */
	public static function display_filesinfo() {
		$is_ajax = false;
		$act     = filter_input( INPUT_GET, 'action', FILTER_DEFAULT );
		if ( ! empty( $act ) && 'sirsc_adon_ufi_execute_refresh' === $act ) {
			$is_ajax = true;
		}

		$upls = wp_upload_dir();
		$base = trailingslashit( $upls['basedir'] );
		$trid = 'sirsc_adon_uploads_folder_summary';
		if ( true === $is_ajax ) {
			$info = false; // Force recompute the transient on AJAX too.
		} else {
			$info = get_transient( $trid );
		}

		if ( false === $info ) {
			$info = \SIRSC\Helper\get_folders_list( $base );
			set_transient( $trid, $info, 1 * HOUR_IN_SECONDS );
			update_option( 'sirsc_adon_uploads_files_count', $info[0]['totals']['files_count'] );
		}
		?>
		<div class="label-row as-title">
			<h2><?php esc_html_e( 'Folder Details', 'sirsc' ); ?></h2>
		</div>

		<?php
		self::output_folders_details( $info );
		if ( true === $is_ajax ) {
			if ( class_exists( 'SIRSC_Adons_Images_Profiler' ) ) {
				update_option( SIRSC_Adons_Images_Profiler::PLUGIN_TABLE . '_proc_dir', '' );
				update_option( SIRSC_Adons_Images_Profiler::PLUGIN_TABLE . '_proc_item', '' );
			}

			\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-ufi-refresh', 'reset' ) . ' sirscUfiDisplaySummary();' );

			wp_die();
			die();
		}
	}
}

// Instantiate the class.
SIRSC_Adons_Uploads_Folder_Info::get_instance();
