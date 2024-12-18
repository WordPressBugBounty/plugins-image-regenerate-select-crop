<?php
/**
 * Description: The adons component of the Image Regenerate & Select Crop plugin.
 *
 * @package sirsc
 */

/**
 * Adons class for SIRSC plugin.
 */
class SIRSC_Adons extends SIRSC_Image_Regenerate_Select_Crop {
	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Retrun the current instance.
	 *
	 * @return object
	 */
	public static function get_instance() { // phpcs:ignore
		if ( ! self::$instance ) {
			self::$instance = new SIRSC_Adons();
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
		$called = get_called_class();
		if ( is_admin() ) {
			add_action( 'init', [ $called, 'detect_menu_items' ] );
			add_action( 'init', [ $called, 'detect_adons' ] );
			add_action( 'init', [ $called, 'maybe_deal_with_adons' ] );
			add_action( 'admin_menu', [ $called, 'admin_menu' ], 30 );
		}

		if ( wp_doing_cron() ) {
			add_action( 'init', [ $called, 'detect_adons' ] );
		}
	}

	/**
	 * Detect menu items.
	 */
	public static function detect_menu_items() {
		self::$menu_items = [];

		self::$menu_items[ SIRSC_PAGE ] = [
			'slug'  => SIRSC_PAGE,
			'title' => __( 'General Settings', 'sirsc' ),
			'url'   => admin_url( 'admin.php?page=' . SIRSC_PAGE ),
			'icon'  => '',
		];

		self::$menu_items['image-regenerate-select-crop-rules'] = [
			'slug'  => 'image-regenerate-select-crop-rules',
			'title' => __( 'Advanced Rules', 'sirsc' ),
			'url'   => admin_url( 'admin.php?page=image-regenerate-select-crop-rules' ),
			'icon'  => '',
		];

		if ( ! empty( self::$settings['enable_debug_log'] ) ) {
			self::$menu_items['sirsc-debug'] = [
				'slug'  => 'sirsc-debug',
				'title' => __( 'Debug', 'sirsc' ),
				'url'   => admin_url( 'admin.php?page=sirsc-debug' ),
				'icon'  => '<span class="dashicons dashicons-admin-generic"></span>',
			];
		}

		self::$menu_items['image-regenerate-select-crop-extensions'] = [
			'slug'  => 'image-regenerate-select-crop-extensions',
			'title' => __( 'Extensions', 'sirsc' ),
			'url'   => admin_url( 'admin.php?page=image-regenerate-select-crop-extensions' ),
			'icon'  => '<span class="dashicons dashicons-admin-plugins"></span>',
		];
	}

	/**
	 * Detect menu items.
	 *
	 * @param array $item New menu item.
	 */
	public static function sirsc_add_menu_items( $item ) { // phpcs:ignore
		if ( empty( self::$menu_items[ $item['slug'] ] ) ) {
			self::$menu_items[ $item['slug'] ] = $item;
		}
	}

	/**
	 * Get adon details.
	 *
	 * @param  string $slug Adon slug.
	 * @param  string $prop Adon property.
	 * @return mixed
	 */
	public static function get_adon_details( $slug, $prop = '' ) { // phpcs:ignore
		if ( empty( $slug ) || empty( self::$adons[ $slug ] ) ) {
			return;
		}
		if ( ! empty( $prop ) ) {
			if ( ! empty( self::$adons[ $slug ][ $prop ] ) ) {
				return self::$adons[ $slug ][ $prop ];
			}
			// Retrun empty.
			return;
		}
		// Return all.
		return self::$adons[ $slug ];
	}

	/**
	 * Regenerate options.
	 */
	public static function regenerate_options() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( // phpcs:ignore
			' DELETE FROM ' . $wpdb->options . ' WHERE option_name like %s or option_name like %s ',
			'%sirsc_adon%',
			'%sirsc-adon%'
		) );
	}

	/**
	 * Predict adons.
	 *
	 * @return array
	 */
	public static function predict_adons(): array {
		$default = [
			'import-export'       => [
				'name'        => __( 'Import/Export', 'sirsc' ),
				'description' => __( 'Export and import the plugin settings from one instance to another (general settings, advanced rules, media settings, additional image sizes managed with this plugin).', 'sirsc' ),
				'icon'        => '<span class="dashicons dashicons-migrate"></span>',
				'available'   => true,
				'active'      => false,
				'free'        => true,
				'current_ver' => 1.0,
				'price'       => 0.00,
				'license_key' => '',
				'sku'         => '',
				'buy_url'     => '',
			],
			'images-seo'          => [
				'name'        => __( 'Images SEO', 'sirsc' ),
				'description' => __( 'Rename the images files (bulk rename, individual rename or image rename on upload) and override the attachments attributes based on the settings.', 'sirsc' ),
				'icon'        => '',
				'available'   => false,
				'active'      => false,
				'free'        => false,
				'current_ver' => 1.0,
				'price'       => 10.00,
				'license_key' => '',
				'sku'         => 'SIRSC.01',
				'buy_url'     => 'https://iuliacazan.ro/wordpress-extension/images-seo/',
			],
			'uploads-folder-info' => [
				'name'        => __( 'Uploads Folder Info', 'sirsc' ),
				'description' => __( 'See details about your uploads folder: number of files and folders (including subfolders), the total size.', 'sirsc' ),
				'icon'        => '',
				'available'   => false,
				'active'      => false,
				'free'        => false,
				'current_ver' => 1.0,
				'price'       => 4.00,
				'license_key' => '',
				'sku'         => 'SIRSC.02',
				'buy_url'     => 'https://iuliacazan.ro/wordpress-extension/uploads-folder-info/',
			],
			'uploads-inspector'   => [
				'name'        => __( 'Uploads Inspector', 'sirsc' ),
				'description' => __( 'Analyze the files from your uploads folder (including the orphaned files - not associated with attachment records in the database) and see details about the size, MIME type, attachment IDs, sub-sizes, etc.', 'sirsc' ),
				'icon'        => '',
				'available'   => false,
				'active'      => false,
				'free'        => false,
				'current_ver' => 1.0,
				'price'       => 6.00,
				'license_key' => '',
				'sku'         => 'SIRSC.03',
				'buy_url'     => 'https://iuliacazan.ro/wordpress-extension/uploads-inspector/',
			],
		];
		return $default;
	}

	/**
	 * Detect adons.
	 */
	public static function detect_adons() {
		$options     = get_option( 'sirsc_adons_list', [] );
		$default     = self::predict_adons();
		self::$adons = wp_parse_args( $options, $default );

		foreach ( self::$adons as $key => $value ) {
			if ( empty( $default[ $key ]['name'] ) ) {
				unset( self::$adons[ $key ] );
				continue;
			}

			self::$adons[ $key ]['name']        = $default[ $key ]['name'];
			self::$adons[ $key ]['free']        = $default[ $key ]['free'];
			self::$adons[ $key ]['description'] = $default[ $key ]['description'];
			self::$adons[ $key ]['price']       = $default[ $key ]['price'];
			self::$adons[ $key ]['buy_url']     = $default[ $key ]['buy_url'];
			if ( file_exists( SIRSC_ADONS_DIR . $key . '/class-sirsc-' . $key . '.php' ) ) {
				if ( ! empty( self::$adons[ $key ]['available'] ) && ! empty( self::$adons[ $key ]['active'] ) ) {
					self::sirsc_add_menu_items( [
						'slug'  => 'sirsc-adon-' . $key,
						'title' => $value['name'],
						'url'   => admin_url( 'admin.php?page=sirsc-adon-' . $key ),
						'icon'  => ! empty( $default[ $key ]['icon'] ) ? $default[ $key ]['icon'] : $value['icon'],
					] );
					include_once SIRSC_ADONS_DIR . $key . '/class-sirsc-' . $key . '.php';
				}
			}
		}
	}

	/**
	 * Add the new menu in tools section that allows to configure the image sizes restrictions.
	 */
	public static function admin_menu() {
		$adons_notice = '';
		$maybe_trans  = get_transient( SIRSC_NOTICE . '_adons_notice' );
		if ( ! empty( $maybe_trans ) ) {
			$adons_notice = '<span class="update-plugins count-4"><span class="plugin-count">4</span></span>';
		}

		add_submenu_page(
			'image-regenerate-select-crop-settings',
			__( 'Extensions', 'sirsc' ),
			__( 'Extensions', 'sirsc' ) . $adons_notice,
			'manage_options',
			'image-regenerate-select-crop-extensions',
			[ get_called_class(), 'features_manager' ]
		);

		global $submenu;
		if ( ! empty( $submenu['image-regenerate-select-crop-settings'] ) ) {
			$items = [];
			foreach ( $submenu['image-regenerate-select-crop-settings'] as $k => $item ) {
				if ( substr_count( $item[2], '-adon-' ) || substr_count( $item[2], 'options-media.php' ) ) {
					$item[0] = $item[0];
				}

				if ( substr_count( $item[2], '-adon-' ) ) {
					$items[ 20 + (int) $k ] = $item;
				} else {
					$items[ $k ] = $item;
				}
			}

			ksort( $items );
			$items[0][0] = __( 'General Settings', 'sirsc' );

			$submenu['image-regenerate-select-crop-settings'] = $items; // phpcs:ignore
		}
	}

	/**
	 * Check adon valid.
	 *
	 * @param string $slug Adon slug.
	 */
	public static function check_adon_valid( $slug ) { // phpcs:ignore
		$trans_id    = 'sirsc-adon-check-' . $slug;
		$maybe_trans = get_transient( $trans_id );
		if ( empty( $maybe_trans ) ) {
			if ( ! self::get_adon_details( $slug, 'free' ) ) {
				$sku = self::get_adon_details( $slug, 'sku' );
				$key = self::get_adon_details( $slug, 'license_key' );
				$id  = self::get_adon_details( $slug, 'activation_id' );
				SIRSC_Adons_API::validate_license_key( $slug, $sku, $key, $id );
			}
			set_transient( $trans_id, time(), 1 * HOUR_IN_SECONDS );
		}
	}

	/**
	 * Maybe deal with adons.
	 */
	public static function maybe_deal_with_adons() {
		$nonce = filter_input( INPUT_POST, '_sirsc_adon_box_nonce', FILTER_DEFAULT );
		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, '_sirsc_adon_box_action' ) ) {
			$error = 0;
			if ( current_user_can( 'manage_options' ) ) {
				// Maybe update settings.
				$slug = filter_input( INPUT_POST, 'sirsc-adon-slug', FILTER_DEFAULT );
				if ( ! empty( $slug ) ) {
					$attr         = self::adon_check( $slug );
					$activate_key = filter_input( INPUT_POST, 'sirsc-save-adon-activate-license-key', FILTER_DEFAULT );
					$license_key  = filter_input( INPUT_POST, 'license-key', FILTER_DEFAULT );
					$deactivate   = filter_input( INPUT_POST, 'sirsc-save-adon-deactivate', FILTER_DEFAULT );
					$activate     = filter_input( INPUT_POST, 'sirsc-save-adon-activate', FILTER_DEFAULT );
					if ( ! empty( $activate_key ) && ! empty( $license_key ) ) {
						$sku = self::get_adon_details( $slug, 'sku' );
						SIRSC_Adons_API::activate_license_key( $slug, $sku, $license_key );
					} elseif ( ! empty( $deactivate ) && 'deactivate' === $attr['action'] ) {
						SIRSC_Adons_API::update_adon_property( $slug, 'active', false );
					} elseif ( ! empty( $activate ) && 'activate' === $attr['action'] ) {
						$opt = self::$adons;
						delete_transient( 'sirsc-adon-check-' . $slug );
						self::check_adon_valid( $slug );
						$ava = self::get_adon_details( $slug, 'available' );
						if ( ! empty( $ava ) ) {
							SIRSC_Adons_API::update_adon_property( $slug, 'active', true );
						}
					}
					self::detect_adons();
					wp_safe_redirect( admin_url( 'admin.php?page=image-regenerate-select-crop-extensions' ) . '#sirsc_adon_box_' . $slug . '_frm' );
					exit;
				}
				wp_safe_redirect( admin_url( 'admin.php?page=image-regenerate-select-crop-extensions' ) );
				exit;
			}
		}

		$reset = filter_input( INPUT_GET, 'sirsc-adons-reset', FILTER_DEFAULT );
		if ( ! empty( $reset ) ) {
			self::regenerate_options();
			wp_safe_redirect( admin_url( 'admin.php?page=image-regenerate-select-crop-extensions' ) );
			exit;
		}
	}

	/**
	 * Adon check.
	 *
	 * @param  string $slug Adon slug.
	 * @return array
	 */
	public static function adon_check( $slug ) { // phpcs:ignore
		$attr = [
			'class'  => '',
			'action' => 'remove',
		];
		if ( ! empty( self::$adons[ $slug ] ) ) {
			$item   = self::$adons[ $slug ];
			$class  = '';
			$class .= ( ! empty( $item['available'] ) ) ? ' available' : ' unavailable';
			$class .= ( ! empty( $item['active'] ) ) ? ' active' : '';
			$class .= ( empty( $item['free'] ) ) ? ' purchase' : '';

			$attr['class'] = $class;
			if ( ! empty( $item['active'] ) ) {
				$attr['action'] = 'deactivate';
			} elseif ( ! empty( $item['available'] ) ) {
				$attr['action'] = 'activate';
			} elseif ( ! empty( $item['free'] ) ) {
				$attr['action'] = 'download';
			} else {
				$attr['action'] = 'purchase';
			}
		}

		return $attr;
	}

	/**
	 * Adon files exist.
	 *
	 * @param  string $slug Adon slug.
	 * @return boolean
	 */
	public static function adon_files_exist( $slug = '' ) { // phpcs:ignore
		if ( file_exists( SIRSC_ADONS_DIR . $slug . '/class-sirsc-' . $slug . '.php' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Button attribute.
	 *
	 * @param  string $slug Adon slug.
	 * @return array
	 */
	public static function button_processing( $slug ) { // phpcs:ignore
		return [
			'onclick' => 'sirscToggleAdon( \'' . esc_attr( $slug ) . '\');',
		];
	}

	/**
	 * Adon activate/deactivate button.
	 *
	 * @param string $slug Adon slug.
	 */
	public static function maybe_adon_button_activate_deactivate( $slug ) { // phpcs:ignore
		if ( ! empty( self::$adons[ $slug ] ) && self::adon_files_exist( $slug ) ) {
			$item = self::$adons[ $slug ];
			if ( ! empty( $item['available'] ) ) {
				?>
				<div class="sirsc-save-adon-elements">
					<?php
					if ( empty( $item['active'] ) ) {
						?>
						<button type="submit" class="sirsc-save-adon-activate button has-icon button-secondary sirsc-button-icon" name="sirsc-save-adon-activate" value="activate" onclick="sirscToggleAdon( '<?php echo esc_attr( $slug ); ?>' );">
							<span class="dashicons dashicons-marker"></span>
							<span><?php esc_html_e( 'Disabled', 'sirsc' ); ?></span>
						</button>
						<?php
					} else {
						?>
						<button type="submit" class="sirsc-save-adon-deactivate button has-icon button-primary sirsc-button-icon" name="sirsc-save-adon-deactivate" value="deactivate" onclick="sirscToggleAdon( '<?php echo esc_attr( $slug ); ?>' );">
							<span class="dashicons dashicons-yes-alt"></span>
							<span><?php esc_html_e( 'Enabled', 'sirsc' ); ?></span>
						</button>
						<?php
					}
					?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Adon activate/deactivate button.
	 *
	 * @param string $slug Adon slug.
	 */
	public static function maybe_adon_button_buy( $slug ) { // phpcs:ignore
		if ( ! empty( self::$adons[ $slug ] ) ) {
			$item = self::$adons[ $slug ];
			?>
			<a href="<?php echo esc_url( $item['buy_url'] ); ?>" target="_blank" class="button has-icon auto button-primary sirsc-save-adon-purchase"><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Purchase', 'sirsc' ); ?></a>
			<?php
		}
	}

	/**
	 * Adon activate/deactivate button.
	 *
	 * @param string $slug Adon slug.
	 */
	public static function maybe_adon_button_license_key( $slug ) { // phpcs:ignore
		if ( ! empty( self::$adons[ $slug ] ) ) {
			$item    = self::$adons[ $slug ];
			$message = ( ! empty( $item['key_message'] ) ) ? $item['key_message'] : '';
			if ( ! empty( $item['activation_response']->status ) && 'active' === $item['activation_response']->status ) {
				echo wp_kses_post( str_replace( '">', '"><b class="key">' . esc_attr( $item['license_key'] ) . '</b><br>', $message ) );
			} else {
				?>
				<div class="as-row no-margin sirsc-message info sirsc-save-adon-activate-license">
					<input type="text" name="license-key" class="button wide" autocomplete="off" value="<?php echo esc_attr( $item['license_key'] ); ?>" placeholder="<?php esc_attr_e( 'License Key', 'sirsc' ); ?>">
					<?php submit_button( __( 'Activate', 'sirsc' ), 'primary auto last', 'sirsc-save-adon-activate-license-key', false, self::button_processing( $slug ) ); ?></td>
				</div>
				<?php echo wp_kses_post( $message ); ?>
				<?php
			}
		}
	}

	/**
	 * Adon details and purchase buttons.
	 *
	 * @param string $slug The slug.
	 * @param array  $item The item.
	 * @param array  $attr The attributes.
	 */
	public static function adon_details_button( $slug, $item, $attr ) { // phpcs:ignore
		if ( empty( $item['price'] ) && ! empty( $item['free'] ) ) {
			if ( ! empty( $item['buy_url'] ) ) {
				?>
				<a href="<?php echo esc_url( $item['buy_url'] ); ?>" target="_blank" class="button auto last button-secondary sirsc-save-adon-details"><?php esc_html_e( 'Details', 'sirsc' ); ?></a>
				<?php
			}
		} else {
			self::check_adon_valid( $slug );
			$id = self::get_adon_details( $slug, 'activation_id' );
			if ( empty( $id ) ) {
				self::maybe_adon_button_buy( $slug );
			}

			if ( ! empty( $item['buy_url'] ) ) {
				?>
				<a href="<?php echo esc_url( $item['buy_url'] ); ?>" target="_blank" class="button auto last  button-secondary sirsc-save-adon-details"><?php esc_html_e( 'Details', 'sirsc' ); ?></a>
				<?php
			}
		}
	}

	/**
	 * Adon price info.
	 *
	 * @param string $slug The slug.
	 * @param array  $item The item.
	 * @param array  $attr The attributes.
	 */
	public static function adon_price_info( $slug, $item, $attr ) { // phpcs:ignore
		?>
		<b class="price">
			<?php
			if ( empty( $item['price'] ) && ! empty( $item['free'] ) ) {
				esc_html_e( 'Free', 'sirsc' );
			} else {
				echo esc_html( sprintf(
					// Translators: %1$s - adon price.
					__( '&euro; %1$s / year', 'sirsc' ),
					number_format( $item['price'], 2, '.', '' )
				) );
			}
			?>
		</b>
		<?php
	}

	/**
	 * Adon action button.
	 *
	 * @param string $slug The slug.
	 * @param array  $item The item.
	 * @param array  $attr The attributes.
	 */
	public static function adon_on_off_button( $slug, $item, $attr ) { // phpcs:ignore
		if ( empty( $item['price'] ) && ! empty( $item['free'] ) ) {
			self::maybe_adon_button_activate_deactivate( $slug );
		} else {
			self::check_adon_valid( $slug );
			$id = self::get_adon_details( $slug, 'activation_id' );
			if ( ! empty( $id ) ) {
				self::maybe_adon_button_activate_deactivate( $slug );
			}
		}
	}

	/**
	 * Adon action button.
	 *
	 * @param string $slug The slug.
	 * @param array  $item The item.
	 * @param array  $attr The attributes.
	 */
	public static function adon_action_button( $slug, $item, $attr ) { // phpcs:ignore
		if ( ! ( empty( $item['price'] ) && ! empty( $item['free'] ) ) ) {
			self::check_adon_valid( $slug );
			$id = self::get_adon_details( $slug, 'activation_id' );
			if ( ! empty( $id ) ) {
				self::maybe_adon_button_license_key( $slug );
			} else {
				self::maybe_adon_button_license_key( $slug );
			}
		}
	}

	/**
	 * Output adon box.
	 *
	 * @param string $slug Adon slug.
	 * @param array  $item Adon item.
	 */
	public static function output_adon_box( $slug, $item ) { // phpcs:ignore
		if ( empty( $slug ) ) {
			// Fail-fast.
			return;
		}
		$attr = self::adon_check( $slug );
		?>
		<form id="sirsc-box-adon-<?php echo esc_attr( $slug ); ?>" name="sirsc-box-adon-<?php echo esc_attr( $slug ); ?>" class="sirsc-feature as-target sirsc-adon-box adon-<?php echo esc_attr( $slug ); ?> <?php echo esc_attr( $attr['class'] ); ?>" action="" method="post">
			<?php wp_nonce_field( '_sirsc_adon_box_action', '_sirsc_adon_box_nonce' ); ?>
			<input type="hidden" name="sirsc-adon-slug" value="<?php echo esc_attr( $slug ); ?>">

			<div class="box-wrap">
				<img src="<?php echo esc_url( SIRSC_URL . 'assets/images/adon-' . esc_attr( $slug ) . '.png' ); ?>" loading="lazy">
				<?php self::adon_price_info( $slug, $item, $attr ); ?>
			</div>

			<b class="label-row as-title"><?php echo esc_html( $item['name'] ); ?></b>
			<div class="as-row small-gap">
				<?php self::adon_on_off_button( $slug, $item, $attr ); ?>
				<?php self::adon_details_button( $slug, $item, $attr ); ?>
			</div>
			<?php self::adon_action_button( $slug, $item, $attr ); ?>
			<p><?php echo esc_html( $item['description'] ); ?></p>
		</form>
		<?php
	}

	/**
	 * Functionality to manage the image regenerate & select crop settings.
	 */
	public static function features_manager() {
		if ( ! current_user_can( 'manage_options' ) ) {
			// Verify user capabilities in order to deny the access if the user does not have the capabilities.
			wp_die( esc_html__( 'Action not allowed.', 'sirsc' ) );
		}
		$maybe_trans = get_transient( SIRSC_NOTICE . '_adons_notice' );
		if ( ! empty( $maybe_trans ) ) {
			delete_transient( SIRSC_NOTICE . '_adons_notice' );
		}
		?>

		<div class="wrap sirsc-settings-wrap sirsc-feature">
			<?php \SIRSC\Admin\show_plugin_top_info(); ?>
			<?php \SIRSC\Admin\maybe_all_features_tab(); ?>

			<div class="sirsc-tabbed-menu-content">
				<p><?php esc_html_e( 'See the available extensions compatible with the installed plugin version.', 'sirsc' ); ?> <?php esc_html_e( 'You can activate and deactivate these at any time.', 'sirsc' ); ?></p>

				<?php if ( ! empty( self::$adons ) ) : ?>
					<div class="as-row columns-4">
						<?php foreach ( self::$adons as $slug => $item ) : ?>
							<?php $class = ( ! empty( $item['active'] ) ) ? ' bg-secondary' : ' bg-dark'; ?>
							<div class="as-box<?php echo esc_attr( $class ); ?>">
								<?php self::output_adon_box( $slug, $item ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<?php esc_html_e( 'No extension available at the moment.', 'sirsc' ); ?>
				<?php endif; ?>
			</div>

			<?php \SIRSC\admin\show_donate_text(); ?>
		</div>
		<?php
	}
}

// Instantiate the class.
SIRSC_Adons::get_instance();

if ( file_exists( SIRSC_DIR . 'inc/adons-api.php' ) ) {
	// Hookup the SIRSC adons API component.
	require_once SIRSC_DIR . 'inc/adons-api.php';
}
