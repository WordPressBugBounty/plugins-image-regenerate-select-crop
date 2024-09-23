<?php
/**
 * Images SEO extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

/**
 * Class for Image Regenerate & Select Crop plugin adon Images SEO.
 */
class SIRSC_Adons_Images_SEO {

	const RENAME_QUERY_TYPE  = 1; // 0 = process by post, 1 = process by attachment.
	const PROCESS_BATCH_SIZE = 2; // The rename batch size.
	const ADON_PAGE_SLUG     = 'sirsc-adon-images-seo';
	const ADON_SLUG          = 'images-seo';

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
			self::$instance = new SIRSC_Adons_Images_SEO();
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
		add_action( 'init', [ $called, 'init_settings' ], 15 );
		add_action( 'wp_ajax_sirsc_adon_is_execute_bulk_rename', [ $called, 'execute_bulk_rename' ] );
		add_action( 'wp_generate_attachment_metadata', [ $called, 'process_rename_after_file_uploaded' ], 99, 2 );
		add_action( 'sirsc_seo_after_file_renamed', [ $called, 'trace_rename_changes' ], 30, 3 );
		add_filter( 'get_attached_media_args', [ $called, 'get_attached_media_sorted' ], 30, 3 );

		if ( is_admin() ) {
			add_action( 'admin_menu', [ $called, 'images_admin_menu' ], 20 );
			add_action( 'add_meta_boxes', [ $called, 'rename_metaboxes' ] );
			add_action( 'admin_enqueue_scripts', [ $called, 'load_admin_assets' ], 1 );
			self::init_buttons();
		}
	}

	/**
	 * Get available filtered post types and settings.
	 */
	public static function init_settings_types() {
		self::get_types();
		self::get_settings();
	}

	/**
	 * Init the adon main buttons.
	 */
	public static function init_buttons() {
		do_action( 'sirsc/iterator/setup_buttons', 'sirsc-is', [
			'rename' => [
				'icon'     => '<span class="dashicons dashicons-image-rotate"></span>',
				'text'     => __( 'Bulk rename', 'sirsc' ),
				'callback' => 'sirscIsBulkRename()',
				'buttons'  => [ 'stop', 'resume', 'cancel' ],
				'class'    => '',
			],
		] );
	}

	/**
	 * Get available filtered post types and settings.
	 */
	public static function init_settings() {
		self::init_settings_types();
		$settings       = self::$settings;
		$settings_nonce = filter_input( INPUT_POST, '_sirsc_imgseo_settings_nonce', FILTER_DEFAULT );
		if ( ! empty( $settings_nonce ) && wp_verify_nonce( $settings_nonce, '_sirsc_imgseo_settings_action' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				// Maybe update settings.
				$set = filter_input( INPUT_POST, '_sirsc_imgseo_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

				$settings['types']  = ( empty( $set['types'] ) ) ? [] : array_keys( $set['types'] );
				$settings['upload'] = ( empty( $set['upload'] ) ) ? [] : array_keys( $set['upload'] );
				$settings['bulk']   = ( empty( $set['bulk'] ) ) ? [] : array_keys( $set['bulk'] );

				$settings['override_title']     = ( ! empty( $set['override_title'] ) ) ? true : false;
				$settings['override_filename']  = ( ! empty( $set['override_filename'] ) ) ? true : false;
				$settings['track_initial']      = ( ! empty( $set['track_initial'] ) && ! empty( $set['override_filename'] ) ) ? true : false;
				$settings['override_alt']       = ( ! empty( $set['override_alt'] ) ) ? true : false;
				$settings['override_permalink'] = ( ! empty( $set['override_permalink'] ) ) ? true : false;

				update_option( 'sirsc_adon_images_seo_settings', $settings );
				self::init_settings_types();
			}
		}
	}

	/**
	 * Get available filtered post types.
	 */
	public static function get_types() {
		$types      = [];
		$post_types = get_post_types( [], 'objects' );
		if ( ! empty( $post_types ) ) {
			$list = wp_list_pluck( $post_types, 'label', 'name' );
			if ( ! empty( $list ) ) {
				foreach ( $list as $type => $label ) {
					if ( in_array( $type, \SIRSC::$exclude_post_type, true ) && 'attachment' !== $type ) {
						continue;
					}

					if ( 'attachment' === $type || post_type_supports( $type, 'thumbnail' ) ) {
						$types[ $type ] = $label;
					}
				}
			}
		}

		if ( ! empty( $types['product'] ) ) {
			$types['product_variation'] = $list['product_variation'];
		}

		self::$post_types = $types;
	}

	/**
	 * Get current settings of the plugin.
	 */
	public static function get_settings() {
		$settings = get_option( 'sirsc_adon_images_seo_settings', [] );
		$defaults = [
			'types'              => [],
			'upload'             => [],
			'bulk'               => [],
			'track_initial'      => true,
			'override_title'     => true,
			'override_filename'  => true,
			'override_alt'       => true,
			'override_permalink' => true,
		];
		$settings = wp_parse_args( $settings, $defaults );

		self::$settings = $settings;
	}

	/**
	 * Enqueue the custom styles.
	 */
	public static function load_admin_assets() {
		$uri = $_SERVER['REQUEST_URI']; // phpcs:ignore
		if ( ! substr_count( $uri, 'page=sirsc-adon-images-seo' ) && ! substr_count( $uri, 'post.php' ) ) {
			// Fail-fast, the assets should not be loaded.
			return;
		}

		$dir = SIRSC_URL . 'build/adons/images-seo/';
		$ver = \SIRSC\get_build_ver();
		wp_enqueue_script( 'sirsc-adons-is', $dir . 'index.js', [ 'sirsc-iterator' ], $ver, true );
		wp_enqueue_style( 'sirsc-adons-is', $dir . 'style.css', [], $ver, false );
	}

	/**
	 * Do some custom processing then return back the attachment metadata.
	 *
	 * @param array $metadata      Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 */
	public static function process_rename_after_file_uploaded( $metadata = [], $attachment_id = 0 ) { // phpcs:ignore
		if ( ! empty( $attachment_id ) && defined( 'DOING_SIRSC' ) ) {
			\SIRSC\Helper\debug( 'ATTEMPT TO RENAME files for ' . $attachment_id, true, true );
			$post = get_post( $attachment_id );
			if ( ! empty( $post->post_parent ) ) {
				if ( empty( self::$settings ) ) {
					self::get_settings();
				}
				if ( empty( self::$settings['upload'] ) ) {
					// Fail-fast, no upload settings.
					return $metadata;
				}
				$type = get_post_type( $post->post_parent );
				if ( ! in_array( $type, self::$settings['upload'], true ) ) {
					// Fail-fast, not a targeted type.
					return $metadata;
				}
				$title = get_the_title( $post->post_parent );
				if ( ! empty( $title ) ) {
					self::rename_image_filename( $attachment_id, $title, 0, 'attachment', false );
				}
			}

			// Re-fetch the latest metadata.
			$metadata = wp_get_attachment_metadata( $attachment_id );
			\SIRSC\Helper\debug( 'RENAME FINISHED for attachment ' . $attachment_id, true, true );
		}

		// This is what the filter expects back.
		return $metadata;
	}

	/**
	 * Rename image filename.
	 *
	 * @param int    $id      The attachment ID.
	 * @param string $title   The "parent" post title.
	 * @param int    $count   Perhaps a counter suffix for the image.
	 * @param string $type    The "parent" post type.
	 * @param bool   $output  Output the result or not.
	 * @param string $message The extra message.
	 */
	public static function rename_image_filename( $id, $title, $count, $type, $output = true, $message = '' ) { // phpcs:ignore
		$meta  = wp_get_attachment_metadata( $id );
		$title = apply_filters( 'sirsc_seo_title_before_rename_file', $title, $id, $meta );

		if ( ! empty( $meta['file'] ) && ! empty( $title ) && \SIRSC\Helper\file_is_image( $meta['file'] ) ) {
			$upls = wp_upload_dir();
			if ( empty( self::$settings ) ) {
				self::get_settings();
			}

			$extra_hints = [];
			if ( ! empty( self::$settings['track_initial'] ) ) {
				$was_tracked = get_post_meta( $id, '_seoimg_initial_filename' );
				if ( empty( $was_tracked ) ) {
					// Only the first time.
					$finfo = pathinfo( $meta['file'] );
					update_post_meta( $id, '_seoimg_initial_filename', basename( $meta['file'] ) );
					update_post_meta( $id, '_seoimg_initial_filepath', $finfo['dirname'] );
					$extra_hints[] = __( 'The initial filename was recorded.', 'sirsc' );
				}
			}
			if ( ! empty( self::$settings['override_alt'] ) ) {
				update_post_meta( $id, '_wp_attachment_image_alt', $title );
				$extra_hints[] = sprintf(
					// Translators: %s - attribute.
					__( 'The attachment %s was updated.', 'sirsc' ),
					__( 'alternative text', 'sirsc' )
				);
			}
			if ( ! empty( self::$settings['override_title'] ) ) {
				wp_update_post( [
					'ID'         => $id,
					'post_title' => $title,
				] );
				$extra_hints[] = sprintf(
					// Translators: %s - attribute.
					__( 'The attachment %s was updated.', 'sirsc' ),
					__( 'title', 'sirsc' )
				);
			}
			if ( ! empty( self::$settings['override_permalink'] ) ) {
				wp_update_post( [
					'ID'        => $id,
					'post_name' => sanitize_title( $title ),
				] );
				$extra_hints[] = sprintf(
					// Translators: %s - attribute.
					__( 'The attachment %s was updated.', 'sirsc' ),
					__( 'permalink', 'sirsc' )
				);
			}

			$basedir      = trailingslashit( $upls['basedir'] );
			$old_path     = $basedir . $meta['file'];
			$renamed      = '<b class="dashicons dashicons-dismiss"></b>';
			$tmp_name     = '';
			$result_class = 'sirsc-info';

			// Translators: %s - path.
			$change_log = '<ul><li>' . sprintf( __( 'No changes for %s', 'sirsc' ), $old_path ) . '</li></ul>';

			if ( ! empty( self::$settings['override_filename'] ) ) {
				$maybe_type = wp_check_filetype( $meta['file'] );
				$tmp_name   = self::generate_filename(
					trailingslashit( $basedir . dirname( $meta['file'] ) ),
					$title,
					$maybe_type['ext'],
					$count >= 1 ? 0 : $count,
					$old_path
				);

				$filename     = $tmp_name;
				$new_filename = $filename . '.' . $maybe_type['ext'];
				$subdir       = trailingslashit( dirname( $meta['file'] ) );
				$new_path     = trailingslashit( $basedir . $subdir ) . $new_filename;
				$new_meta     = $meta;
				$base_one     = wp_basename( $meta['file'] );
				$renamed      = '<b class="dashicons dashicons-dismiss"></b>';

				// Translators: %s - path.
				$change_log = '<ul><li>' . sprintf( __( 'No changes for %s', 'sirsc' ), $old_path ) . '</li></ul>';

				if ( $old_path === $new_path ) {
					$renamed = '<b class="dashicons dashicons-yes-alt"></b>';
				} elseif ( ! empty( $new_path ) && ! is_dir( $new_path ) && ! file_exists( $new_path ) ) {
					$renamed      = '<b class="dashicons dashicons-dismiss error"></b>';
					$result_class = 'warning sirsc-warning';
					if ( $old_path !== $new_path && @rename( $old_path, $new_path ) ) { // phpcs:ignore
						$new_meta['file'] = $subdir . $new_filename;

						if ( ! empty( $meta['original_image'] ) && $meta['original_image'] !== $meta['file'] ) {
							$orig_old_path = $basedir . $subdir . $meta['original_image'];
							$orig_new_path = $basedir . $subdir . $new_filename;
							@rename( $orig_old_path, $orig_new_path ); // phpcs:ignore
							$new_meta['original_image'] = $new_filename;
						}

						$change_log = '';
						$size_count = 0;
						if ( ! empty( $meta['sizes'] ) ) {
							foreach ( $meta['sizes'] as $size => $image ) {
								if ( ! empty( $image['file'] )
									&& ( $base_one === $image['file'] || $new_filename === $image['file'] ) ) {
									// The file is the same as the full size or already renamed.
									$new_meta['sizes'][ $size ]['file'] = $new_filename;

									++$size_count;
									$change_log .= '<li class="sirsc_imgseo-toggle is-hidden">' . $old_path . ' -> ' . $new_path . '</li>';
								} else {
									// This is a regular image size.
									$fname    = $filename . '-' . $image['width'] . 'x' . $image['height'] . '.' . $maybe_type['ext'];
									$size_old = $basedir . $subdir . $image['file'];
									$size_new = $basedir . $subdir . $fname;
									if ( file_exists( $size_old ) ) {
										@rename( $size_old, $size_new ); // phpcs:ignore
										do_action( 'sirsc_seo_file_renamed', $id, $size_old, $size_new );

										++$size_count;
										$change_log .= '<li class="sirsc_imgseo-toggle is-hidden">' . $size_old . ' -> ' . $size_new . '</li>';
									}
									$new_meta['sizes'][ $size ]['file'] = $fname;
								}
							}
						}

						$maybe_toggle = '';
						if ( ! empty( $size_count ) ) {
							$maybe_toggle = '<p class="sirsc-imgseo-toggler"><b>' . sprintf(
								// Translators: %1$d - image sized replaced.
								__( ' + %1$d more image sizes that were found for this', 'sirsc' ),
								$size_count
							) . ' <span class="dashicons dashicons-arrow-down-alt2"></span> </b></p>';
						}
						$change_log = '
						<ul>
							<li>' . $old_path . ' -> ' . $new_path . $maybe_toggle . '</li>
							' . $change_log . '
						</ul>';

						wp_update_attachment_metadata( $id, $new_meta );
						update_post_meta( $id, '_wp_attached_file', $subdir . $new_filename );
						$renamed      = '<b class="dashicons dashicons-yes-alt success"></b>';
						$result_class = 'sirsc-success';

						do_action( 'sirsc_seo_after_file_renamed', $id, $meta, $new_meta );
					}
				}
			}

			$change_log = str_replace( $basedir, '', $change_log );
			$change_log = str_replace( $tmp_name, '<b>' . $tmp_name . '</b>', $change_log );

			if ( true === $output ) {
				if ( ! empty( $extra_hints ) ) {
					$extra      = '<li>' . implode( ' &bull; ', $extra_hints ) . '</li>';
					$change_log = str_replace( '</ul>', $extra . '</ul>', $change_log );
				}
				echo '<div class="file-info sirsc_imgseo-item-processed as-box sirsc_imgseo-label-wrap-' . $type . ' ' . $result_class . '"><div class="label-row"><span>' . esc_html__( 'Attachment ID', 'sirsc' ) . ' <b>' . $id . '</b></span>' . $renamed . '<label class="sirsc_imgseo-label-info">' . $type . '</label></div><div>' . esc_html__( 'New Title', 'sirsc' ) . ' <strong>' . $title . '</strong></div><div class="small-font">' . $change_log . $message . '</div></div>'; // phpcs:ignore
			}

			// Attempt to clear the attachment cache.
			clean_post_cache( $id );
			clean_attachment_cache( $id );
		}
	}

	/**
	 * Add the plugin menu.
	 */
	public static function rename_metaboxes() {
		global $post;

		if ( ! empty( self::$settings['types'] ) && ! empty( $post->ID ) ) {
			if ( 'attachment' === $post->post_type && ! \SIRSC\Helper\file_is_image( $post->guid ) ) {
				// Nor an image type.
				return;
			}

			add_meta_box(
				'sirsc_imgseo_rename_meta',
				__( 'Images SEO', 'sirsc' ),
				[ get_called_class(), 'rename_metaboxes_meta' ],
				self::$settings['types'],
				'side',
				'default'
			);
		}
	}

	/**
	 * Exposes the buttons info in the attachemnt edit page sidebar box.
	 */
	public static function rename_metaboxes_meta() {
		global $post;
		if ( empty( $post->ID ) ) {
			return;
		}
		?>
		<div class="sirsc_imgseo_meta sirsc-feature">
			<p>
				<?php
				if ( 'attachment' === $post->post_type ) {
					esc_html_e( 'You can rename this attachment files (including the files generated as image sizes) and other attributes.', 'sirsc' );
				} else {
					esc_html_e( 'You can rename and update attributes of some of the files already uploaded or attached to this post.', 'sirsc' );
				}
				?>
			</p>

			<div class="sirsc-buttons">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ADON_PAGE_SLUG ) ); ?>" class="button has-icon button-secondary">
					<span class="dashicons dashicons-admin-plugins"></span>
					<span><?php esc_html_e( 'Settings', 'sirsc' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ADON_PAGE_SLUG . '&tab=rename&target=' . $post->ID ) ); ?>" class="button has-icon button-primary last">
					<span class="dashicons dashicons-image-rotate-right"></span>
					<span><?php esc_html_e( 'Images SEO', 'sirsc' ); ?></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Add the plugin menu.
	 */
	public static function images_admin_menu() {
		add_submenu_page(
			'image-regenerate-select-crop-settings',
			__( 'Images SEO', 'sirsc' ),
			__( 'Images SEO', 'sirsc' ),
			'manage_options',
			self::ADON_PAGE_SLUG,
			[ get_called_class(), 'images_settings' ]
		);
	}

	/**
	 * Add the plugin menu.
	 */
	public static function images_settings() {
		$tab = filter_input( INPUT_GET, 'tab', FILTER_DEFAULT );
		$id  = filter_input( INPUT_GET, 'target', FILTER_VALIDATE_INT );

		SIRSC_Adons::check_adon_valid( self::ADON_SLUG );
		$desc = SIRSC_Adons::get_adon_details( self::ADON_SLUG, 'description' );

		$settings = self::$settings;
		if ( empty( self::$post_types ) ) {
			self::init_settings();
			$settings = self::$settings;
		}
		?>
		<div class="wrap sirsc-settings-wrap sirsc-feature">
			<?php \SIRSC\Admin\show_plugin_top_info(); ?>
			<?php \SIRSC\Admin\maybe_all_features_tab(); ?>
			<?php \SIRSC\admin\addon_intro( __( 'Images SEO', 'sirsc' ), $desc, 'adon-images-seo-image.png' ); ?>

			<div class="sirsc-tabbed-menu-content">
				<div class="intro-next outside menu-wrap">
					<div class="tabs-container">
						<div class="tabs-wrap" tabindex="0">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ADON_PAGE_SLUG ) ); ?>"class="button sirsc-button <?php if ( empty( $tab ) ) : ?>
								button-primary on<?php endif; ?>"
								><?php esc_html_e( 'Settings', 'sirsc' ); ?></a>
							<?php if ( ! empty( $settings['bulk'] ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ADON_PAGE_SLUG . '&tab=bulk-rename' ) ); ?>" class="button <?php if ( 'bulk-rename' === $tab ) : ?>
									button-primary on<?php endif; ?>"
									><?php esc_html_e( 'Bulk rename images', 'sirsc' ); ?></a>
							<?php endif; ?>
							<?php if ( ! empty( $id ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ADON_PAGE_SLUG . '&tab=rename&target=' . $id ) ); ?>" class="button <?php if ( 'rename' === $tab ) : ?>
								button-primary on<?php endif; ?>"
								><?php esc_html_e( 'Rename images', 'sirsc' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="sirsc-tabbed-menu-content">
					<?php
					if ( empty( $tab ) ) {
						self::form_settings_output();
					} elseif ( 'bulk-rename' === $tab ) {
						self::form_bulk_rename_output();
					} elseif ( ! empty( $id ) && 'rename' === $tab ) {
						self::form_rename_output( $id );
					}
					?>
				</div>

				<?php require_once __DIR__ . '/parts/seo-log.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the plugin settings form.
	 */
	public static function form_settings_output() {
		$types = self::$post_types;
		if ( empty( $types ) ) {
			self::init_settings();
			$types = self::$post_types;
		}

		$settings = self::$settings;
		?>
		<form action="" method="post" autocomplete="off" id="js-sirsc_imgseo-frm-settings">
			<?php wp_nonce_field( '_sirsc_imgseo_settings_action', '_sirsc_imgseo_settings_nonce' ); ?>
			<?php require_once __DIR__ . '/parts/settings.php'; ?>
		</form>
		<?php
	}

	/**
	 * Outputs the bulk rename form.
	 */
	public static function form_bulk_rename_output() {
		$settings = self::$settings;
		if ( ! empty( $settings['bulk'] ) ) {
			$settings['bulk'] = array_diff( $settings['bulk'], [ 'attachment' ] );
		}

		$types = $settings['bulk'];
		$bulk  = filter_input( INPUT_POST, '_sirsc_imgseo_bulk_update', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		?>
		<form action="" method="post" autocomplete="off">
			<?php wp_nonce_field( '_sirsc_imgseo_bulk_action', '_sirsc_imgseo_bulk_nonce' ); ?>
			<?php require_once __DIR__ . '/parts/bulk-rename-output.php'; ?>
		</form>
		<?php
	}

	/**
	 * Maybe run the individual rename.
	 */
	public static function maybe_rename_form_execute() {
		$rename = filter_input( INPUT_POST, '_sirsc_imgseo_dorename_nonce', FILTER_DEFAULT );

		if ( ! empty( $rename ) && wp_verify_nonce( $rename, '_sirsc_imgseo_dorename_action' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$type  = filter_input( INPUT_POST, 'sirsc_imgseo_type', FILTER_DEFAULT );
				$title = filter_input( INPUT_POST, 'sirsc_imgseo-renamefile-title', FILTER_DEFAULT );
				$id    = filter_input( INPUT_POST, 'sirsc_imgseo_id', FILTER_VALIDATE_INT );
				if ( ! empty( $id ) ) {
					if ( 'attachment' === $type ) {
						?>
						<hr>
						<h2><?php esc_html_e( 'Rename result', 'sirsc' ); ?></h3>
						<div id="sirsc_imgseo-images-process-wrap" class="as-row">
							<?php self::rename_image_filename( $id, $title, 0, $type ); ?>
						</div>
						<?php
					} else {
						?>
						<hr>
						<h2><?php esc_html_e( 'Rename result', 'sirsc' ); ?>
						</h2>
						<div id="sirsc_imgseo-images-process-wrap" class="as-row columns-2">
							<?php self::regenerate_filenames_by_post( $id, $title ); ?>
						</div>
						<?php
					}
				} else {
					esc_html_e( 'This feature works when you select an image.', 'sirsc' );
				}
			}
		}
	}

	/**
	 * Maybe initiate the bulk rename process.
	 */
	public static function maybe_bulk_rename_form_execute() {
		$bulk = filter_input( INPUT_GET, '_sirsc_imgseo_bulk_update', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $bulk ) ) {
			self::maybe_bulk_process_form( $bulk );
		} else {
			?>
			<p><?php esc_html_e( 'If you want to start the bulk rename of images, you have to select at least one post type, then click the bulk rename button.', 'sirsc' ); ?></p>
			<hr>
			<p>
				<?php esc_html_e( 'If you have a large set of files, we recommend using the command line tools for performance and speed reasons. If you can run commands from the terminal, you could use the wp-cli command. When you run the command it will prompt for you to enter the site id (or 1 if you are not using a multi-site) and the post type for which you want to bulk rename the files (if multiple, then separate these with commas).', 'sirsc' ); ?>
				<em><?php esc_html_e( 'Ex: wp sirsc seorename 1 product,product_variation --content-replace=each', 'sirsc' ); ?></em>
			</p>
			<pre class="code sirsc-wpcli">wp sirsc seorename</pre>
			<p><?php esc_html_e( 'Make sure you back up your files and database before running any bulk action, some changes cannot be reverted from the browser.', 'sirsc' ); ?></p>
			<?php
		}
	}

	/**
	 * Execute the processing of each items batch rename.
	 */
	public static function execute_bulk_rename() {
		\SIRSC\Iterator\is_valid_ajax();

		$bulk_type = filter_input( INPUT_GET, 'bulk_types', FILTER_DEFAULT );
		$iterator  = filter_input( INPUT_GET, 'iterator', FILTER_DEFAULT );
		if ( empty( $bulk_type ) ) {
			?>
			<p class="sirsc-message warning"><?php esc_html_e( 'If you want to start the bulk rename of images, you have to select at least one post type, then click the bulk rename button.', 'sirsc' ); ?></p>
			<?php
			\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-is-rename', 'reset' ) );

			wp_die();
			die();
		} else {
			global $wpdb;
			$option = get_option( 'sirsc_adons_is_bulk_rename', [] );
			if ( 'start' === $iterator || empty( $option['total'] ) ) {
				$total  = $wpdb->get_var( self::rename_get_query( $bulk_type, 0, true ) ); // phpcs:ignore
				$option = [
					'types'     => $bulk_type,
					'total'     => $total,
					'last_id'   => 0,
					'processed' => 0,
				];
				update_option( 'sirsc_adons_is_bulk_rename', $option );

				if ( empty( $total ) ) {
					?>
					<p class="sirsc-message warning"><?php esc_html_e( 'Nothing to be processed.', 'sirsc' ); ?></p>
					<?php
					\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-is-rename', 'reset' ) );

					wp_die();
					die();
				}
			}

			$option = get_option( 'sirsc_adons_is_bulk_rename', [] );
			?>
			<div class="label-row as-title">
				<h2><?php esc_html_e( 'Bulk renaming files', 'sirsc' ); ?></h2>
			</div>
			<?php
			$percent = 0;
			if ( ! empty( $option['total'] ) ) {
				$percent = ceil( $option['processed'] * 100 / $option['total'] );
			}
			self::show_progress_bar( $option['processed'], $percent, $option['total'], false );
			?>

			<?php
			if ( 'finish' === $iterator || 'cancel' === $iterator ) {
				update_option( 'sirsc_adons_is_bulk_rename', [] );
				echo '<p class="sirsc-message success">';
				esc_html_e( 'The identified images were renamed.', 'sirsc' );
				echo '</p>';
				wp_die();
				die();
			}
			?>

			<div id="sirsc-feature-files-renamed" class="as-row no-margin">
				<?php
				if ( ! empty( $option['total'] ) ) {
					$rows = $wpdb->get_results( self::rename_get_query( $option['types'], $option['last_id'] ) ); // phpcs:ignore
					if ( ! empty( $rows ) ) {
						foreach ( $rows as $row ) {
							$option['last_id'] = (int) $row->ID;
							if ( 0 === self::RENAME_QUERY_TYPE ) {
								self::regenerate_filenames_by_post( $row->ID );
							} else {
								$info = self::assess_attachment_title( $row, $option['types'] );
								self::rename_image_filename( $row->ID, $info['title'], 0, $info['parent_type'], true, $info['message'] );
							}
							++$option['processed'];
						}
						update_option( 'sirsc_adons_is_bulk_rename', $option );
					}
				}
				?>
			</div>

			<?php
			if ( ! empty( $option['total'] ) && (int) $option['total'] === (int) $option['processed'] ) {
				\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-is-rename', 'finish' ) . ' sirscIsBulkRenameFinish(\'' . __( 'The identified images were renamed.', 'sirsc' ) . '\');', true );
				wp_die();
				die();
			} else {
				\SIRSC\Helper\the_document_ready_js( \SIRSC\Iterator\button_callback( 'sirsc-is-rename', 'continue' ), true );
			}
		}

		wp_die();
		die();
	}

	/**
	 * Compute the rename query.
	 *
	 * @param  string $type     The post types list.
	 * @param  int    $prev     A previous processed attachment/post ID.
	 * @param  bool   $is_count The query is for count.
	 * @return string
	 */
	public static function rename_get_query( $type, $prev = 0, $is_count = false ) { // phpcs:ignore
		global $wpdb;

		$types        = explode( ',', $type );
		$use_products = ( in_array( 'product', $types, true ) ) ? true : false;

		if ( 0 === self::RENAME_QUERY_TYPE ) {
			if ( true === $is_count ) {
				$query = $wpdb->prepare(
					'SELECT count(p.ID)
					 FROM ' . $wpdb->posts . ' as p
					 INNER JOIN ' . $wpdb->postmeta . ' as pm ON (p.ID = pm.post_id AND ( pm.meta_key = %s OR pm.meta_key = %s ) )
					 LEFT OUTER JOIN ' . $wpdb->posts . ' as a ON ( a.post_parent = p.ID )
					 WHERE FIND_IN_SET( p.post_type, %s )
					 AND ( pm.meta_value IS NOT NULL OR a.post_type = %s ) ',
					'_thumbnail_id',
					'_product_image_gallery',
					$type,
					'attachment'
				);
			} else {
				$query = $wpdb->prepare(
					'SELECT p.ID, p.post_title as parent_title FROM ' . $wpdb->posts . ' as p
					 INNER JOIN ' . $wpdb->postmeta . ' as pm ON (p.ID = pm.post_id AND ( pm.meta_key = %s OR pm.meta_key = %s ) )
					 LEFT OUTER JOIN ' . $wpdb->posts . ' as a ON ( a.post_parent = p.ID )
					 WHERE FIND_IN_SET( p.post_type, %s )
					 AND ( pm.meta_value IS NOT NULL OR a.post_type = %s )
					 AND p.ID > %d ORDER BY p.ID ASC LIMIT 0, %d',
					'_thumbnail_id',
					'_product_image_gallery',
					$type,
					'attachment',
					$prev,
					self::PROCESS_BATCH_SIZE
				);
			}
		} else {
			$qstr = '';
			$args = [];

			// The attachments set as featured.
			$qstr = '
			(
				SELECT a.ID as ID, a.post_title as attachment_title, a.post_parent as post_parent
				FROM ' . $wpdb->posts . ' as a
				INNER JOIN ' . $wpdb->postmeta . ' as pm ON (pm.meta_value = a.ID and pm.meta_key = %s)
				INNER JOIN ' . $wpdb->posts . ' as thp ON (pm.post_id = thp.ID)
				WHERE a.post_type = %s
				AND thp.post_title IS NOT NULL
				AND FIND_IN_SET(thp.post_type, %s)
				AND (thp.post_status != %s AND thp.post_status != %s)
			)';

			$args[] = '_thumbnail_id';
			$args[] = 'attachment';
			$args[] = $type;
			$args[] = 'trash';
			$args[] = 'auto-draft';

			// The attachments set as media (children).
			$qstr .= '
			UNION
			(
				SELECT a.ID as ID, a.post_title as attachment_title, a.post_parent as post_parent
				FROM ' . $wpdb->posts . ' as a
				INNER JOIN ' . $wpdb->posts . ' as pp ON (pp.ID = a.post_parent)
				WHERE a.post_type = %s
				AND pp.post_title IS NOT NULL
				AND FIND_IN_SET(pp.post_type, %s)
				AND (pp.post_status != %s AND pp.post_status != %s)
			)';

			$args[] = 'attachment';
			$args[] = $type;
			$args[] = 'trash';
			$args[] = 'auto-draft';

			if ( true === $use_products ) {
				// The product gallery images.
				$qstr .= '
				UNION
				(
					SELECT a.ID as ID, a.post_title as attachment_title, a.post_parent as post_parent
					FROM ' . $wpdb->posts . ' as a
					INNER JOIN ' . $wpdb->postmeta . ' as pm2 ON (pm2.meta_value = a.ID and pm2.meta_key = %s)
					INNER JOIN ' . $wpdb->posts . ' as pr ON (pm2.post_id = pr.ID)
					WHERE a.post_type = %s
					AND pr.post_title IS NOT NULL
					AND pr.post_type = %s
					AND (pr.post_status != %s AND pr.post_status != %s)
				)
				';

				$args[] = '_product_image_gallery';
				$args[] = 'attachment';
				$args[] = 'product';
				$args[] = 'trash';
				$args[] = 'auto-draft';
			}

			if ( true === $is_count ) {
				$qstr  = ' SELECT count(u.ID) FROM ( ' . $qstr . ') as u ';
				$query = $wpdb->prepare( $qstr, $args ); // phpcs:ignore
			} else {
				$qstr   = ' SELECT * FROM ( ' . $qstr . ') as u WHERE u.ID > %d ORDER BY u.ID ASC LIMIT 0, %d ';
				$args[] = $prev;
				$args[] = self::PROCESS_BATCH_SIZE;
				$query  = $wpdb->prepare( $qstr, $args ); // phpcs:ignore
			}
		}

		return $query;
	}

	/**
	 * Show a progress bar.
	 *
	 * @param int $items_proc The total items processed.
	 * @param int $processed  The percent processed.
	 * @param int $total      The total.
	 * @param int $batch      The current batch count.
	 */
	public static function show_progress_bar( $items_proc = 0, $processed = 0, $total = 0, $batch = 0 ) { // phpcs:ignore
		$text = esc_html( sprintf(
			// Translators: %1$d - count products, %2$d - total.
			__( 'Processed the filename replacement for %1$d items of %2$d.', 'sirsc' ),
			$items_proc,
			$total
		) );

		\SIRSC\Helper\progress_bar( $total, $items_proc, true, $text );
	}

	/**
	 * Assess attachment potential title by priority.
	 *
	 * @param  object $row  Attachment info.
	 * @param  string $type Query post type.
	 * @return string
	 */
	public static function assess_attachment_title( $row, $type ) { // phpcs:ignore
		// Assess in the order of priority.
		$query_args = [
			'post_type'   => explode( ',', $type ),
			'post_status' => 'any',
			'meta_query'  => [ // phpcs:ignore
				[
					'key'   => '_thumbnail_id',
					'value' => $row->ID,
				],
			],
			'numberposts' => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		];

		$query = new WP_Query( $query_args );
		if ( ! empty( $query->posts[0]->post_title ) ) {
			return [
				'title'       => $query->posts[0]->post_title,
				'message'     => __( 'The image inherited the title from the post that is using this as featured image.', 'sirsc' ),
				'parent_type' => $query->posts[0]->post_type,
			];
		}

		// Assess if the image is used in a product gallery.
		$query_args = [
			'post_type'   => explode( ',', $type ),
			'post_status' => 'any',
			'meta_query'  => [ // phpcs:ignore
				[
					'key'     => '_product_image_gallery',
					'value'   => $row->ID,
					'compare' => 'LIKE',
				],
			],
			'numberposts' => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		];

		$query = new WP_Query( $query_args );
		if ( ! empty( $query->posts[0]->post_title ) ) {
			return [
				'title'       => $query->posts[0]->post_title,
				'message'     => __( 'The image inherited the title from the product that is using this as gallery image.', 'sirsc' ),
				'parent_type' => $query->posts[0]->post_type,
			];
		}

		// Assess if the image has a parent.
		if ( ! empty( $row->post_parent ) ) {
			$query_args = [
				'post_type'   => explode( ',', $type ),
				'post_status' => 'any',
				'post__in'    => [ $row->post_parent ],
				'numberposts' => 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			];

			$query = new WP_Query( $query_args );
			if ( ! empty( $query->posts[0]->post_title ) ) {
				return [
					'title'       => $query->posts[0]->post_title,
					'message'     => __( 'The image inherited the title from the post parent of the image.', 'sirsc' ),
					'parent_type' => $query->posts[0]->post_type,
				];
			}
		}

		// Assess if the attachment title is used.
		if ( ! empty( $row->attachment_title ) ) {
			return [
				'title'       => $row->attachment_title,
				'message'     => __( 'The image inherited the title from the attachment title.', 'sirsc' ),
				'parent_type' => 'attachment',
			];
		}

		return [
			'title'       => '',
			'message'     => '',
			'parent_type' => '',
		];
	}

	/**
	 * Attempt to generate a unique filename.
	 *
	 * @param  string $dir     Base directory.
	 * @param  string $title   Parent title.
	 * @param  string $type    Attachment mime type.
	 * @param  int    $count   A potential suffix.
	 * @param  string $initial The initial filename (with the path too).
	 * @return string
	 */
	public static function generate_filename( $dir, $title, $type, $count = 0, $initial = '' ) { // phpcs:ignore
		$new_filename = '';
		while ( '' === $new_filename ) {
			$suffix   = ! empty( $count ) ? '-' . $count : '';
			$maxlen   = 80 - strlen( $suffix . '.' . $type ) - 1;
			$filename = substr( sanitize_file_name( $title ), 0, $maxlen ) . $suffix;
			$filename = preg_replace( '/[^\x00-\x7F]/u', '', $filename ); // Remove non-unicode.
			$filename = trim( mb_strtolower( sanitize_title( $filename ) ) );

			if ( $dir . $filename . '.' . $type === $initial ) {
				$new_filename = $filename;
			}
			if ( ! empty( $filename ) && ! file_exists( $dir . $filename . '.' . $type ) ) {
				$new_filename = $filename;
			}
			++$count;
		}

		return $new_filename;
	}

	/**
	 * Outputs the rename form.
	 *
	 * @param int $id Post ID.
	 */
	public static function form_rename_output( $id ) { // phpcs:ignore
		$post = get_post( $id );
		if ( ! ( $post instanceof WP_Post ) ) {
			?>
			<div class="as-box bg-secondary no-gap">
				<?php esc_html_e( 'No target selected.', 'sirsc' ); ?>
			</div>
			<?php
			return;
		}
		?>
		<form action="" method="post" autocomplete="off">
			<?php wp_nonce_field( '_sirsc_imgseo_dorename_action', '_sirsc_imgseo_dorename_nonce' ); ?>
			<input type="hidden" name="sirsc_imgseo_type" value="<?php echo esc_attr( $post->post_type ); ?>">
			<input type="hidden" name="sirsc_imgseo_id" value="<?php echo (int) $id; ?>">

			<p><?php esc_html_e( 'Please note that any of the rename process options (on upload, manual rename, bulk rename) will override the attachment attributes based on the images SEO settings you made.', 'sirsc' ); ?></p>

			<?php
			if ( 'attachment' === $post->post_type ) {
				require_once __DIR__ . '/parts/rename-attachment.php';
			} else {
				require_once __DIR__ . '/parts/rename-other.php';
			}
			?>
		</form>
		<?php
	}

	/**
	 * Filters arguments used to retrieve media attached to the given post.
	 *
	 * @param  array   $args Post query arguments.
	 * @param  string  $type Mime type of the desired media.
	 * @param  WP_Post $post Post object.
	 * @return array
	 */
	public static function get_attached_media_sorted( $args, $type, $post ) { // phpcs:ignore
		$args['orderby'] = 'ID';

		return $args;
	}

	/**
	 * Identify the attachment filenames by post parent.
	 *
	 * @param  int $id Post ID.
	 * @return array
	 */
	public static function get_attachments_by_post( $id ) { // phpcs:ignore
		$all   = [];
		$upls  = wp_upload_dir();
		$base  = trailingslashit( $upls['baseurl'] );
		$items = [];
		$title = get_the_title( $id );
		$meta  = get_post_meta( $id, '_thumbnail_id', true );
		if ( ! empty( $meta ) ) {
			$filename = wp_get_attachment_image_src( (int) $meta, 'full' );
			$items[]  = [
				'type'      => 'featured image',
				'id'        => (int) $meta,
				'count'     => 0,
				'new_title' => $title,
				'filename'  => ! empty( $filename[0] ) ? str_replace( $base, '', $filename[0] ) : '',
			];

			$all[] = (int) $meta;
		}

		$count = 0;
		$meta  = get_post_meta( $id, '_product_image_gallery', true );
		if ( ! empty( $meta ) ) {
			$list = explode( ',', $meta );
			foreach ( $list as $iid ) {
				$iid = (int) $iid;
				if ( ! in_array( $iid, $all, true ) ) {
					$filename = wp_get_attachment_image_src( $iid, 'full' );
					$items[]  = [
						'type'      => 'gallery image',
						'id'        => $iid,
						'count'     => ++$count,
						'new_title' => $title,
						'filename'  => ! empty( $filename[0] ) ? str_replace( $base, '', $filename[0] ) : '',
					];

					$all[] = $iid;
				}
			}
		}

		$meta = get_attached_media( '', $id );
		if ( ! empty( $meta ) ) {
			foreach ( $meta as $obj ) {
				$iid = (int) $obj->ID;
				if ( ! in_array( $iid, $all, true ) ) {
					$filename = wp_get_attachment_image_src( $iid, 'full' );
					$items[]  = [
						'type'      => 'media',
						'id'        => $iid,
						'count'     => ++$count,
						'new_title' => $title,
						'filename'  => ! empty( $filename[0] ) ? str_replace( $base, '', $filename[0] ) : '',
					];

					$all[] = $iid;
				}
			}
		}

		$list = [];
		foreach ( $items as $item ) {
			if ( ! empty( $item['filename'] ) ) {
				$list[] = $item;
			}
		}

		uasort( $list, fn( $a, $b ) => $a['id'] <=> $b['id'] );
		return $list;
	}

	/**
	 * Identify filenames by post attachment id.
	 *
	 * @param  int $id Attachment ID.
	 * @return array
	 */
	public static function get_attachments_by_id( $id ) { // phpcs:ignore
		if ( ! empty( $id ) ) {
			$upls     = wp_upload_dir();
			$base     = trailingslashit( $upls['baseurl'] );
			$items    = [];
			$title    = get_the_title( $id );
			$filename = wp_get_attachment_image_src( (int) $id, 'full' );
			$items[]  = [
				'type'      => 'attachment',
				'id'        => (int) $id,
				'count'     => 0,
				'new_title' => $title,
				'filename'  => ! empty( $filename[0] ) ? str_replace( $base, '', $filename[0] ) : '',
			];
		}

		return $items;
	}

	/**
	 * Regenerate attachment filenames by post parent.
	 *
	 * @param int    $id    Post ID.
	 * @param string $title The expected title.
	 */
	public static function regenerate_filenames_by_post( $id, $title = '' ) { // phpcs:ignore
		$title = empty( $title ) ? get_the_title( $id ) : $title;
		$type  = get_post_type( $id );
		$items = self::get_attachments_by_post( $id );
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				self::rename_image_filename( $item['id'], $title, $item['count'], $type );
			}
		}
	}

	/**
	 * Trace filenames changes.
	 *
	 * @param int   $id       Attachment ID.
	 * @param array $old_meta Initial meta.
	 * @param array $new_meta Meta after rename.
	 */
	public static function trace_rename_changes( $id, $old_meta, $new_meta ) { // phpcs:ignore
		if ( ! empty( $old_meta ) && ! empty( $new_meta ) ) {
			if ( isset( $old_meta['file'] ) && isset( $new_meta['file'] )
				&& $old_meta['file'] !== $new_meta['file'] ) {
				\SIRSC\Debug\bulk_rename_log_write(
					'"' . $old_meta['file'] . '","' . $new_meta['file'] . '"'
				);
			}

			if ( isset( $old_meta['sizes'] ) && isset( $new_meta['sizes'] )
				&& $old_meta['sizes'] !== $new_meta['sizes'] ) {
				$info   = pathinfo( $old_meta['file'] );
				$prefix = trailingslashit( $info['dirname'] );
				foreach ( $old_meta['sizes'] as $size => $info ) {
					if ( isset( $new_meta['sizes'][ $size ]['file'] )
						&& $info['file'] !== $new_meta['sizes'][ $size ]['file'] ) {
						\SIRSC\Debug\bulk_rename_log_write(
							'"' . $prefix . $info['file'] . '","' . $prefix . $new_meta['sizes'][ $size ]['file'] . '"'
						);
					}
				}
			}
		}
	}
}

// Instantiate the class.
SIRSC_Adons_Images_SEO::get_instance();
