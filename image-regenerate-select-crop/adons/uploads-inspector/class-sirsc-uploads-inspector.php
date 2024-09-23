<?php
/**
 * Uploads inspector extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

/**
 * Class for Image Regenerate & Select Crop plugin adon Uploads Inspector.
 */
class SIRSC_Adons_Uploads_Inspector {
	const PLUGIN_VER        = 8.0;
	const PLUGIN_TRANS      = 'sirsc_adon_uploads_inspector';
	const PLUGIN_TABLE      = 'sirsc_adon_uploads_inspector';
	const PLUGIN_BATCH_SIZE = 20;
	const ADON_PAGE_SLUG    = 'sirsc-adon-uploads-inspector';
	const ADON_SLUG         = 'uploads-inspector';

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	public static $settings;

	/**
	 * Plugin identified and filtered post types.
	 *
	 * @var array
	 */
	public static $post_types;

	/**
	 * Get active object instance
	 *
	 * @return object
	 */
	public static function get_instance() { // phpcs:ignore
		if ( ! self::$instance ) {
			self::$instance = new SIRSC_Adons_Uploads_Inspector();
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

		$called = get_called_class();
		if ( is_admin() ) {
			add_action( 'admin_menu', [ get_called_class(), 'adon_admin_menu' ], 20 );
			add_action( 'plugins_loaded', [ $called, 'load_textdomain' ] );
			add_action( 'admin_enqueue_scripts', [ $called, 'load_assets' ] );
			add_action( 'sirsc_folder_assess_images_button', [ $called, 'folder_assess_images_button' ] );
			add_action( 'sirsc_folder_refresh_button', [ $called, 'folder_refresh_button' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_display_summary', [ $called, 'display_summary' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_display_filesinfo', [ $called, 'display_filesinfo' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_display_listing', [ $called, 'display_listing' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_execute_refresh', [ $called, 'execute_refresh' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_execute_finalize', [ $called, 'execute_finalize' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_execute_assess', [ $called, 'execute_assess' ] );
			add_action( 'wp_ajax_sirsc_adon_ui_execute_cron', [ $called, 'assess_cron' ] );
			add_action( 'sirsc_folder_assess_images_stats', [ $called, 'folder_assess_images_stats' ] );

			// Check extension version.
			add_action( 'init', [ $called, 'adon_ver_check' ], 30 );
			self::init_buttons();
		}
	}

	/**
	 * Load text domain for internalization.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'sirsc', false, basename( SIRSC_DIR ) . '/langs/' );
	}

	/**
	 * Enqueue the css and javascript files.
	 */
	public static function load_assets() {
		$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore
		if ( ! substr_count( $uri, 'page=sirsc-adon-uploads-inspector' ) ) {
			// Fail-fast, the assets should not be loaded.
			return;
		}

		$dir = SIRSC_URL . 'build/adons/uploads-inspector/';
		$ver = \SIRSC\get_build_ver();
		wp_enqueue_style( 'sirsc-adons-improf', $dir . 'style.css', [], $ver, false );
		wp_register_script( 'sirsc-adons-improf', $dir . 'index.js', [ 'sirsc-iterator' ], $ver, true );
		wp_localize_script( 'sirsc-adons-improf', 'SIRSC_Adons_Improf', [
			'ajaxUrl'      => esc_url( admin_url( 'admin-ajax.php' ) ),
			'listBoxTitle' => __( 'List', 'sirsc' ),
			'delay'        => 2000,
		] );
		wp_enqueue_script( 'sirsc-adons-improf' );
	}

	/**
	 * The actions to be executed when the plugin is updated.
	 */
	public static function adon_ver_check() {
		$opt = str_replace( '-', '_', self::PLUGIN_TRANS ) . '_db_ver';
		$dbv = get_option( $opt, 0 );
		if ( self::PLUGIN_VER !== (float) $dbv ) {
			self::maybe_upgrade_db();
			set_transient( self::PLUGIN_TRANS, true );
		}
	}

	/**
	 * Maybe upgrade the table structure.
	 */
	public static function maybe_upgrade_db() {
		global $wpdb;
		$opt = str_replace( '-', '_', self::PLUGIN_TRANS ) . '_db_ver';
		$dbv = get_option( $opt, 0 );
		if ( self::PLUGIN_VER !== (float) $dbv ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$sql = ' CREATE TABLE ' . self::PLUGIN_TABLE . ' (
				`id` bigint(20) AUTO_INCREMENT,
				`date` bigint(20),
				`type` varchar(15),
				`path` varchar(255),
				`attachment_id` bigint(20),
				`size_name` varchar(255),
				`size_width` int(11),
				`size_height` int(11),
				`mimetype` varchar(32),
				`filesize` bigint(20),
				`in_option` varchar(255),
				`valid` tinyint(1) default 0,
				`assessed` tinyint(1) default 0,
				`count_files` bigint(20),
				UNIQUE KEY `id` (id),
				KEY `type` (`type`),
				KEY `size_name` (`size_name`),
				KEY `attachment_id` (`attachment_id`),
				KEY `mimetype` (`mimetype`),
				KEY `path` (`path`),
				KEY `date` (`date`),
				KEY `valid` (`valid`),
				KEY `in_option` (`in_option`)
			) CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = \'Table created by Image Regenerate & Select Crop Adon for Uploads Inspector\'';
			dbDelta( $sql );
			update_option( $opt, (float) self::PLUGIN_VER );
		}
	}

	/**
	 * Init the adon main buttons.
	 */
	public static function init_buttons() {
		do_action(
			'sirsc/iterator/setup_buttons',
			'sirsc-ui',
			[
				'assess'  => [
					'icon'       => '<span class="dashicons dashicons-image-rotate"></span>',
					'text'       => __( 'Assess', 'sirsc' ),
					'callback'   => 'sirscUiStartAssess()',
					'attributes' => [ 'data-path' => '*' ],
					'buttons'    => [ 'stop', 'resume', 'cancel' ],
					'class'      => '',
				],
				'refresh' => [
					'icon'     => '<span class="dashicons dashicons-image-rotate"></span>',
					'text'     => __( 'Refresh', 'sirsc' ),
					'callback' => 'sirscUiStartRefresh()',
					'class'    => '',
				],
			]
		);
	}

	/**
	 * Add the plugin menu.
	 */
	public static function adon_admin_menu() {
		add_submenu_page(
			'image-regenerate-select-crop-settings',
			__( 'Uploads Inspector', 'sirsc' ),
			__( 'Uploads Inspector', 'sirsc' ),
			'manage_options',
			self::ADON_PAGE_SLUG,
			[ get_called_class(), 'adon_page' ]
		);
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
			<?php \SIRSC\admin\addon_intro( __( 'Uploads Inspector', 'sirsc' ), $desc, 'adon-uploads-inspector-image.png' ); ?>

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
				</div>

				<div class="as-box bg-secondary">
					<?php do_action( 'sirsc_folder_assess_images_button', '*' ); ?>
				</div>

				<div class="as-box bg-secondary">
					<?php do_action( 'sirsc_folder_refresh_button' ); ?>
				</div>
			</div>

			<div id="sirsc-filesinfo-wrap" class="sirsc-feature as-box bg-secondary">
				<?php self::display_filesinfo(); ?>
			</div>

			<div id="sirsc-listing-wrap" class="sirsc-feature sirsc-target"></div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for uploads inspector cron assessment.
	 */
	public static function assess_cron() {
		\SIRSC\AJAX\verify_ajax_call_nonce();

		$start = filter_input( INPUT_GET, 'start', FILTER_DEFAULT );
		if ( ! empty( \SIRSC::$use_cron ) ) {
			\SIRSC\Cron\assess_task( 'sirsc_adon_ui_execute_assess', [] );
		}

		\SIRSC\AJAX\sirsc_call_end();
	}

	/**
	 * Show an images assess trigger button markup.
	 *
	 * @param string $path Path of a folder.
	 */
	public static function folder_assess_images_button( $path ) { // phpcs:ignore
		?>
		<div class="as-row as-title a-middle">
			<h2><?php esc_html_e( 'Assess uploads', 'sirsc' ); ?></h2>
			<?php
			if ( \SIRSC::$use_cron ) {
				?>
				<button type="button"
					class="button has-icon tiny f-right"
					name="sirsc-settings-submit" value="submit"
					id="sirsc-adon-ui-execute-assess-cron"
					title="<?php \esc_attr_e( 'Cron task is scheduled', 'sirsc' ); ?>"
					onclick="sirscUiStartAssessCron('start');">
					<span class="dashicons dashicons-admin-generic"></span>
				</button>
				<?php
			} else {
				ob_start();
				\SIRSC\Iterator\button_display( 'sirsc-ui-assess' );
				$button = ob_get_clean();

				$started = get_option( self::PLUGIN_TABLE . '_proc_dir', 0 );
				if ( ! empty( $started ) ) {
					$button = str_replace( 'button-primary', 'button-primary initiated', $button );
					$button = str_replace( 'sirsc-iterator-stop', 'sirsc-iterator-stop hidden', $button );
					$button = str_replace( 'sirsc-iterator-resume hidden', 'sirsc-iterator-resume', $button );
					$button = str_replace( 'sirsc-iterator-cancel hidden', 'sirsc-iterator-cancel', $button );
					$button = str_replace( 'data-path="*"', 'data-path="*" data-action="stop" data-processing="1" data-stopped="1" data-visibles="len-1"', $button );
				}
				echo wp_kses_post( $button );
			}
			?>
		</div>
		<p>
			<?php esc_html_e( 'Click to assess the files from uploads folder & refresh the info.', 'sirsc' ); ?>
			<?php esc_html_e( 'This option will initiate the assessment of the uploads structure and contents, and will collect information that can be checked later.', 'sirsc' ); ?>
		</p>
		<?php
	}

	/**
	 * Show an refresh trigger button markup.
	 */
	public static function folder_refresh_button() {
		?>
		<div class="as-row as-title a-middle">
			<h2><?php esc_html_e( 'Refresh summary', 'sirsc' ); ?></h2>
			<?php \SIRSC\Iterator\button_display( 'sirsc-ui-refresh' ); ?>
		</div>
		<p>
			<?php esc_html_e( 'Click to refresh summary. This will refresh the totals and counts if something was updated in the meanwhile.', 'sirsc' ); ?>
			<?php esc_html_e( 'This option will also stop the assessment, if that is currently in progress.', 'sirsc' ); ?>
		</p>
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
		if ( 'sirsc_adon_ui_display_summary' === $act ) {
			wp_die();
			die();
		}
	}

	/**
	 * Display files info.
	 */
	public static function display_filesinfo() {
		do_action( 'sirsc_folder_assess_images_stats' );

		$act = filter_input( INPUT_GET, 'action', FILTER_DEFAULT );
		if ( 'sirsc_adon_ui_display_filesinfo' === $act ) {
			wp_die();
			die();
		}
	}

	/**
	 * Reset counters.
	 */
	public static function reset_assess_counters() { // phpcs:ignore
		self::cleanup_not_found();
		update_option( self::PLUGIN_TABLE . '_last_proc', time() );
		update_option( self::PLUGIN_TABLE . '_proc_dir', 0 );
		update_option( self::PLUGIN_TABLE . '_proc_item', '' );
		update_option( self::PLUGIN_TABLE . '_proc_time', 0 );
	}

	/**
	 * Start over.
	 */
	public static function start_over() {
		$upls = wp_upload_dir();
		$base = trailingslashit( $upls['basedir'] );
		$trid = 'sirsc_adon_uploads_folder_summary';
		$info = \SIRSC\Helper\get_folders_list( $base );
		set_transient( $trid, $info, 1 * HOUR_IN_SECONDS );
		update_option( 'sirsc_adon_uploads_files_count', $info[0]['totals']['files_count'] );
	}

	/**
	 * Execute the summary and info funalize.
	 */
	public static function execute_finalize() {
		\SIRSC\Iterator\is_valid_ajax();
		self::start_over();
		self::reset_assess_counters();

		\SIRSC\Helper\the_document_ready_js(
			\SIRSC\Iterator\button_callback( 'sirsc-ui-assess', 'stop' )
			. ' ' . \SIRSC\Iterator\button_callback( 'sirsc-ui-assess', 'reset' )
			. ' ' . \SIRSC\Iterator\button_callback( 'sirsc-ui-refresh', 'reset' )
			. ' sirscUiFinishUp();'
		);

		wp_die();
		die();
	}

	/**
	 * Execute the summary and info refresh.
	 */
	public static function execute_refresh() {
		\SIRSC\Iterator\is_valid_ajax();
		self::start_over();
		self::reset_assess_counters();

		\SIRSC\Helper\the_document_ready_js(
			\SIRSC\Iterator\button_callback( 'sirsc-ui-assess', 'stop' )
			. ' ' . \SIRSC\Iterator\button_callback( 'sirsc-ui-assess', 'reset' )
			. ' ' . \SIRSC\Iterator\button_callback( 'sirsc-ui-refresh', 'reset' )
			. ' sirscUiFinishUp();'
		);

		wp_die();
		die();
	}

	/**
	 * Run assessment step.
	 *
	 * @return array
	 */
	public static function run_assessment_step() {
		$last_item = get_option( self::PLUGIN_TABLE . '_proc_item', '' );
		$dir_id    = get_option( self::PLUGIN_TABLE . '_proc_dir', 0 );
		$upls      = wp_upload_dir();
		$base      = trailingslashit( $upls['basedir'] );

		if ( empty( $dir_id ) ) {
			$trid = 'sirsc_adon_uploads_folder_summary';
			$info = get_transient( $trid );
			if ( ! empty( $info ) ) {
				foreach ( $info as $k => $folder ) {
					if ( $k > 0 ) {
						$p = str_replace( $base, '', $folder['path'] );
						self::record_item( 'folder', $p, $folder['totals']['files_size'], $folder['totals']['files_count'] );
					}
				}
			}
		}

		$time = get_option( self::PLUGIN_TABLE . '_proc_time', 0 );
		if ( empty( $time ) ) {
			update_option( self::PLUGIN_TABLE . '_proc_time', time() );
		}

		$maybe_dir = self::get_assessed_folders( (int) $dir_id, true );
		ob_start();
		if ( ! empty( $maybe_dir->path ) ) {
			?>
			<div class="label-row as-title">
				<h2>
					<?php esc_html_e( 'Processing the request for', 'sirsc' ); ?>
					<b><?php echo esc_html( $maybe_dir->path ); ?></b>
				</h2>
			</div>
			<?php self::compute_progress_bar(); ?>
			<ul class="as-row columns-3 no-margin files-info-wrap">
				<?php
				$dir       = $base . $maybe_dir->path;
				$last_path = $base . $last_item;
				$search    = true;
				$record    = ( empty( $last_item ) ) ? true : false;
				$count     = 0;
				$all       = 0;
				foreach ( glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT ) as $each ) { // | GLOB_NOSORT
					if ( is_file( $each ) ) {
						if ( true === $search ) {
							if ( $each === $last_path ) {
								$record = true;
								$search = false; // This was found, rely only on the counts.
							}
						}
						if ( true === $record ) {
							if ( $count < self::PLUGIN_BATCH_SIZE ) {
								$p = str_replace( $base, '', $each );
								self::record_item( 'file', $p, filesize( $each ) );
								echo wp_kses_post( '<li class="file-info">' . esc_html( $p ) . '</li>' );

								++$count;
							} else {
								break 1;
							}
						}
						++$all;
					}
				}
				?>
			</div>
			<?php
			if ( $count <= 1 && ! empty( $record ) ) {
				// This means that maybe the folder was all processed.
				update_option( self::PLUGIN_TABLE . '_proc_dir', (int) $maybe_dir->id );
				update_option( self::PLUGIN_TABLE . '_proc_item', '' );
			}
		}
		$output = ob_get_clean();
		$result = [
			'maybe_dir' => $maybe_dir,
			'total'     => self::compute_remaining_to_process(),
			'output'    => $output,
		];

		return $result;
	}

	/**
	 * Execute files assessment.
	 */
	public static function execute_assess() {
		\SIRSC\Iterator\is_valid_ajax();

		$iterator = filter_input( INPUT_GET, 'iterator', FILTER_DEFAULT );
		if ( 'stop' === $iterator ) {
			self::reset_assess_counters();
		}

		$processed = self::run_assessment_step();
		$maybe_dir = $processed['maybe_dir'];
		if ( ! empty( $processed['output'] ) ) {
			echo wp_kses_post( $processed['output'] );
		}

		if ( ! empty( $maybe_dir ) ) {
			\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-ui-assess', 'continue' ), true );
		} else {
			\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-ui-assess', 'reset' ) . ' sirscUiFinishUpAll();' );
		}

		wp_die();
		die();
	}

	/**
	 * Stats load list page.
	 */
	public static function display_listing() {
		include_once __DIR__ . '/parts/files-list.php';
		wp_die();
		die();
	}

	/**
	 * File info button.
	 *
	 * @param string $id    Button id.
	 * @param int    $max   Maiximum page.
	 * @param string $size  Image size name.
	 * @param string $mime  Mime type.
	 * @param string $title File type title.
	 * @param int    $total Total items.
	 */
	public static function file_info_button( $id, $max = 0, $size = '', $mime = '', $title = '', $total = 0 ) {
		?>
		<button id="<?php echo esc_html( $id ); ?>" class="sirsc-listing-wrap-item" data-page="1"
			data-valid="0" data-maxpage="<?php echo (int) $max; ?>"
			data-sizename="<?php echo esc_html( $size ); ?>" data-mimetype="<?php echo esc_html( $mime ); ?>"
			data-title="<?php echo esc_html( $title ); ?>">(<?php echo (int) $total; ?> <?php esc_html_e( 'files', 'sirsc' ); ?>)</button>
		<?php
	}

	/**
	 * Show an images assess profile stats.
	 */
	public static function folder_assess_images_stats() {
		$perpag    = get_option( 'posts_per_page' );
		$perpag    = empty( $perpag ) ? 10 : abs( $perpag );
		$last_proc = get_option( self::PLUGIN_TABLE . '_last_proc', 0 );
		if ( empty( $last_proc ) ) {
			return;
		}
		?>
		<div class="as-row as-title">
			<h2><?php esc_html_e( 'Files Info', 'sirsc' ); ?></h2>
		</div>
		<p class="small-gap">
			<?php
			echo wp_kses_post( sprintf(
				// Translators: %1$s - current page, %2$s - total pages.
				__( 'The most recent files assessment was executed on %1$s.', 'sirsc' ),
				'<b>' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_proc, true ) . '</b>'
			) );
			?>
			<?php esc_html_e( 'Click the files counts below to open the list of assessed items.', 'sirsc' ); ?>
		</p>

		<?php
		include_once __DIR__ . '/parts/info-list.php';
	}

	/**
	 * Get the size of the directory.
	 *
	 * @param string $type        Item type (folder|file).
	 * @param string $path        The item path.
	 * @param int    $size        The item size.
	 * @param int    $count_files The items count.
	 */
	public static function record_item( $type, $path, $size, $count_files = 0 ) { // phpcs:ignore
		global $wpdb;

		$attachment_id = 0;
		$mimetype      = '';
		$size_name     = '';
		$size_width    = 0;
		$size_height   = 0;
		$valid         = 0;
		$in_option     = '';

		if ( 'file' === $type ) {
			update_option( self::PLUGIN_TABLE . '_proc_item', $path );

			$original  = $path;
			$size_file = basename( $original );
			$tmp_query = $wpdb->prepare(
				' SELECT a.ID, group_concat( concat( am.meta_key, \'[#$#]\', am.meta_value ) separator \'[#@#]\' ) as str_meta FROM ' . $wpdb->posts . ' as a
				LEFT JOIN ' . $wpdb->postmeta . ' as am ON(am.post_id = a.ID)
				WHERE (am.meta_key like %s OR am.meta_key like %s ) AND ( am.meta_value like %s OR am.meta_value like %s )
				GROUP BY a.id
				ORDER BY a.ID LIMIT 0,1',
				'_wp_attachment_metadata',
				'_wp_attached_file',
				'%' . $original . '%',
				'%' . $size_file . '%'
			);
			$row = $wpdb->get_row( $tmp_query ); // phpcs:ignore
			if ( ! empty( $row ) ) {
				$attachment_id = $row->ID;
				if ( ! empty( $row->str_meta ) ) {
					$meta = '';
					if ( substr_count( $row->str_meta, '_wp_attachment_metadata' ) ) {
						// Potential image.
						$p = explode( '[#@#]', $row->str_meta );
						if ( ! empty( $p[0] ) && substr_count( $p[0], '_wp_attachment_metadata' ) ) {
							$meta = $p[0];
						} elseif ( ! empty( $p[1] ) && substr_count( $p[1], '_wp_attachment_metadata' ) ) {
							$meta = $p[1];
						}

						if ( ! empty( $meta ) ) {
							$meta = trim( str_replace( '_wp_attachment_metadata[#$#]', '', $meta ) );
							$meta = maybe_unserialize( trim( $meta ) );
							if ( ! is_array( $meta ) ) {
								// Fallback to the wp function.
								$meta = wp_get_attachment_metadata( $attachment_id );
							}
						}
					}

					if ( ! empty( $meta ) && is_array( $meta ) ) {
						$mt       = wp_check_filetype( $size_file );
						$mimetype = $mt['type'];
						if ( ! empty( $meta['file'] ) && $original === $meta['file'] ) {
							$size_name   = 'full';
							$size_width  = $meta['width'];
							$size_height = $meta['height'];
							$maybe_type  = wp_check_filetype( $meta['file'] );
							$mimetype    = ( ! empty( $maybe_type['type'] ) ) ? $maybe_type['type'] : $mimetype;
							$valid       = 1;
						} elseif ( ! empty( $meta['sizes'] ) ) {
							foreach ( $meta['sizes'] as $key => $value ) {
								if ( $size_file === $value['file'] ) {
									$size_name   = $key;
									$size_width  = $value['width'] ?? 0;
									$size_height = $value['height'] ?? 0;
									$mimetype    = $value['mime-type'] ?? '';
									$valid       = 1;
									break;
								}
							}
						}
					} else {
						$mt = wp_check_filetype( $path );

						$mimetype = $mt['type'];
					}
				}
			} else {
				$mt = wp_check_filetype( $path );

				$mimetype = $mt['type'];
			}

			$in_option  = '';
			$tmp_query2 = $wpdb->prepare(
				' SELECT group_concat(option_name separator \', \') FROM ' . $wpdb->options . '
				WHERE option_value like %s AND option_name not like %s
				GROUP BY option_name
				ORDER BY option_name LIMIT 0,1',
				'%' . $path . '%',
				'%sirsc_adon%'
			);

			$row2 = $wpdb->get_var( $tmp_query2 ); // phpcs:ignore
			if ( ! empty( $row2 ) ) {
				$in_option = $row2;
			}
		}

		$array_data = [
			'date'          => time(),
			'type'          => $type,
			'path'          => $path,
			'filesize'      => $size,
			'attachment_id' => $attachment_id,
			'mimetype'      => $mimetype,
			'size_name'     => $size_name,
			'size_width'    => $size_width,
			'size_height'   => $size_height,
			'valid'         => $valid,
			'count_files'   => $count_files,
			'in_option'     => $in_option,
		];
		$array_type = [ '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s' ];
		$tmp_query  = $wpdb->prepare(
			' SELECT id FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s AND path = %s ORDER BY id LIMIT 0,1', // phpcs:ignore
			$type,
			$path
		); // phpcs:ignore

		$id = $wpdb->get_var( $tmp_query ); // phpcs:ignore
		if ( ! empty( $id ) ) {
			$wpdb->update( self::PLUGIN_TABLE, $array_data, array( 'id' => $id ), $array_type, array( '%d' ) ); // phpcs:ignore
		} else {
			$wpdb->insert( self::PLUGIN_TABLE, $array_data, $array_type ); // phpcs:ignore
		}
	}

	/**
	 * Get assessed folders.
	 *
	 * @param  int  $id     Folder id.
	 * @param  bool $use_id True to use the id for compare.
	 * @return array|object
	 */
	public static function get_assessed_folders( $id = 0, $use_id = false ) { // phpcs:ignore
		global $wpdb;
		$folders = [];
		if ( true === $use_id ) {
			$query = $wpdb->prepare( ' SELECT * FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s and id > %d ORDER BY id ASC LIMIT 0,1', 'folder', $id ); // phpcs:ignore
			$rows = $wpdb->get_row( $query ); // phpcs:ignore
		} else {
			$query = $wpdb->prepare( ' SELECT * FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s ORDER BY id ASC ', 'folder' ); // phpcs:ignore
			$rows  = $wpdb->get_results( $query ); // phpcs:ignore
		}

		if ( ! empty( $rows ) ) {
			$folders = $rows;
		}
		return $folders;
	}

	/**
	 * Compute progress bar.
	 */
	public static function compute_progress_bar() {
		global $wpdb;

		$total = get_option( 'sirsc_adon_uploads_files_count', 0 );
		$info  = get_transient( 'sirsc_adon_uploads_folder_summary' );
		if ( ! empty( $info[0]['totals']['folders_count'] ) ) {
			$total += $info[0]['totals']['folders_count'];
		}

		$time      = get_option( self::PLUGIN_TABLE . '_proc_time', 0 );
		$processed = $wpdb->get_var( $wpdb->prepare( ' SELECT COUNT(id) as total_files FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s and date >= %d', 'file', $time ) ); // phpcs:ignore

		if ( $processed >= $total ) {
			update_option( 'sirsc_adon_uploads_files_count', $processed );
		}

		$text = esc_html( sprintf(
			// Translators: %1$d - count products, %2$d - total.
			__( 'There are %1$d items assessed out of %2$d.', 'sirsc' ),
			$processed,
			$total
		) );

		\SIRSC\Helper\progress_bar( $total, $processed, true, $text );
	}

	/**
	 * Compute number of items remaining.
	 *
	 * @return int
	 */
	public static function compute_remaining_to_process(): int {
		global $wpdb;

		$total = get_option( 'sirsc_adon_uploads_files_count', 0 );
		$info  = get_transient( 'sirsc_adon_uploads_folder_summary' );
		$time  = get_option( self::PLUGIN_TABLE . '_proc_time', 0 );
		if ( ! empty( $info[0]['totals']['folders_count'] ) ) {
			$total += $info[0]['totals']['folders_count'];
		}

		$processed = $wpdb->get_var( $wpdb->prepare( ' SELECT COUNT(id) as total_files FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s and date >= %d', 'file', $time ) ); // phpcs:ignore

		$diff = (int) $total - (int) $processed;
		$diff = ( $diff <= 0 ) ? 0 : $diff;
		return $diff;
	}

	/**
	 * Cleanup not found.
	 */
	public static function cleanup_not_found() {
		$time = get_option( self::PLUGIN_TABLE . '_proc_time', 0 );
		if ( ! empty( $time ) ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( ' DELETE FROM ' . self::PLUGIN_TABLE . ' WHERE date < %d ', $time ) ); // phpcs:ignore
		}
	}
}

// Instantiate the class.
SIRSC_Adons_Uploads_Inspector::get_instance();
