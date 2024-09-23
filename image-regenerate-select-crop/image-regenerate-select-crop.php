<?php
/**
 * Plugin Name: Image Regenerate & Select Crop
 * Plugin URI:  https://iuliacazan.ro/image-regenerate-select-crop/
 * Description: Regenerate and crop images, details and actions for image sizes registered and image sizes generated, clean up, placeholders, custom rules, register new image sizes, crop medium settings, WP-CLI commands, optimize images.
 * Text Domain: sirsc
 * Domain Path: /langs
 * Version:     8.0.3
 * Author:      Iulia Cazan
 * Author URI:  https://profiles.wordpress.org/iulia-cazan
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ
 * License:     GPL2
 *
 * @package ic-devops
 *
 * Copyright (C) 2014-2024 Iulia Cazan
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define( 'SIRSC_VER', 8.03 );
define( 'SIRSC_FILE', __FILE__ );
define( 'SIRSC_DIR', \plugin_dir_path( __FILE__ ) );
define( 'SIRSC_URL', \plugin_dir_url( __FILE__ ) );
define( 'SIRSC_NOTICE', 'sirsc-plugin-notice' );
define( 'SIRSC_NAME', 'Image Regenerate & Select Crop' );
define( 'SIRSC_SLUG', 'sirsc' );
define( 'SIRSC_SUPPORT', 'https://wordpress.org/support/plugin/image-regenerate-select-crop/' );
define( 'SIRSC_PAGE', 'image-regenerate-select-crop-settings' );
define( 'SIRSC_ADONS_DIR', SIRSC_DIR . 'adons/' );

require_once SIRSC_DIR . 'inc/plugin.php';
require_once SIRSC_DIR . 'inc/notice.php';
require_once SIRSC_DIR . 'inc/debug.php';
require_once SIRSC_DIR . 'inc/action.php';
require_once SIRSC_DIR . 'inc/editor.php';
require_once SIRSC_DIR . 'inc/helper.php';
require_once SIRSC_DIR . 'inc/admin.php';
require_once SIRSC_DIR . 'inc/iterator.php';
require_once SIRSC_DIR . 'inc/integration.php';
require_once SIRSC_DIR . 'inc/calls.php';
require_once SIRSC_DIR . 'inc/adons.php';
require_once SIRSC_DIR . 'inc/wp-cli.php';
require_once SIRSC_DIR . 'inc/cron.php';

/**
 * Class for Image Regenerate & Select Crop.
 */
class SIRSC_Image_Regenerate_Select_Crop {
	const BULK_PROCESS_DELAY = 800;
	const BULK_CLEANUP_ITEMS = 20;
	const DEFAULT_QUALITY    = 90; // This should not be changed, it's the core default value.

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
	 * Plugin user custom rules.
	 *
	 * @var array
	 */
	public static $user_custom_rules;

	/**
	 * Plugin user custom usable rules.
	 *
	 * @var array
	 */
	public static $user_custom_rules_usable;

	/**
	 * Excluded post types.
	 *
	 * @var array
	 */
	public static $exclude_post_type = [];

	/**
	 * Limit the posts.
	 *
	 * @var integer
	 */
	public static $limit9999 = 300;

	/**
	 * Crop positions.
	 *
	 * @var array
	 */
	public static $crop_positions = [];

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	public static $plugin_url = '';

	/**
	 * Plugin native sizes.
	 *
	 * @var array
	 */
	private static $wp_native_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large' ];

	/**
	 * Plugin debug to file.
	 *
	 * @var boolean
	 */
	public static $debug = false;

	/**
	 * Plugin adons list.
	 *
	 * @var array
	 */
	public static $adons;

	/**
	 * Plugin menu items.
	 *
	 * @var array
	 */
	public static $menu_items;

	/**
	 * Upscale width value.
	 *
	 * @var integer
	 */
	public static $upscale_new_w;

	/**
	 * Upscale height value.
	 *
	 * @var array
	 */
	public static $upscale_new_h;

	/**
	 * Core version.
	 *
	 * @var float
	 */
	public static $wp_ver = 5.24;

	/**
	 * Use cron tasks.
	 *
	 * @var bool
	 */
	public static $use_cron = false;

	/**
	 * Is cron running.
	 *
	 * @var bool
	 */
	public static $is_cron = false;

	/**
	 * Computed subsizes info.
	 *
	 * @var array
	 */
	public static $subsizes_info = [];

	/**
	 * Executed action is on demand.
	 *
	 * @var bool
	 */
	public static $action_on_demand = false;

	/**
	 * Get active object instance
	 *
	 * @return object
	 */
	public static function get_instance() { // phpcs:ignore
		if ( ! self::$instance ) {
			self::$instance = new SIRSC_Image_Regenerate_Select_Crop();
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

		self::$settings = get_option( 'sirsc_settings' );
		self::$use_cron = ( ! empty( self::$settings['cron_bulk_execution'] ) ) ? true : false;
		self::$is_cron  = ( defined( 'DOING_CRON' ) && DOING_CRON ) ? true : false;

		self::get_default_user_custom_rules();

		self::$exclude_post_type = [ 'nav_menu_item', 'revision', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'attachment', 'wp_block', 'scheduled-action', 'shop_order', 'shop_order_refund', 'shop_coupon', 'shop_order_placehold', 'wpcf7_contact_form', 'wp_template', 'wp_global_styles', 'wp_template_part', 'wp_navigation', 'e-landing-page', 'elementor_library', 'wp_font_family', 'wp_font_face', 'patterns_ai_data' ];

		self::$wp_ver = (float) get_bloginfo( 'version', 'display' );

		if ( is_admin() ) {
			if ( true === self::$debug && file_exists( SIRSC_DIR . 'sirsc-hooks-tester.php' ) ) {
				include_once SIRSC_DIR . 'sirsc-hooks-tester.php';
			}

			add_action( 'init', [ $called, 'maybe_save_settings' ], 0 );
			add_action( 'wp_ajax_sirsc_autosubmit_save', [ $called, 'maybe_save_settings' ] );

			if ( self::$wp_ver >= 5.0 ) {
				add_filter( 'admin_post_thumbnail_html', '\SIRSC\Admin\append_image_generate_button_tiny', 60, 3 );
			} else {
				add_action( 'image_regenerate_select_crop_button', [ $called, 'image_regenerate_select_crop_button' ] );
				// The init action that is used with older core versions.
				add_action( 'init', [ $called, 'register_image_button' ] );
			}

			add_action( 'wp_ajax_sirsc_show_actions_result', [ $called, 'show_actions_result' ] );

			self::$crop_positions = [
				'lt' => __( 'Left', 'sirsc' ) . '/' . __( 'Top', 'sirsc' ),
				'ct' => __( 'Center', 'sirsc' ) . '/' . __( 'Top', 'sirsc' ),
				'rt' => __( 'Right', 'sirsc' ) . '/' . __( 'Top', 'sirsc' ),
				'lc' => __( 'Left', 'sirsc' ) . '/' . __( 'Center', 'sirsc' ),
				'cc' => __( 'Center', 'sirsc' ) . '/' . __( 'Center', 'sirsc' ),
				'rc' => __( 'Right', 'sirsc' ) . '/' . __( 'Center', 'sirsc' ),
				'lb' => __( 'Left', 'sirsc' ) . '/' . __( 'Bottom', 'sirsc' ),
				'cb' => __( 'Center', 'sirsc' ) . '/' . __( 'Bottom', 'sirsc' ),
				'rb' => __( 'Right', 'sirsc' ) . '/' . __( 'Bottom', 'sirsc' ),
			];

			add_action( 'sirsc_action_after_image_delete', [ $called, 'refresh_extra_info_footer' ] );
			add_filter( 'admin_post_thumbnail_size', [ $called, 'admin_featured_size' ], 60, 3 );
		}

		// This is global, as the image sizes can be also registerd in the themes or other plugins.
		add_filter( 'intermediate_image_sizes_advanced', [ $called, 'filter_ignore_global_image_sizes' ], 0, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ $called, 'wp_generate_attachment_metadata' ], 1, 2 );
		add_action( 'added_post_meta', [ $called, 'process_filtered_attachments' ], 10, 4 );
		add_filter( 'big_image_size_threshold', [ $called, 'big_image_size_threshold_forced' ], 20, 4 );
		add_action( 'delete_attachment', [ $called, 'on_delete_attachment' ] );
		add_action( 'after_setup_theme', [ $called, 'maybe_register_custom_image_sizes' ] );
		add_filter( 'image_size_names_choose', [ $called, 'one_time_custom_image_size_names_choose' ], 60 );
		add_filter( 'wp_php_error_message', [ $called, 'assess_background_errors' ], 60, 2 );
		add_filter( 'wp_unique_filename', [ $called, 'upload_name_prefilter' ], 60, 6 );

		// Attempt to run only one time the action.
		add_action( 'sirsc/image_size_names_choose', [ $called, 'custom_image_size_names_choose' ] );
		add_filter( 'block_editor_settings_all', [ $called, 'custom_image_size_names_choose_blocks' ], 99, 2 );
	}

	/**
	 * Initiate the default structure for the custom rules.
	 *
	 * @return array
	 */
	public static function init_user_custom_rules(): array {
		$default = [];
		for ( $i = 1; $i <= 20; $i++ ) {
			$default[ $i ] = [
				'type'        => '',
				'value'       => '',
				'original'    => '',
				'only'        => [],
				'forfeatured' => [],
				'suppress'    => '',
			];
		}
		return $default;
	}

	/**
	 * Load the user custom rules if available.
	 */
	public static function get_default_user_custom_rules() {
		$default = self::init_user_custom_rules();
		$opt     = get_option( 'sirsc_user_custom_rules' );
		if ( ! empty( $opt ) ) {
			$opt = maybe_unserialize( $opt );
			if ( is_array( $opt ) ) {
				foreach ( $opt as $key => $value ) {
					if ( is_array( $value ) ) {
						$default[ $key ] = array_merge( $default[ $key ], $value );
					}
				}
			}
		}

		self::$user_custom_rules        = $default;
		self::$user_custom_rules_usable = get_option( 'sirsc_user_custom_rules_usable' );
	}

	/**
	 * Maybe register the image sizes.
	 */
	public static function maybe_register_custom_image_sizes() {
		$all = maybe_unserialize( get_option( 'sirsc_use_custom_image_sizes' ) );
		if ( empty( $all['sizes'] ) ) {
			// Fail-fast, no custom image sizes registered.
			return;
		} else {
			foreach ( $all['sizes'] as $i => $value ) {
				if ( ! empty( $value['name'] ) && is_scalar( $value['name'] )
					&& ( ! empty( $value['width'] ) || ! empty( $value['height'] ) ) ) {
					$crop = ( ! empty( $value['crop'] ) ) ? true : false;
					add_image_size( $value['name'], (int) $value['width'], (int) $value['height'], $crop );
				}
			}
		}
	}

	/**
	 * Get all available image sizes with their info.
	 *
	 * @return array
	 */
	public static function assess_all_wp_sizes() { // phpcs:ignore
		$sizes = [];
		if ( function_exists( 'wp_get_registered_image_subsizes' ) ) {
			$sizes = wp_get_registered_image_subsizes();
		} else {
			$names = get_intermediate_image_sizes();
			$added = wp_get_additional_image_sizes();
			foreach ( $names as $name ) {
				if ( isset( $added[ $name ] ) ) {
					$sizes[ $name ] = $added[ $name ];
				} else {
					$sizes[ $name ] = [
						'width'  => (int) get_option( $name . '_size_w' ),
						'height' => (int) get_option( $name . '_size_h' ),
						'crop'   => (int) get_option( $name . '_crop' ),
					];
				}
			}
		}
		return $sizes;
	}

	/**
	 * Exclude globally the image sizes selected in the settings from being generated on upload.
	 *
	 * @param array $sizes    The computed image sizes.
	 * @param array $metadata The image metadata.
	 * @return array
	 */
	public static function filter_ignore_global_image_sizes( $sizes, $metadata = [] ) { // phpcs:ignore
		$sizes = self::assess_all_wp_sizes();
		if ( ! empty( self::$settings['complete_global_ignore'] ) ) {
			foreach ( self::$settings['complete_global_ignore'] as $s ) {
				if ( isset( $sizes[ $s ] ) ) {
					unset( $sizes[ $s ] );
				} else {
					$k = array_keys( $sizes, $s, true );
					if ( ! empty( $k[0] ) ) {
						unset( $sizes[ $k[0] ] );
					}
				}
			}
		}

		$check_size = serialize( $sizes ); // phpcs:ignore
		if ( ! substr_count( $check_size, 'width' ) && ! substr_count( $check_size, 'height' ) ) {
			// Fail-fast here.
			return [];
		}

		$sizes = self::filter_some_more_based_on_metadata( $sizes, $metadata );
		return $sizes;
	}

	/**
	 * Filter the sizes based on the metadata.
	 *
	 * @param array $sizes    Images sizes.
	 * @param array $metadata Uploaded image metadata.
	 * @return array
	 */
	public static function filter_some_more_based_on_metadata( $sizes, $metadata = [] ) { // phpcs:ignore
		if ( empty( $metadata['file'] ) ) {
			// Fail-fast, no upload.
			return $sizes;
		} else {
			if ( ! empty( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $key => $value ) {
					unset( $sizes[ $key ] );
					if ( in_array( $key, $sizes, true ) ) {
						$sizes = array_diff( $sizes, [ $key ] );
					}
				}
			}
			if ( empty( $sizes ) ) {
				return [];
			}
		}

		$args = [
			'meta_key'       => '_wp_attached_file', // phpcs:ignore
			'meta_value'     => $metadata['file'], // phpcs:ignore
			'post_status'    => 'any',
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		];
		$post = new WP_Query( $args );
		if ( ! empty( $post->posts[0] ) ) {
			// The attachment was found.
			self::load_settings_for_post_id( $post->posts[0] );

			if ( ! empty( self::$settings['restrict_sizes_to_these_only'] ) ) {
				foreach ( $sizes as $s => $v ) {
					if ( ! in_array( $s, self::$settings['restrict_sizes_to_these_only'], true ) ) {
						unset( $sizes[ $s ] );
					}
				}
			}
		}
		wp_reset_postdata();

		return $sizes;
	}

	/**
	 * Returns an array of all the native image sizes.
	 *
	 * @return array
	 */
	public static function get_native_image_sizes() { // phpcs:ignore
		return self::$wp_native_sizes;
	}

	/**
	 * Returns an array of all the image sizes registered in the application.
	 *
	 * @param string $size Image size slug.
	 */
	public static function get_all_image_sizes( $size = '' ) { // phpcs:ignore
		global $_wp_additional_image_sizes;
		$sizes = [];

		$get_intermediate_image_sizes = get_intermediate_image_sizes();
		// Create the full array with sizes and crop info.
		foreach ( $get_intermediate_image_sizes as $_size ) {
			if ( in_array( $_size, self::$wp_native_sizes, true ) ) {
				$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
				$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
				$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = [
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				];
			}
		}

		if ( ! empty( $sizes ) ) {
			$all = [];
			foreach ( $sizes as $name => $details ) {
				if ( ! empty( $name ) ) {
					$all[ $name ] = $details;
				}
			}
			$sizes = $all;
		}

		if ( ! empty( $size ) && is_scalar( $size ) ) { // Get only 1 size if found.
			if ( ! empty( $sizes ) && isset( $sizes[ $size ] ) ) {
				return $sizes[ $size ];
			} else {
				return false;
			}
		}

		return $sizes;
	}

	/**
	 * Returns an array of all the image sizes registered in the application filtered by the plugin settings and for a specified image size name.
	 *
	 * @param  string $size   Image size slug.
	 * @param  bool   $strict True if needs to return only the strict available from settings.
	 * @return array|bool
	 */
	public static function get_all_image_sizes_plugin( $size = '', $strict = false ) { // phpcs:ignore
		$sizes = self::get_all_image_sizes( $size );
		$init  = $sizes;
		if ( ! empty( self::$settings['exclude'] ) ) {
			$new_sizes = [];
			foreach ( $sizes as $k => $si ) {
				if ( ! in_array( $k, self::$settings['exclude'], true ) ) {
					$new_sizes[ $k ] = $si;
				}
			}
			$sizes = $new_sizes;
		}
		if ( true === $strict ) {
			if ( ! empty( self::$settings['complete_global_ignore'] ) ) {
				foreach ( self::$settings['complete_global_ignore'] as $ignored ) {
					unset( $sizes[ $ignored ] );
				}
			}
			if ( ! empty( self::$settings['restrict_sizes_to_these_only'] ) ) {
				foreach ( $init as $s => $v ) {
					if ( ! in_array( $s, self::$settings['restrict_sizes_to_these_only'], true ) ) {
						unset( $sizes[ $s ] );
					} else {
						$sizes[ $s ] = $v;
					}
				}
			}
		}

		if ( $size ) { // Get only 1 size if found.
			if ( isset( self::$subsizes_info[ $size ] ) ) {
				// Pick it from the list.
				return self::$subsizes_info[ $size ];
			} elseif ( isset( $sizes['width'] ) && isset( $sizes['height'] ) && isset( $sizes['crop'] ) ) {
				// This must be the requested size.
				return $sizes;
			} else {
				return false;
			}
		}

		return $sizes;
	}

	/**
	 * Returns registered subsizes info.
	 *
	 * @param  string $one    Subsize name.
	 * @param  bool   $usable True if needs to return only the strict available from settings.
	 * @return array
	 */
	public static function get_subsizes_info( $one = '', $usable = false ) { // phpcs:ignore
		if ( empty( self::$subsizes_info ) ) {
			$sizes = self::get_all_image_sizes();
			$init  = $sizes;
			if ( ! empty( self::$settings['exclude'] ) ) {
				$new_sizes = [];
				foreach ( $sizes as $k => $si ) {
					if ( ! in_array( $k, self::$settings['exclude'], true ) ) {
						$new_sizes[ $k ] = $si;
					}
				}
				$sizes = $new_sizes;
			}
			if ( true === $usable ) {
				if ( ! empty( self::$settings['complete_global_ignore'] ) ) {
					foreach ( self::$settings['complete_global_ignore'] as $ignored ) {
						unset( $sizes[ $ignored ] );
					}
				}
				if ( ! empty( self::$settings['restrict_sizes_to_these_only'] ) ) {
					foreach ( $init as $s => $v ) {
						if ( ! in_array( $s, self::$settings['restrict_sizes_to_these_only'], true ) ) {
							unset( $sizes[ $s ] );
						} else {
							$sizes[ $s ] = $v;
						}
					}
				}
			}

			if ( ! empty( $sizes ) ) {
				foreach ( $sizes as $name => $info ) {
					$position = 'cc';
					if ( ! empty( $info['crop'] ) && ! empty( self::$settings['default_crop'][ $name ] ) ) {
						$position = self::$settings['default_crop'][ $name ];
					}

					$quality = 0;
					if ( ! empty( self::$settings['default_quality'][ $name ] ) ) {
						$quality = (int) self::$settings['default_quality'][ $name ];
					}

					$sizes[ $name ]['name']     = $name;
					$sizes[ $name ]['quality']  = $quality;
					$sizes[ $name ]['position'] = $position;
				}
			}

			self::$subsizes_info = $sizes;
		}

		if ( $one ) { // Get only 1 subsize if found.
			if ( isset( self::$subsizes_info[ $one ] ) ) {
				// Pick it from the list.
				return self::$subsizes_info[ $one ];
			} else {
				return [];
			}
		}

		return self::$subsizes_info;
	}

	/**
	 * Assess the background errors.
	 *
	 * @param string $message Error message.
	 * @param array  $error   The error array.
	 * @return string
	 */
	public static function assess_background_errors( $message, $error ) { // phpcs:ignore
		if ( ! empty( $error ) || ! empty( $message ) ) {
			if ( ! empty( $error['message'] ) && substr_count( $error['message'], 'memor' ) ) {

				$monitor = get_option( 'sirsc_monitor_errors', [] );
				if ( empty( $monitor['error'] ) ) {
					$monitor['error'] = [];
				}
				if ( empty( $monitor['schedule'] ) ) {
					$monitor['schedule'] = [];
				}
				if ( ! empty( $monitor['schedule'] ) ) {
					$keys = array_keys( $monitor['schedule'] );
					$id   = $keys[ count( $keys ) - 1 ];

					$monitor['error'][ $id ] = $monitor['schedule'][ $id ] . ' ' . trim( $message . ' ' . $error['message'] );
				}

				update_option( 'sirsc_monitor_errors', $monitor );
			}
		}
		return $message;
	}

	/**
	 * Maybe execute the options update if the nonce is valid, then redirect.
	 */
	public static function maybe_save_settings() {
		$notice = get_option( 'sirsc_settings_updated' );
		if ( ! empty( $notice ) ) {
			add_action( 'admin_notices', '\SIRSC\Admin\\on_settings_update_notice', 10 );
			delete_option( 'sirsc_settings_updated' );
		}

		$nonce = filter_input( INPUT_POST, '_sirsc_settings_nonce', FILTER_DEFAULT );
		if ( empty( $nonce ) ) {
			return;
		}
		if ( ! empty( $nonce ) ) {
			if ( ! wp_verify_nonce( $nonce, '_sirsc_settings_save' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Action not allowed.', 'sirsc' ), esc_html__( 'Security Breach', 'sirsc' ) );
			}

			$data = filter_input( INPUT_POST, 'sirsc', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
			if ( ! empty( $data['trigger'] ) ) {
				if ( 'sirsc-settings-advanced-rules' === $data['trigger'] ) {
					// Custom rules update.
					self::maybe_update_user_custom_rules();
					self::get_default_user_custom_rules();

				} else {
					// Save the general settings.
					self::maybe_save_general_settings();
					self::$settings = get_option( 'sirsc_settings' );
				}
			}

			$is_ajax = filter_input( INPUT_POST, 'sirsc_autosubmit_save', FILTER_DEFAULT );
			if ( ! empty( $is_ajax ) ) {
				wp_die();
				die();
			}
		}
	}

	/**
	 * Get settings list.
	 *
	 * @return array
	 */
	public static function get_settings_list(): array {
		$settings = [
			'exclude'                  => [],
			'unavailable'              => [],
			'force_original_to'        => '',
			'complete_global_ignore'   => [],
			'placeholders'             => [],
			'default_crop'             => [],
			'default_quality'          => [],
			'enable_perfect'           => false,
			'enable_upscale'           => false,
			'regenerate_missing'       => false,
			'disable_woo_thregen'      => false,
			'sync_settings_ewww'       => false,
			'listing_tiny_buttons'     => true,
			'force_size_choose'        => false,
			'leave_settings_behind'    => true,
			'listing_show_summary'     => false,
			'media_grid_buttons'       => true,
			'regenerate_only_featured' => false,
			'bulk_actions_descending'  => true,
			'enable_debug_log'         => false,
			'disable_verbose_log'      => false,
			'cron_bulk_execution'      => false,
			'cron_batch_regenerate'    => 30,
			'cron_batch_cleanup'       => 30,
		];
		return $settings;
	}

	/**
	 * Get settings list.
	 *
	 * @param string $cpt Post type.
	 * @return array
	 */
	public static function prepare_settings_list( $cpt = '' ) : array { // phpcs:ignore
		$list            = self::get_settings_list();
		$global_settings = maybe_unserialize( get_option( 'sirsc_settings' ) );
		$global_settings = wp_parse_args( $global_settings, $list );

		if ( ! empty( $cpt ) ) {
			$common       = self::common_settings();
			$cpt_settings = maybe_unserialize( get_option( 'sirsc_settings_' . $cpt ) );
			$cpt_settings = wp_parse_args( $cpt_settings, $list );
			$cpt_settings = array_merge( $cpt_settings, $common['values'] );
			return $cpt_settings;
		}

		return $global_settings;
	}

	/**
	 * Get common settings.
	 *
	 * @return array
	 */
	public static function common_settings(): array {
		$list = [
			'placeholders',
			'disable_woo_thregen',
			'sync_settings_ewww',
			'listing_tiny_buttons',
			'leave_settings_behind',
			'media_grid_buttons',
			'force_size_choose',
			'listing_show_summary',
			'enable_debug_log',
			'cron_bulk_execution',
			'cron_batch_regenerate',
			'cron_batch_cleanup',
			'bulk_actions_descending',
		];

		$settings = maybe_unserialize( get_option( 'sirsc_settings' ) );
		$common   = [];
		if ( ! empty( $list ) ) {
			foreach ( $list as $item ) {
				if ( isset( $settings[ $item ] ) ) {
					$common[ $item ] = $settings[ $item ];
				}
			}
		}

		return [
			'list'   => $list,
			'values' => $common,
		];
	}

	/**
	 * Execute the update of the general settings.
	 */
	public static function maybe_save_general_settings() {
		$nonce = filter_input( INPUT_POST, '_sirsc_settings_nonce', FILTER_DEFAULT );
		if ( empty( $nonce ) ) {
			return;
		}

		if ( ! empty( $nonce ) ) {
			if ( ! wp_verify_nonce( $nonce, '_sirsc_settings_save' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Action not allowed.', 'sirsc' ), esc_html__( 'Security Breach', 'sirsc' ) );
			}
		}

		$to_update = filter_input( INPUT_POST, '_sirsc_settings_submit', FILTER_DEFAULT );
		if ( ! empty( $to_update ) ) {
			$settings = self::get_settings_list();
			$data     = filter_input( INPUT_POST, 'sirsc', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
			if ( ! empty( $data['trigger'] ) ) {
				if ( 'sirsc-settings-reset' === $data['trigger'] ) {
					$list = get_option( 'sirsc_types_options' );
					if ( ! empty( $list ) && is_array( $list ) ) {
						foreach ( $list as $item ) {
							delete_option( 'sirsc_settings_' . $item );
						}
					}
					delete_option( 'sirsc_settings' );
					delete_option( 'sirsc_user_custom_rules' );
					delete_option( 'sirsc_user_custom_rules_usable' );

					\SIRSC\mark_updated_settings();

					$settings       = self::get_settings_list();
					self::$settings = $settings;
					update_option( 'sirsc_settings', $settings );

					\SIRSC\Cron\maybe_remove_tasks();
					wp_die();
				} elseif ( 'sirsc-settings-cancel-crons' === $data['trigger'] ) {
					\SIRSC\Cron\maybe_remove_tasks();
					wp_die();
				}
			}

			if ( ! empty( $data['placeholders'] ) ) {
				if ( 'force_global' === $data['placeholders'] ) {
					$settings['placeholders']['force_global'] = 1;
				} elseif ( 'only_missing' === $data['placeholders'] ) {
					$settings['placeholders']['only_missing'] = 1;
				}

				if ( $settings['placeholders'] !== self::$settings['placeholders'] ) {
					\SIRSC\Placeholder\regenerate_all_placeholders();
				}
			}

			$post_types = ( ! empty( $data['post_types'] ) ) ? $data['post_types'] : '';

			if ( ! empty( $data['global_ignore'] ) ) {
				$settings['complete_global_ignore'] = array_keys( $data['global_ignore'] );
			}
			if ( ! empty( $data['force_original'] ) ) {
				$settings['force_original_to'] = $data['force_original'];
			}
			if ( ! empty( $data['exclude_size'] ) ) {
				$settings['exclude'] = array_keys( $data['exclude_size'] );
			}
			if ( ! empty( $data['unavailable_size'] ) ) {
				$settings['unavailable'] = array_keys( $data['unavailable_size'] );
			}
			if ( ! empty( $data['default_crop'] ) ) {
				$settings['default_crop'] = $data['default_crop'];
			}
			if ( ! empty( $data['default_quality'] ) ) {
				$settings['default_quality'] = $data['default_quality'];
			}

			$bool = [ 'enable_perfect', 'enable_upscale', 'regenerate_missing', 'regenerate_only_featured', 'bulk_actions_descending', 'disable_woo_thregen', 'sync_settings_ewww', 'listing_tiny_buttons', 'media_grid_buttons', 'listing_show_summary', 'force_size_choose', 'leave_settings_behind', 'enable_debug_log' ];
			foreach ( $bool as $opt ) {
				$settings[ $opt ] = ! empty( $data[ $opt ] );
			}

			if ( ! empty( $data['cron_bulk_execution'] ) ) {
				$settings['cron_bulk_execution']   = true;
				$settings['cron_batch_regenerate'] = ( ! empty( $data['cron_batch_regenerate'] ) )
					? (int) $data['cron_batch_regenerate']
					: 30;
				$settings['cron_batch_cleanup']    = ( ! empty( $data['cron_batch_cleanup'] ) )
					? (int) $data['cron_batch_cleanup']
					: 30;
			} else {
				// Unset the current tasks.
				\SIRSC\Cron\maybe_remove_tasks();
			}

			if ( ! empty( $post_types ) ) { // Specific post type.
				update_option( 'sirsc_settings_' . $post_types, $settings );
			} else { // General settings.
				update_option( 'sirsc_settings', $settings );
			}

			self::$settings = get_option( 'sirsc_settings' );
			\SIRSC\mark_updated_settings();

			\SIRSC\Placeholder\regenerate_all_placeholders();
			$is_ajax = ( ! empty( $data['is-ajax'] ) ) ? true : false;
			if ( ! $is_ajax ) {
				wp_safe_redirect( self::$plugin_url );
				exit;
			} else {
				wp_die();
			}
		}
	}

	/**
	 * Maybe execute the update of custom rules.
	 */
	public static function maybe_update_user_custom_rules() {
		$nonce = filter_input( INPUT_POST, '_sirsc_settings_nonce', FILTER_DEFAULT );
		if ( empty( $nonce ) ) {
			return;
		}

		if ( ! empty( $nonce ) ) {
			if ( ! wp_verify_nonce( $nonce, '_sirsc_settings_save' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Action not allowed.', 'sirsc' ), esc_html__( 'Security Breach', 'sirsc' ) );
			}
		}

		self::get_default_user_custom_rules();
		$data    = filter_input( INPUT_POST, 'sirsc', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$urules  = filter_input( INPUT_POST, '_user_custom_rule', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$ucrules = [];
		foreach ( self::$user_custom_rules as $k => $v ) {
			if ( isset( $urules[ $k ] ) ) {
				$ucrules[ $k ] = ( ! empty( $urules[ $k ] ) ) ? $urules[ $k ] : '';
			}
		}

		foreach ( $ucrules as $k => $v ) {
			if ( ! empty( $v['type'] ) && ! empty( $v['original'] ) ) {
				if ( empty( $v['only'] ) || ! is_array( $v['only'] ) ) {
					$v['only'] = [];
				}
				if ( empty( $v['forfeatured'] ) || ! is_array( $v['forfeatured'] ) ) {
					$v['forfeatured'] = [];
				}
				if ( ! empty( $v['only'] ) ) {
					$ucrules[ $k ]['only'] = $v['only'];
				} elseif ( '**full**' !== $v['original'] ) {
					$ucrules[ $k ]['only'] = [ $v['original'] ];
				}
				if ( '**full**' !== $v['original'] ) {
					$ucrules[ $k ]['only'] = array_merge( $ucrules[ $k ]['only'], [ $v['original'] ] );
				}
				if ( ! empty( $ucrules[ $k ]['only'] ) ) {
					$ucrules[ $k ]['only'] = array_unique( array_diff( $ucrules[ $k ]['only'], [ '**full**' ] ) );
				}
				if ( ! empty( $ucrules[ $k ]['forfeatured'] ) ) {
					$ucrules[ $k ]['forfeatured'] = array_unique( array_diff( $ucrules[ $k ]['forfeatured'], [ '**full**' ] ) );
				}
			}
		}

		$ucrules = self::update_user_custom_rules_priority( $ucrules );
		update_option( 'sirsc_user_custom_rules', $ucrules );

		$usable_crules = [];
		foreach ( $ucrules as $key => $val ) {
			if ( ! empty( $val['type'] ) && ! empty( $val['value'] )
				&& ! empty( $val['original'] ) && ( ! empty( $val['only'] ) || ! empty( $val['forfeatured'] ) )
				&& empty( $val['suppress'] ) ) {
				$usable_crules[] = $val;
			}
		}
		$usable_crules = self::update_user_custom_rules_priority( $usable_crules );
		update_option( 'sirsc_user_custom_rules_usable', $usable_crules );

		self::$user_custom_rules_usable = $usable_crules;
		\SIRSC\mark_updated_settings();

		$is_ajax = ( ! empty( $data['is-ajax'] ) ) ? true : false;
		if ( ! $is_ajax ) {
			wp_safe_redirect( admin_url( 'admin.php?page=image-regenerate-select-crop-rules' ) );
			exit;
		} else {
			wp_die();
		}
	}

	/**
	 * Maybe re-order the custom rules options as priorities.
	 *
	 * @access public
	 * @static
	 * @param array $usable_crules The rules to be prioritized.
	 * @return array
	 */
	public static function update_user_custom_rules_priority( $usable_crules = [] ) { // phpcs:ignore
		if ( ! empty( $usable_crules ) ) {
			// Put the rules in the priority order.
			$ucr = [];
			$c   = 0;

			// Collect the ID rules.
			foreach ( $usable_crules as $k => $rule ) {
				if ( 'ID' === $rule['type'] ) {
					$ucr[ ++$c ] = $rule;
					unset( $usable_crules[ $k ] );
				}
			}
			// Collect the post type rules.
			if ( ! empty( $usable_crules ) ) {
				foreach ( $usable_crules as $k => $rule ) {
					if ( 'post_type' === $rule['type'] ) {
						$ucr[ ++$c ] = $rule;
						unset( $usable_crules[ $k ] );
					}
				}
			}
			// Collect the post format rules.
			if ( ! empty( $usable_crules ) ) {
				foreach ( $usable_crules as $k => $rule ) {
					if ( 'post_format' === $rule['type'] ) {
						$ucr[ ++$c ] = $rule;
						unset( $usable_crules[ $k ] );
					}
				}
			}
			// Collect the post parent rules.
			if ( ! empty( $usable_crules ) ) {
				foreach ( $usable_crules as $k => $rule ) {
					if ( 'post_parent' === $rule['type'] ) {
						$ucr[ ++$c ] = $rule;
						unset( $usable_crules[ $k ] );
					}
				}
			}
			// Collect the tags rules.
			if ( ! empty( $usable_crules ) ) {
				foreach ( $usable_crules as $k => $rule ) {
					if ( 'post_tag' === $rule['type'] ) {
						$ucr[ ++$c ] = $rule;
						unset( $usable_crules[ $k ] );
					}
				}
			}
			// Collect the categories rules.
			if ( ! empty( $usable_crules ) ) {
				foreach ( $usable_crules as $k => $rule ) {
					if ( 'category' === $rule['type'] ) {
						$ucr[ ++$c ] = $rule;
						unset( $usable_crules[ $k ] );
					}
				}
			}
			// Collect the test of the taxonomies rules.
			if ( ! empty( $usable_crules ) ) {
				foreach ( $usable_crules as $k => $rule ) {
					$ucr[ ++$c ] = $rule;
					unset( $usable_crules[ $k ] );
				}
			}

			$usable_crules = $ucr;
		}

		return $usable_crules;
	}

	/**
	 * Custom image size names list in the media screen.
	 *
	 * @param  array $list Initial list of sizes.
	 * @return array
	 */
	public static function one_time_custom_image_size_names_choose( $list ) { // phpcs:ignore
		// Expose all image sizes in the editor.
		return self::custom_image_size_names_choose( $list );
	}

	/**
	 * Custom image size names list in the media screen.
	 *
	 * @param  array $list Initial list of sizes.
	 * @return array
	 */
	public static function custom_image_size_names_choose( $list ) { // phpcs:ignore
		$initial = $list;
		$sizes   = get_intermediate_image_sizes();
		if ( empty( $sizes ) ) {
			return $list;
		}

		$all_ims  = array_filter( $sizes );
		$override = false;

		if ( ! empty( self::$settings['complete_global_ignore'] ) ) {
			$override = true;
			foreach ( self::$settings['complete_global_ignore'] as $rem ) {
				// Remove from check the ignored sizes.
				$all_ims = array_diff( $all_ims, [ $rem ] );
			}
		}
		if ( ! empty( self::$settings['unavailable'] ) ) {
			$override = true;
			foreach ( self::$settings['unavailable'] as $rem ) {
				// Remove from check the unavailable sizes.
				$all_ims = array_diff( $all_ims, [ $rem ] );
			}
		}
		if ( true === $override || ! empty( self::$settings['force_size_choose'] ) ) {
			if ( ! empty( $all_ims ) ) {
				$list = [];
				foreach ( $all_ims as $value ) {
					if ( ! empty( $value ) ) {
						if ( ! empty( $initial[ $value ] ) ) {
							// Re-use the title from the initial array.
							$list[ $value ] = $initial[ $value ];
						} else {
							// Add this to the list of available sizes in the media screen.
							$list[ $value ] = ucwords( str_replace( '-', ' ', str_replace( '_', ' ', $value ) ) );
						}
					}
				}
				if ( ! empty( $initial['full'] ) ) {
					$list['full'] = $initial['full'];
				}
			} else {
				// Fall-back to the minimal.
				$list = [ 'thumbnail' => $initial['thumbnail'] ];
				if ( ! empty( $initial['full'] ) ) {
					$list['full'] = $initial['full'];
				}
			}
		}

		if ( ! empty( self::$user_custom_rules_usable ) ) {
			$must = wp_list_pluck( self::$user_custom_rules_usable, 'only' );
			if ( ! empty( $must ) ) {
				$should = [];
				foreach ( $must as $items ) {
					$should = array_merge( $should, $items );
				}
				$should = array_unique( $should );
				foreach ( $should as $slug ) {
					if ( empty( $list[ $slug ] ) ) {
						$list[ $slug ] = ucwords( str_replace( '_', ' ', $slug ) );
					}
				}
			}
		}

		return $list;
	}

	/**
	 * Append the images sizes that are marked in the custom rules to be used also in the editor.
	 *
	 * @param  array                   $editor_settings      Editor settings.
	 * @param  WP_Block_Editor_Context $block_editor_context Block editor context.
	 * @return array
	 */
	public static function custom_image_size_names_choose_blocks( $editor_settings, $block_editor_context ) { // phpcs:ignore
		if ( ! empty( $block_editor_context->post ) ) {
			self::hook_upload_extra_rules(
				0,
				'',
				$block_editor_context->post->ID ?? 0,
				$block_editor_context->post->post_type ?? ''
			);
			if ( ! empty( self::$settings['restrict_sizes_to_these_only'] ) ) {
				$list = wp_list_pluck( $editor_settings['imageSizes'], 'slug' );
				foreach ( self::$settings['restrict_sizes_to_these_only'] as $slug ) {
					if ( ! in_array( $slug, $list, true ) ) {
						$editor_settings['imageSizes'][] = [
							'slug' => $slug,
							'name' => ucwords( str_replace( '_', ' ', $slug ) ),
						];
					}
				}
			}
		}

		return $editor_settings;
	}

	/**
	 * Load the settings for a post ID (by parent post type).
	 *
	 * @param int $post_id The post ID.
	 */
	public static function load_settings_for_post_id( $post_id = 0 ) { // phpcs:ignore
		$post = get_post( $post_id );
		if ( ! empty( $post->post_parent ) ) {
			$pt = get_post_type( $post->post_parent );
			if ( ! empty( $pt ) && ( 'attachment' === $post->post_type || ! in_array( $post->post_type, self::$exclude_post_type, true ) ) ) {
				self::get_post_type_settings( $pt );
			}
			self::hook_upload_extra_rules( $post_id, $post->post_type, $post->post_parent, $pt );
		} elseif ( ! empty( $post->post_type )
			&& ! in_array( $post->post_type, self::$exclude_post_type, true ) ) {
			self::get_post_type_settings( $post->post_type );
			self::hook_upload_extra_rules( $post_id, $post->post_type, 0, '' );
		}

		if ( empty( self::$settings ) ) {
			// Get the general settings.
			self::get_post_type_settings( '' );
		}
	}

	/**
	 * Attempts to override the settings for a single media file.
	 *
	 * @param int    $id          Attachment post ID.
	 * @param string $type        Attachment post type.
	 * @param int    $parent_id   Attachment post parent ID.
	 * @param string $parent_type Attachment post parent type.
	 */
	public static function hook_upload_extra_rules( $id, $type = '', $parent_id = 0, $parent_type = '' ) {
		if ( ! isset( self::$settings['force_original_to'] ) ) {
			self::$settings['force_original_to'] = '';
		}
		if ( ! isset( self::$settings['complete_global_ignore'] ) ) {
			self::$settings['complete_global_ignore'] = [];
		}
		if ( ! isset( self::$settings['restrict_sizes_to_these_only'] ) ) {
			self::$settings['restrict_sizes_to_these_only'] = [];
		}

		// First, let's apply user custom rules if any are set.
		self::apply_user_custom_rules( $id, $type, $parent_id, $parent_type );

		// Allow to hook from external scripts and create your own upload rules.
		self::$settings = apply_filters( 'sirsc_custom_upload_rule', self::$settings, $id, $type, $parent_id, $parent_type );
	}

	/**
	 * Assess if an attachment is the featured image of a specified post.
	 *
	 * @param  int $attachment_id Attachment ID.
	 * @param  int $post_id       Post ID.
	 * @return bool
	 */
	public static function attachment_is_featured_image( $attachment_id = 0, $post_id = 0 ) { // phpcs:ignore
		$post_featured_image = get_post_thumbnail_id( (int) $post_id );
		return (int) $post_featured_image === (int) $attachment_id;
	}

	/**
	 * Log the rule that matched for processing an attachment.
	 *
	 * @param int   $id          Attachment ID.
	 * @param array $val         Rule details.
	 * @param bool  $is_featured The rule is for a featured image.
	 */
	public static function log_the_matched_rule( $id, $val, $is_featured = false ) {
		$info = $val;
		unset( $info['original'] );
		if ( $is_featured ) {
			$info['sizes'] = $info['forfeatured'];
		} else {
			$info['sizes'] = $info['only'];
		}
		unset( $info['only'] );
		unset( $info['forfeatured'] );

		\SIRSC\Helper\debug( 'MATCHED RULE - ID ' . $id . ': ' . wp_json_encode( $info ), true, true );
	}

	/**
	 * Attempts to override the settings for a single media file.
	 *
	 * @param int    $id          Attachment post ID.
	 * @param string $type        Attachment post type.
	 * @param int    $parent_id   Attachment post parent ID.
	 * @param string $parent_type Attachment post parent type.
	 */
	public static function apply_user_custom_rules( $id, $type, $parent_id = 0, $parent_type = '' ) { // phpcs:ignore
		if ( empty( self::$user_custom_rules_usable ) ) {
			// Fail-fast, no custom rule set.
			return;
		}

		foreach ( self::$user_custom_rules_usable as $key => $val ) {
			$apply        = false;
			$image_par    = wp_get_post_parent_id( $id );
			$for_featured = false;
			$val['value'] = str_replace( ' ', '', $val['value'] );
			$val_array    = [];
			if ( ! empty( $val['value'] ) && in_array( $val['type'], [ 'ID', 'post_parent' ], true ) ) {
				$val_array = array_map( 'intval', explode( ',', $val['value'] ) );
			}

			switch ( $val['type'] ) {
				case 'ID':
					// This is the attachment parent id.
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $image_par ) ) {
						if ( in_array( (int) $image_par, $val_array, true ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} elseif ( in_array( (int) $parent_id, $val_array, true ) ) {
						$apply = true;
					}
					break;
				case 'post_parent':
					// This is the post parent.
					$par = wp_get_post_parent_id( $parent_id );
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $par ) ) {
						if ( in_array( (int) $par, $val_array, true ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} elseif ( in_array( (int) $par, $val_array, true ) ) {
						$apply = true;
					}
					break;
				case 'post_type':
					// This is the attachment parent type.
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $image_par ) ) {
						$image_par_type = ( ! empty( $image_par ) ) ? get_post_type( $image_par ) : '';
						if ( in_array( $image_par_type, explode( ',', $val['value'] ), true ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} else { // phpcs:ignore
						if ( in_array( $parent_type, explode( ',', $val['value'] ), true ) ) {
							$apply = true;
						} elseif ( in_array( $type, explode( ',', $val['value'] ), true ) ) {
							$apply = true;
						}
					}
					break;
				case 'post_format':
					// This is the post format.
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $image_par ) ) {
						$image_par_format = ( ! empty( $image_par ) ) ? get_post_format( $image_par ) : '';
						if ( in_array( $image_par_format, explode( ',', $val['value'] ), true ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} else {
						$format = get_post_format( $parent_id );
						if ( in_array( $format, explode( ',', $val['value'] ), true ) ) {
							$apply = true;
						}
					}
					break;
				case 'post_tag':
					// This is the post tag.
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $image_par ) ) {
						if ( has_tag( explode( ',', $val['value'] ), $image_par ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} elseif ( has_tag( explode( ',', $val['value'] ), $parent_id ) ) {
						$apply = true;
					}
					break;
				case 'category':
					// This is the post category.
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $image_par ) ) {
						if ( has_term( explode( ',', $val['value'] ), 'category', $image_par ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} elseif ( has_term( explode( ',', $val['value'] ), 'category', $parent_id ) ) {
						$apply = true;
					}
					break;
				default:
					// This is a taxonomy.
					if ( ! empty( $val['forfeatured'] ) && self::attachment_is_featured_image( $id, $image_par ) ) {
						if ( has_term( explode( ',', $val['value'] ), $val['type'], $image_par ) ) {
							$apply        = true;
							$for_featured = true;
						}
					} elseif ( has_term( explode( ',', $val['value'] ), $val['type'], $parent_id ) ) {
						$apply = true;
					}
					break;
			}

			if ( true === $apply ) {
				if ( ! empty( $id ) ) {
					self::log_the_matched_rule( $id, $val, $for_featured );
				}

				// The post matched the rule.
				self::$settings = self::custom_rule_to_settings_rules( self::$settings, $val, $for_featured );

				// Fail-fast, no need to iterate more through the rules to speed things up.
				return;
			}
		}

		// The post did not matched any of the cusom rule.
		self::$settings = self::get_post_type_settings( $type );
	}

	/**
	 * Load the post type settings if available.
	 *
	 * @param string $post_type The post type.
	 */
	public static function get_post_type_settings( $post_type ) { // phpcs:ignore
		$pt = '';
		if ( ! empty( $post_type ) && ( 'attachment' === $post_type || ! in_array( $post_type, self::$exclude_post_type, true ) ) ) {
			$pt = '_' . $post_type;
		}

		$list    = self::common_settings();
		$tmp_set = get_option( 'sirsc_settings' . $pt );
		if ( ! empty( $tmp_set ) ) {
			if ( ! empty( $list['values'] ) ) {
				self::$settings = array_merge( $tmp_set, $list['values'] );
			} else {
				self::$settings = $tmp_set;
			}
		}
	}

	/**
	 * Override and returns the settings after apllying a rule.
	 *
	 * @param array $settings    The settings.
	 * @param array $rule        The rule.
	 * @param array $is_featured The rule is for featured image or not.
	 * @return array
	 */
	public static function custom_rule_to_settings_rules( $settings = [], $rule = [], $is_featured = false ) { // phpcs:ignore
		if ( empty( $rule ) || ! is_array( $rule ) ) {
			// Fail-fast, no need to continue.
			return $settings;
		}

		if ( ! empty( $rule['original'] ) ) {
			if ( '**full**' === $rule['original'] ) {
				$settings['force_original_to'] = '';
			} else {
				// Force original.
				$settings['force_original_to'] = $rule['original'];

				// Let's remove it from the global ignore if it was previously set.
				$settings['complete_global_ignore'] = array_diff(
					$settings['complete_global_ignore'],
					[ $rule['original'] ]
				);
			}
		}

		if ( $is_featured ) {
			if ( ! empty( $rule['forfeatured'] ) && is_array( $rule['forfeatured'] ) ) {
				// Make sure we only generate these image sizes.
				$rule['forfeatured'] = array_diff( $rule['forfeatured'], [ '**full**' ] );

				$settings['restrict_sizes_to_these_only'] = $rule['forfeatured'];
				$settings['restrict_sizes_to_these_only'] = array_unique( $settings['restrict_sizes_to_these_only'] );

				if ( ! empty( $settings['default_quality'] ) ) {
					foreach ( $settings['default_quality'] as $s => $q ) {
						if ( ! in_array( $s, $rule['forfeatured'], true ) ) {
							array_push( $settings['complete_global_ignore'], $s );
						}
					}
				}

				$settings['complete_global_ignore'] = array_unique( $settings['complete_global_ignore'] );
			}
		} elseif ( ! empty( $rule['only'] ) && is_array( $rule['only'] ) ) {
			// Make sure we only generate these image sizes.
			$rule['only'] = array_diff( $rule['only'], [ '**full**' ] );

			$settings['restrict_sizes_to_these_only'] = $rule['only'];
			$settings['restrict_sizes_to_these_only'] = array_unique( $settings['restrict_sizes_to_these_only'] );
			if ( ! empty( $settings['default_quality'] ) ) {
				foreach ( $settings['default_quality'] as $s => $q ) {
					if ( ! in_array( $s, $rule['only'], true ) ) {
						array_push( $settings['complete_global_ignore'], $s );
					}
				}
			}

			$settings['complete_global_ignore'] = array_unique( $settings['complete_global_ignore'] );
		}

		return $settings;
	}

	/**
	 * Collect regenerate results.
	 *
	 * @param int    $id        Attachment ID.
	 * @param string $message   An intent or error message.
	 * @param string $type      The collect type (error|schedule).
	 * @param string $initiator The collect initiator.
	 */
	public static function collect_regenerate_results( $id, $message = '', $type = 'schedule', $initiator = 'regenerate' ) { // phpcs:ignore
		$monitor = get_option( 'sirsc_monitor_errors', [] );
		if ( empty( $monitor['error'] ) ) {
			$monitor['error'] = [];
		}
		if ( empty( $monitor['schedule'] ) ) {
			$monitor['schedule'] = [];
		}

		if ( 'error' === $type ) {
			$monitor['error'][ $id ] = $message;
			\SIRSC\Debug\bulk_log_write( $message );
		} elseif ( 'success' === $type || 'info' === $type ) {
			if ( isset( $monitor['schedule'][ $id ] ) ) {
				unset( $monitor['schedule'][ $id ] );
			}
		} else {
			$monitor['schedule'][ $id ] = $message;
		}
		$monitor['initiator'] = $initiator;
		update_option( 'sirsc_monitor_errors', $monitor );
	}

	/**
	 * Output bulk message regenerate original too small.
	 *
	 * @param string $name File name.
	 * @param array  $upls Upload info array.
	 */
	public static function output_bulk_message_regenerate_success( $name, $upls ) { // phpcs:ignore
		$fname = str_replace( trailingslashit( $upls['basedir'] ), '', $name );
		$fname = str_replace( trailingslashit( $upls['baseurl'] ), '', $fname );
		echo '<b class="dashicons dashicons-yes-alt"></b> ' . $fname; // phpcs:ignore
	}

	/**
	 * Assess original vs target.
	 *
	 * @param  array  $image The attachment meta.
	 * @param  array  $sval  The intended image size details.
	 * @param  string $sname The intended image size name.
	 * @return bool
	 */
	public static function assess_original_vs_target( $image = [], $sval = [], $sname = '' ) { // phpcs:ignore
		if ( empty( $image ) || empty( $sval ) ) {
			return false;
		}
		if ( ! empty( $image ) && ! empty( $sval ) ) {
			if ( ! empty( $image['sizes'][ $sname ]['file'] ) || ! self::has_enable_perfect() ) {
				// For the images already created, bypasss the check.
				return true;
			}

			if ( ! empty( $sval['crop'] ) ) {
				// This should be a crop.
				if ( $image['width'] < $sval['width'] || $image['height'] < $sval['height'] ) {
					// The image is too small, return.
					return false;
				}
			} else {
				// This should be a resize.
				if ( ! empty( $sval['width'] ) && $image['width'] < $sval['width'] ) {
					// The image is too small, return.
					return false;
				}
				if ( empty( $sval['width'] ) && ! empty( $sval['height'] ) && $image['height'] < $sval['height'] ) {
					// The image is too small, return.
					return false;
				}
			}

			return true;
		}
	}

	/**
	 * Check if an image size should be generated or not for image meta.
	 *
	 * @param  array  $image    The image metadata.
	 * @param  string $sname    Image size slug.
	 * @param  array  $sval     The image size detail.
	 * @param  string $filename Image filename.
	 * @param  bool   $force    True to force re-crop.
	 * @return bool
	 */
	public static function check_if_execute_size( $image = [], $sname = '', $sval = [], $filename = '', $force = false ) { // phpcs:ignore
		$execute = false;
		if ( ! self::assess_original_vs_target( $image, $sval, $sname ) ) {
			// Fail-fast.
			return false;
		}

		if ( empty( $image['sizes'][ $sname ] ) ) {
			$execute = true;
		} elseif ( empty( $image['sizes'][ $sname ]['file'] ) ) {
			// Check if the file does exist, else generate it.
			$execute = true;
		} else {
			$file = str_replace( basename( $filename ), $image['sizes'][ $sname ]['file'], $filename );

			if ( ! file_exists( $file ) ) {
				$execute = true;
			} else {
				// Check if the file does exist and has the required width and height.
				$w = ( ! empty( $sval['width'] ) ) ? (int) $sval['width'] : 0;
				$h = ( ! empty( $sval['height'] ) ) ? (int) $sval['height'] : 0;
				$c = ( ! empty( $sval['crop'] ) ) ? $sval['crop'] : false;

				$c_image_size = getimagesize( $file );
				$ciw          = (int) $c_image_size[0];
				$cih          = (int) $c_image_size[1];
				if ( ! empty( $c ) ) {
					if ( $w !== $ciw || $h !== $cih ) {
						$execute = true;
					} elseif ( true === $force ) {
						$execute = true;
					}
				} elseif ( ( 0 === $w && $cih <= $h )
					|| ( 0 === $h && $ciw <= $w )
					|| ( 0 !== $w && 0 !== $h && $ciw <= $w && $cih <= $h ) ) {
					$execute = true;
				}
			}
		}
		return $execute;
	}

	/**
	 * Process a single image size for an attachment.
	 *
	 * @param  int    $id                 The Attachment ID.
	 * @param  string $size_name          The image size name.
	 * @param  array  $size_info          Maybe a previously computed image size info.
	 * @param  string $small_crop         Maybe a position for the content crop.
	 * @param  int    $force_quality      Maybe a specified quality loss.
	 * @param  bool   $first_time_replace Maybe it is the first time when the image is processed after upload.
	 * @return mixed
	 */
	public static function process_single_size_from_file( $id, $size_name = '', $size_info = [], $small_crop = '', $force_quality = 0, $first_time_replace = false ) { // phpcs:ignore
		if ( empty( $size_name ) ) {
			return;
		}

		if ( empty( $small_crop ) && ! empty( self::$settings['default_crop'][ $size_name ] ) ) {
			$small_crop = self::$settings['default_crop'][ $size_name ];
		}

		$the_diffs = 0;
		$is_reused = false;
		$from_file = '';
		$metadata  = wp_get_attachment_metadata( $id );
		if ( is_wp_error( $metadata ) || empty( $metadata['file'] ) ) {
			return;
		}

		$mimetype = get_post_mime_type( $id );
		if ( substr_count( $mimetype, 'svg' ) ) {
			// Fail-fast, SVGs have no image sizes.
			return;
		}

		$initial_m = $metadata;
		$filename  = get_attached_file( $id );
		$uploads   = wp_get_upload_dir();
		if ( ! empty( $filename ) ) {
			$file_full = $filename;
			$from_file = $file_full;
		}
		if ( self::$wp_ver >= 5.3 ) {
			if ( ! empty( $metadata['original_image'] ) && ! empty( $metadata['file'] ) ) {
				$file_orig = path_join( trailingslashit( $uploads['basedir'] ) . dirname( $metadata['file'] ), $metadata['original_image'] );
				$from_file = $file_orig;
			}
		}

		if ( true === $first_time_replace ) {
			// Do the switch.
			\SIRSC\Helper\debug( 'REPLACE ORIGINAL', true, true );
			if ( ! empty( self::$settings['force_original_to'] ) && $size_name === self::$settings['force_original_to'] ) {
				$maybe_new_meta = self::swap_full_with_another_size( $id, $from_file, $size_name, $small_crop, $force_quality );
				if ( ! empty( $maybe_new_meta ) ) {
					return $maybe_new_meta;
				}
			}
		}

		\SIRSC\Helper\debug( 'PROCESSING SINGLE ' . $id . '|WP' . self::$wp_ver . '|' . $size_name . '|' . $from_file, true, true );

		if ( ! empty( $from_file ) ) {
			if ( empty( $size_info ) ) {
				self::load_settings_for_post_id( $id );
				$size_info = self::get_all_image_sizes_plugin( $size_name );
			}
			if ( ! empty( $size_info ) ) {
				$assess = self::assess_original_vs_target( $metadata, $size_info, $size_name );
				if ( ! $assess ) {
					if ( ! self::has_enable_perfect() ) {
						// Fail-fast, the original is too small.
						\SIRSC\Helper\debug( 'ERROR TOO SMALL', true, true );
						return 'error-too-small';
					}
				}

				$allow_upscale = self::has_enable_perfect() && self::has_enable_upscale();

				$execute = self::check_if_execute_size( $metadata, $size_name, $size_info, $from_file, true );
				if ( ! empty( $execute ) || $allow_upscale ) {
					$generated = ! empty( $meta['sizes'][ $size_name ] ) ? $meta['sizes'][ $size_name ] : false;

					$saved = self::image_editor( $id, $from_file, $size_name, $size_info, $small_crop, $force_quality );
					if ( ! empty( $saved ) ) {
						if ( ! empty( $saved['path'] ) && file_exists( $saved['path'] ) ) {
							$filedate = filemtime( $saved['path'] );
							if ( $filedate >= time() - 1 ) {
								++$the_diffs;
							}
						}

						// Check for the previous crop file, if that matches the subsize expected dimensions.
						if ( ! empty( $generated ) ) {
							$assess = false;
							if ( ! empty( $size_info['crop'] ) ) {
								// Crop needs exact width AND exact height.
								$assess = $generated['width'] !== $size_info['width'] || $generated['height'] !== $size_info['height'];
							} else {
								// Scale needs exact width OR exact height.
								$assess = ( ! empty( $size_info['width'] ) && $generated['width'] !== $size_info['width'] )
								|| ( ! empty( $size_info['height'] ) && $generated['height'] !== $size_info['height'] );
							}

							if ( $assess ) {
								// Previously a file generated that does not match the expected dimensions.
								$previous = \SIRSC\Helper\get_subsize_path( $id, $size_name, $meta );
								if ( ! empty( $previous ) ) {
									\wp_delete_file( $previous );
								}
							}
						}

						if ( is_wp_error( $metadata ) ) {
							\SIRSC\Helper\debug( 'DO NOT UPDATE METADATA', true, true );
							return;
						}
						$is_reused = ! empty( $saved['reused'] ) ? true : false;
						if ( $is_reused ) {
							\SIRSC\Helper\debug( 'EDITOR REUSED IMAGE', true, true );
						} else {
							\SIRSC\Helper\debug( 'EDITOR PROCESSED IMAGE', true, true );
						}

						if ( empty( $metadata ) ) {
							$metadata = self::attempt_to_create_metadata( $id, $filename );
						}
						if ( empty( $metadata['sizes'] ) ) {
							$metadata['sizes'] = [];
						}
						if ( isset( $saved['path'] ) ) {
							unset( $saved['path'] );
						}
						if ( isset( $saved['reused'] ) ) {
							unset( $saved['reused'] );
						}
						$metadata['sizes'][ $size_name ] = $saved;
						wp_update_attachment_metadata( $id, $metadata );
						$initial_m = $metadata;
					}
				}
			}
		}

		if ( $initial_m !== $metadata ) {
			// If something changed, then save the metadata.
			wp_update_attachment_metadata( $id, $metadata );
		}

		if ( $the_diffs > 0 && ! $is_reused ) {
			\SIRSC\Helper\set_last_update_time( $id );
			do_action( 'sirsc_image_processed', $id, $size_name );
		}
	}

	/**
	 * Swap full image with another image size.
	 *
	 * @param  int    $id            The attachment ID.
	 * @param  string $file          The original file.
	 * @param  string $size_name     The image size name.
	 * @param  string $small_crop    Maybe some crop position.
	 * @param  int    $force_quality Maybe some forced quality.
	 * @return array|bool
	 */
	public static function swap_full_with_another_size( $id, $file, $size_name, $small_crop, $force_quality ) { // phpcs:ignore
		$metadata  = wp_get_attachment_metadata( $id );
		$initial_m = $metadata;
		if ( empty( $metadata ) ) {
			// Fail-fast.
			return false;
		}

		// Make the image.
		self::load_settings_for_post_id( $id );
		$size_info = self::get_all_image_sizes_plugin( $size_name );
		$saved     = self::image_editor( $id, $file, $size_name, $size_info, $small_crop, 0, true );

		// Maybe rename the full size with the original.
		$info     = self::assess_rename_original( $id );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! empty( $saved ) && ! empty( $info ) ) {
			if ( ! empty( $saved['path'] ) ) {
				unset( $saved['path'] );
			}

			\SIRSC\Helper\debug( 'FORCED SIZE EDITOR PROCESSED IMAGE', true, true );
			$saved_filename = $info['path'] . $saved['file'];

			if ( wp_basename( $saved_filename ) !== $info['name'] ) {
				// Rename the new size as the full image.
				@copy( $saved_filename, $info['filename'] ); // phpcs:ignore

				// Remove the image size.
				@unlink( $saved_filename ); // phpcs:ignore

				// Adjust the metadata to match the new set.
				$metadata['width']    = $saved['width'];
				$metadata['height']   = $saved['height'];
				$metadata['filesize'] = ! empty( $saved['filesize'] ) ? $saved['filesize'] : filesize( $info['filename'] );
				$saved['file']        = $info['name'];

				$metadata['sizes'][ $size_name ] = $saved;
			}

			if ( $initial_m !== $metadata ) {
				// If something changed, then save the metadata.
				update_post_meta( $id, '_wp_attachment_metadata', $metadata );
				update_post_meta( $id, '_wp_attached_file', $info['dir'] . $info['name'] );
				clean_attachment_cache( $id );

				if ( ! defined( 'SIRSC_REPLACED_ORIGINAL' ) ) {
					// Notify other scripts that the original file is now this one.
					define( 'SIRSC_REPLACED_ORIGINAL', $info['dir'] . $info['name'] );
				}
			}

			\SIRSC\Helper\debug( 'AFTER EDITOR PROCESSED IMAGE ' . print_r( $metadata, 1 ), true, true ); // phpcs:ignore
			return $metadata;
		}

		return $metadata;
	}

	/**
	 * Decide the estimated size.
	 *
	 * @param  array  $native File native width and height.
	 * @param  string $size   Sub-size name.
	 * @return array
	 */
	public static function decide_estimated_size( $native, $size ) {
		if ( empty( $size ) ) {
			// Fail-fast, no size mentioned.
			return $native;
		}

		$info = self::get_all_image_sizes_plugin( $size );
		if ( empty( $info ) ) {
			// Fail-fast, no size info available or the image was cropped.
			return $native;
		}

		$tmp = wp_constrain_dimensions( $native[0] ?? 0, $native[1] ?? 0, $info['width'] ?? 0, $info['height'] ?? 0 );
		$new = $tmp;

		if ( $info['width'] >= $info['height'] ) {
			// Expects a landscape.
			if ( ! empty( $info['crop'] ) ) {
				if ( $tmp[0] < $info['width'] ) {
					$new = [ $info['width'], $info['height'] ];
				}
			} elseif ( $tmp[0] < $info['width'] ) {
				$new[0] = $info['width'];
				$new[1] = ceil( $info['width'] * $native[1] / $native[0] );
			}
		} else { // phpcs:ignore
			// Expects a portrait.
			if ( ! empty( $info['crop'] ) ) {
				if ( $tmp[1] < $info['height'] ) {
					$new = [ $info['width'], $info['height'] ];
				}

				if ( $new[0] < $native ) {
					$new[1] = $info['height'];
					$new[0] = ceil( $info['height'] * $native[0] / $native[1] );
				}
			} elseif ( $tmp[1] < $info['height'] ) {
				$new[1] = $info['height'];
				$new[0] = ceil( $info['height'] * $native[0] / $native[1] );
			}
		}

		return $new;
	}

	/**
	 * Has enable perfect.
	 *
	 * @return bool
	 */
	public static function has_enable_perfect(): bool {
		return ! empty( self::$settings['enable_perfect'] );
	}

	/**
	 * Has enable upscale.
	 *
	 * @return bool
	 */
	public static function has_enable_upscale(): bool {
		return ! empty( self::$settings['enable_upscale'] );
	}

	/**
	 * Access directly the image editor to generate a specific image.
	 *
	 * @param  string $id            The attachment ID.
	 * @param  string $file          The original file.
	 * @param  string $name          The image size name.
	 * @param  array  $info          The image size info.
	 * @param  string $small_crop    Maybe some crop position.
	 * @param  int    $force_quality Maybe some forced quality.
	 * @param  bool   $is_swap       Maybe use the swap rules.
	 * @return array|bool
	 */
	public static function image_editor( $id, $file, $name = '', $info = [], $small_crop = '', $force_quality = 0, $is_swap = false ) { // phpcs:ignore
		if ( empty( $file ) || ( ! empty( $file ) && ! file_exists( $file ) ) ) {
			// Fail-fast, the original is not found.
			return false;
		}

		$filetype  = wp_check_filetype( $file );
		$mime_type = $filetype['type'];

		if ( ! substr_count( $mime_type, 'image/' ) ) {
			// This is not image, not processing the file.
			return false;
		}

		if ( substr_count( $mime_type, 'svg' ) ) {
			// This is SVG, not processing the file.
			return false;
		}

		if ( ! empty( self::$settings['regenerate_missing'] )
			&& empty( self::$action_on_demand ) ) {
			$meta = wp_get_attachment_metadata( $id );
			if ( ! empty( $meta['sizes'][ $name ]['file'] ) ) {
				$initial  = basename( $meta['file'] );
				$the_file = str_replace( $initial, $meta['sizes'][ $name ]['file'], $file );
				if ( file_exists( $the_file ) ) {
					// The file exists, no need to regenerate it.
					$the_size = getimagesize( $the_file );
					$saved    = [
						'file'   => wp_basename( $the_file ),
						'width'  => (int) $the_size[0],
						'height' => (int) $the_size[1],
						'mime'   => $mime_type,
						'reused' => true,
					];
					return $saved;
				}
			}
		}

		if ( empty( $force_quality ) ) {
			$force_quality = self::editor_set_custom_quality( $name, $mime_type, 0 );
		}

		$image_size = getimagesize( $file );
		if ( $is_swap ) {
			$estimated = self::decide_estimated_size( $image_size, $name );
		} else {
			$estimated = wp_constrain_dimensions( $image_size[0] ?? 0, $image_size[1] ?? 0, $info['width'] ?? 0, $info['height'] ?? 0 );

			$predict = \SIRSC\Editor\predict_subsize( $name, $image_size[0], $image_size[1] );
			if ( ! empty( $predict['upscale'] ) ) {
				$estimated[0] = $predict['upscale']['width'];
				$estimated[1] = $predict['upscale']['height'];
			}
		}

		if ( ! empty( $estimated ) &&
			(int) $estimated[0] === (int) $image_size[0] && (int) $estimated[1] === (int) $image_size[1] ) {
			$meta = wp_get_attachment_metadata( $id );

			// Skip the editor, this is the same as the current file.
			if ( self::$wp_ver < 5.3 ) {
				// For older version, let's check the size in DB.
				if ( ! empty( $meta['sizes'][ $name ]['file'] ) ) {
					$the_file = trailingslashit( dirname( $file ) ) . $meta['sizes'][ $name ]['file'];
					if ( file_exists( $the_file ) ) {
						$the_size = getimagesize( $the_file );
						rename( $the_file, $file ); // phpcs:ignore
						return [
							'file'   => wp_basename( $file ),
							'width'  => (int) $the_size[0],
							'height' => (int) $the_size[1],
							'mime'   => $mime_type,
							'reused' => true,
						];
					}
				}
			} else { // phpcs:ignore
				if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] )
					&& (int) $estimated[0] === (int) $meta['width']
					&& (int) $estimated[1] === (int) $meta['height'] ) {
					// This matches the orginal.
					return [
						'file'   => wp_basename( $file ),
						'width'  => (int) $estimated[0],
						'height' => (int) $estimated[1],
						'mime'   => $mime_type,
						'reused' => true,
					];
				} elseif ( ! empty( $meta['sizes'][ $name ]['file'] ) ) {
					$the_file = trailingslashit( dirname( $file ) ) . $meta['sizes'][ $name ]['file'];
					if ( file_exists( $the_file ) ) {
						$the_size = getimagesize( $the_file );
						return [
							'file'   => wp_basename( $file ),
							'width'  => (int) $the_size[0],
							'height' => (int) $the_size[1],
							'mime'   => $mime_type,
							'reused' => true,
						];
					}
				}
			}

			return false;
		}

		return \SIRSC\Editor\process_subsize_for_file( $id, $name, $image_size[0], $image_size[1], $small_crop, $force_quality );
	}

	/**
	 * Assess unique original.
	 *
	 * @param  int    $id     Attachment ID.
	 * @param  string $folder The path.
	 * @param  string $dir    File relative directory.
	 * @param  string $name   File name.
	 * @return string
	 */
	public static function assess_unique_original( $id, $folder, $dir = '', $name = '' ) { // phpcs:ignore
		if ( ! file_exists( $folder . $name ) ) {
			return $name;
		}

		return $name;
	}

	/**
	 * Assess rename original.
	 *
	 * @param  int $id Attachment ID.
	 * @return array
	 */
	public static function assess_rename_original( $id ) { // phpcs:ignore
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! empty( $metadata ) ) {
			$orig_me = $metadata;
			$uploads = wp_get_upload_dir();
			if ( empty( $metadata['file'] ) ) {
				// Read the filename from the attachmed file, as this was not set in the metadata.
				$filename = get_attached_file( $id );
			} else {
				// Read the filename from the metadata.
				$filename = trailingslashit( $uploads['basedir'] ) . $metadata['file'];
			}

			$ext  = pathinfo( $filename, PATHINFO_EXTENSION );
			$name = pathinfo( $filename, PATHINFO_FILENAME );
			$path = pathinfo( $filename, PATHINFO_DIRNAME );
			if ( file_exists( $filename ) ) {
				$size = getimagesize( $filename );
			} else {
				// This means that the image was probably moved in the previous iteration.
				$size = [ $metadata['width'], $metadata['height'] ];
			}

			$filetype = wp_check_filetype( $filename );
			$info     = [
				'path'   => trailingslashit( $path ),
				'dir'    => trailingslashit( dirname( str_replace( trailingslashit( $uploads['basedir'] ), '', $filename ) ) ),
				'name'   => $name . '.' . $ext,
				'width'  => ( ! empty( $size[0] ) ) ? (int) $size[0] : 0,
				'height' => ( ! empty( $size[1] ) ) ? (int) $size[1] : 0,
				'mime'   => $filetype['type'],
			];

			$initial_unique = '';
			if ( ! empty( $metadata['original_image'] ) && $metadata['original_image'] !== $info['name'] ) {
				$initial_unique = $metadata['original_image'];
				$unique         = wp_unique_filename( $info['path'], $metadata['original_image'] );

				// Remove the initial original file if that is not used by another attachment.
				if ( file_exists( $info['path'] . $metadata['original_image'] )
					&& $metadata['original_image'] !== $unique ) {
					@unlink( $info['path'] . $metadata['original_image'] ); // phpcs:ignore
				}

				// Rename the full size as the initial original file.
				if ( file_exists( $info['path'] . $info['name'] ) ) {
					@rename( $info['path'] . $info['name'], $info['path'] . $unique ); // phpcs:ignore
				}

				// Pass the new name.
				$info['name'] = $unique;

				$metadata['original_image'] = $unique;
			}

			$info['filename']   = $info['path'] . $info['name'];
			$metadata['file']   = $info['dir'] . $info['name'];
			$metadata['width']  = $info['width'];
			$metadata['height'] = $info['height'];

			if ( ! empty( self::$settings['force_original_to'] ) ) {
				$fo_orig = self::$settings['force_original_to'];
				if ( empty( $metadata['sizes'][ $fo_orig ] ) ) {
					$metadata['sizes'][ $fo_orig ] = [
						'file'      => $info['name'],
						'width'     => $info['width'],
						'height'    => $info['height'],
						'mime-type' => $info['mime'],
					];
				}
			}

			// Save this.
			update_post_meta( $id, '_wp_attachment_metadata', $metadata );
			update_post_meta( $id, '_wp_attached_file', $info['dir'] . $info['name'] );

			if ( ! empty( $initial_unique ) && $initial_unique !== $unique ) {
				$new = self::assess_unique_original( $id, $info['path'], $info['dir'], $initial_unique );
				if ( $new === $initial_unique ) {
					\SIRSC\Helper\debug( 'FOUND A POTENTIAL REVERT ' . $new, true, true );
					@rename( $info['path'] . wp_basename( $metadata['file'] ), $info['path'] . $new ); // phpcs:ignore
					$metadata['file']           = $info['dir'] . $new;
					$metadata['original_image'] = $new;

					if ( ! empty( $fo_orig ) && ! empty( $metadata['sizes'][ $fo_orig ] ) ) {
						$metadata['sizes'][ $fo_orig ]['file'] = $new;
					}
					update_post_meta( $id, '_wp_attachment_metadata', $metadata );
					update_post_meta( $id, '_wp_attached_file', $info['dir'] . $new );
					clean_attachment_cache( $id );

					$info['name']     = $new;
					$info['filename'] = $info['path'] . $info['name'];
				}
			}
			clean_attachment_cache( $id );
			return $info;
		}
		return [];
	}

	/**
	 * Assess the quality by mime-type.
	 *
	 * @param  string $sname         Size name.
	 * @param  string $mime          Mime-type.
	 * @param  int    $force_quality Custom quality.
	 * @return int|null
	 */
	public static function editor_set_custom_quality( $sname, $mime, $force_quality ) { // phpcs:ignore
		if ( ! empty( $force_quality ) ) {
			// There is some forced quality.
			$quality = (int) $force_quality;
		} else {
			// Read the quality from the sub-size settings.
			$quality = ! empty( self::$settings['default_quality'][ $sname ] )
				? (int) self::$settings['default_quality'][ $sname ]
				: self::DEFAULT_QUALITY;
		}

		if ( 'image/webp' === $mime ) {
			// The core sets the webp fixed quality.
			$quality = 86;
		}

		if ( empty( $quality ) || $quality <= 0 || $quality > 100 ) {
			// Fallback to the core default quality handle by setting null.
			$quality = null;
		}

		return $quality;
	}

	/**
	 * Identify a crop position by the image size and return the crop array.
	 *
	 * @param string $size_name Image size slug.
	 * @param string $selcrop   Perhaps a selected crop string.
	 * @return array|boolean
	 */
	public static function identify_crop_pos( $size_name = '', $selcrop = '' ) { // phpcs:ignore
		if ( empty( $size_name ) ) {
			// Fail-fast.
			return false;
		}

		$pos = 'cc';
		if ( ! empty( $selcrop ) && is_string( $selcrop ) ) {
			$pos = $selcrop;
		} else {
			$pos = ! empty( self::$settings['default_crop'][ $size_name ] ) ? self::$settings['default_crop'][ $size_name ] : 'cc';
		}

		return \SIRSC\Helper\crop_string_to_array( $pos );
	}

	/**
	 * Match all the files and the images sizes registered.
	 *
	 * @param  int    $id      Attachment ID.
	 * @param  array  $image   Maybe metadata.
	 * @param  object $compute Maybe extra computed info.
	 * @return array|void
	 */
	public static function general_sizes_and_files_match( $id, $image = [], $compute = null ) { // phpcs:ignore
		if ( empty( $id ) ) {
			// Fail-fast.
			return;
		}

		if ( is_array( $id ) ) {
			$id = ( ! empty( $id['id'] ) ) ? (int) $id['id'] : 0;
		}
		if ( empty( $id ) ) {
			// Fail-fast.
			return;
		}

		$upload_dir = wp_upload_dir();
		if ( empty( $compute ) ) {
			$compute = self::compute_image_paths( $id, '', $upload_dir );
		} else {
			$compute = (array) $compute;
		}
		if ( empty( $image ) && ! empty( $compute['metadata'] ) ) {
			$image = $compute['metadata'];
		}
		if ( empty( $image ) ) {
			$image = wp_get_attachment_metadata( $id );
			if ( empty( $image ) ) {
				$filename = get_attached_file( $id );
				$image    = self::attempt_to_create_metadata( $id, $filename );
			}
		}

		$list       = [];
		$registered = get_intermediate_image_sizes();
		$basedir    = trailingslashit( $upload_dir['basedir'] );
		$baseurl    = trailingslashit( $upload_dir['baseurl'] );
		$dir        = '';
		if ( ! empty( $image['file'] ) ) {
			$dir = trailingslashit( dirname( $image['file'] ) );
		} elseif ( ! empty( $compute['source'] ) ) {
			if ( is_scalar( $compute['source'] ) ) {
				$dir = trailingslashit( dirname( $compute['source'] ) );
			} elseif ( is_object( $compute['source'] ) && ! empty( $compute['source']->dir ) ) {
				$dir = trailingslashit( $compute['source']->dir );
			} elseif ( is_array( $compute['source'] ) && ! empty( $compute['source']['dir'] ) ) {
				$dir = trailingslashit( $compute['source']['dir'] );
			}
		}

		$gene_all = self::assess_files_for_attachment_original( $id, $image );
		if ( ! empty( $gene_all['names'] ) ) {
			$list = array_merge(
				$gene_all['names']['original'],
				$gene_all['names']['full'],
				$gene_all['names']['generated']
			);
		}

		// Start to gather data.
		$summary = [];
		if ( ! empty( $gene_all['names']['full'][0] ) ) {
			$file  = $gene_all['names']['full'][0];
			$fsize = ( file_exists( $basedir . $file ) ) ? filesize( $basedir . $file ) : 0;
			$info  = [
				'width'      => $compute['metadata']['width'],
				'height'     => $compute['metadata']['height'],
				'size'       => 'full',
				'registered' => true,
				'fsize'      => $fsize,
				'filesize'   => \SIRSC\Helper\human_filesize( $fsize ),
				'icon'       => 'dashicons-yes-alt is-full',
				'hint'       => __( 'currently registered', 'sirsc' ),
				'is_main'    => 1,
			];

			$summary[ $file ] = $info;
			$list             = array_diff( $list, [ $file ] );
		}

		if ( ! empty( $gene_all['names']['original'][0] ) ) {
			$file  = $gene_all['names']['original'][0];
			$s     = ( file_exists( $basedir . $file ) ) ? getimagesize( $basedir . $file ) : 0;
			$fsize = ( file_exists( $basedir . $file ) ) ? filesize( $basedir . $file ) : 0;
			$info  = [
				'width'      => ( ! empty( $s[0] ) ) ? $s[0] : 0,
				'height'     => ( ! empty( $s[1] ) ) ? $s[1] : 0,
				'size'       => ( $file === $gene_all['names']['full'][0] ) ? 'full,original' : 'original',
				'registered' => true,
				'fsize'      => $fsize,
				'filesize'   => \SIRSC\Helper\human_filesize( $fsize ),
				'icon'       => ( $file === $gene_all['names']['full'][0] ) ? 'dashicons-yes-alt is-full is-original' : 'dashicons-yes-alt is-original',
				'',
				'hint'       => __( 'currently registered', 'sirsc' ),
				'is_main'    => 2,
			];

			$summary[ $file ] = $info;
			$list             = array_diff( $list, [ $file ] );
		}

		if ( ! empty( $compute['metadata']['sizes'] ) ) {
			foreach ( $compute['metadata']['sizes'] as $k => $v ) {
				$file  = $dir . $v['file'];
				$fsize = ( file_exists( $basedir . $file ) ) ? filesize( $basedir . $file ) : 0;
				$info  = [
					'width'      => ( ! empty( $v['width'] ) ) ? $v['width'] : 0,
					'height'     => ( ! empty( $v['height'] ) ) ? $v['height'] : 0,
					'size'       => $k,
					'registered' => ( in_array( $k, $registered, true ) ),
					'fsize'      => $fsize,
					'filesize'   => \SIRSC\Helper\human_filesize( $fsize ),
					'icon'       => ( in_array( $k, $registered, true ) ) ? 'dashicons-yes-alt' : 'dashicons-marker',
					'hint'       => ( in_array( $k, $registered, true ) ) ? __( 'currently registered', 'sirsc' ) : __( 'not registered anymore', 'sirsc' ),
					'is_main'    => 0,
				];
				if ( ! isset( $summary[ $file ] ) ) {
					$summary[ $file ] = $info;
				} else {
					$summary[ $file ]['size'] .= ',' . $k;
				}
				$list = array_diff( $list, [ $file ] );
			}
		}

		if ( ! empty( $list ) ) {
			foreach ( $list as $k ) {
				$fsize = ( file_exists( $basedir . $k ) ) ? filesize( $basedir . $k ) : 0;
				$s     = ( file_exists( $basedir . $k ) ) ? getimagesize( $basedir . $k ) : [];

				$summary[ $k ] = [
					'width'      => ( ! empty( $s[0] ) ) ? $s[0] : 0,
					'height'     => ( ! empty( $s[1] ) ) ? $s[1] : 0,
					'size'       => __( 'unknown', 'sirsc' ),
					'registered' => false,
					'fsize'      => $fsize,
					'filesize'   => \SIRSC\Helper\human_filesize( $fsize ),
					'icon'       => 'dashicons-marker',
					'hint'       => __( 'never registered', 'sirsc' ),
					'is_main'    => 0,
				];
			}
		}
		if ( empty( $summary ) ) {
			return;
		}

		$sortable = wp_list_pluck( $summary, 'fsize' );
		arsort( $sortable );

		$sorted = [];
		foreach ( $sortable as $k => $v ) {
			$sorted[ $k ] = $summary[ $k ];
		}
		$summary = $sorted;

		// This attempts to matche the sizes and updates the summary.
		$summary = self::maybe_match_unknown_files_to_meta( $id, $summary );
		return $summary;
	}

	/**
	 * Size is registered.
	 *
	 * @param  string $size Size name.
	 * @return bool
	 */
	public static function size_is_registered( $size ) { // phpcs:ignore
		$registered = get_intermediate_image_sizes();

		if ( 'full' === $size || 'original' === $size ) {
			return true;
		}

		return ( in_array( $size, $registered, true ) ) ? true : false;
	}

	/**
	 * Attempt to match the unknown files and update the attachment metadata.
	 *
	 * @param  int   $id      Attachment ID.
	 * @param  array $summary Identified generated files.
	 * @return array
	 */
	public static function maybe_match_unknown_files_to_meta( $id, $summary ) { // phpcs:ignore
		$assess = [];
		if ( ! empty( $summary ) ) {
			$image_meta   = wp_get_attachment_metadata( $id );
			$initial_meta = $image_meta;
			$sizes_info   = self::get_all_image_sizes_plugin();
			if ( ! empty( $image_meta['sizes'] ) ) {
				$direct = wp_list_pluck( $image_meta['sizes'], 'file' );
				if ( ! empty( $direct ) ) {
					$dir = trailingslashit( dirname( $image_meta['file'] ) );
					foreach ( $direct as $key => $value ) {
						$file = $dir . $value;
						if ( ! empty( $summary[ $file ] ) ) {
							if ( substr_count( $summary[ $file ]['size'], 'unknown' ) ) {
								$summary[ $file ]['size'] = $key;
							} elseif ( ! substr_count( $summary[ $file ]['size'], $key ) ) {
								$summary[ $file ]['size'] .= ',' . $key;
							}
						}
					}
				}
			}

			foreach ( $summary as $file => $info ) {
				if ( 'unknown' === $info['size']
					&& ! empty( $info['width'] ) && ! empty( $info['height'] ) ) {
					$filetype = wp_check_filetype( $file );
					foreach ( $sizes_info as $name => $details ) {
						if ( (int) $details['width'] === (int) $info['width']
							&& (int) $details['height'] === (int) $info['height'] ) {
							if ( substr_count( $file, '-' . $info['width'] . 'x' . $info['height'] . '.' ) ) {
								// This is a perfect match.
								$image_meta['sizes'][ $name ] = [
									'file'      => wp_basename( $file ),
									'width'     => (int) $info['width'],
									'height'    => (int) $info['height'],
									'mime-type' => $filetype['type'],
								];

								$summary[ $file ]['size'] .= ',' . $name;
							}
						} elseif ( empty( $details['crop'] ) ) {
							// This can be a scale type.
							if ( (int) $details['width'] === $info['width']
								&& empty( $details['height'] ) ) {
								$image_meta['sizes'][ $name ] = [
									'file'      => wp_basename( $file ),
									'width'     => (int) $details['width'],
									'height'    => (int) $info['height'],
									'mime-type' => $filetype['type'],
								];

								$summary[ $file ]['size'] .= ',' . $name;
							} elseif ( (int) $details['height'] === (int) $info['height']
								&& empty( $details['width'] ) ) {
								$image_meta['sizes'][ $name ] = [
									'file'      => wp_basename( $file ),
									'width'     => (int) $info['width'],
									'height'    => (int) $details['height'],
									'mime-type' => $filetype['type'],
								];

								$summary[ $file ]['size'] .= ',' . $name;
							}
						}
					}

					if ( substr_count( $summary[ $file ]['size'], ',' ) ) {
						$summary[ $file ]['size'] = str_replace( 'unknown,', '', $summary[ $file ]['size'] );
						$summary[ $file ]['icon'] = 'dashicons-yes-alt';
						$summary[ $file ]['hint'] = __( 'currently registered', 'sirsc' );
					}
				}
			}

			if ( ! empty( $summary ) ) {
				foreach ( $summary as $key => $value ) {
					$summary[ $key ]['match'] = explode( ',', $summary[ $key ]['size'] );
				}
			}

			if ( $image_meta !== $initial_meta ) {
				// Override the meta with matched images, to fix missing metadata.
				wp_update_attachment_metadata( $id, $image_meta );
			}
		}

		return $summary;
	}

	/**
	 * Assess the files generated for an attachment.
	 *
	 * @param  int   $id    Attachment ID.
	 * @param  array $image Maybe the known attachment metadata.
	 * @return array
	 */
	public static function assess_files_for_attachment_original( $id, $image = [] ) { // phpcs:ignore
		if ( empty( $image ) ) {
			$image = wp_get_attachment_metadata( $id );
		}

		$dir        = '';
		$upload_dir = wp_upload_dir();
		$basedir    = trailingslashit( $upload_dir['basedir'] );
		$list       = [];
		$full       = [];
		$gene       = [];

		// Assess the original files.
		if ( ! empty( $image['file'] ) ) {
			$full[] = $basedir . $image['file'];
			$dir    = trailingslashit( dirname( $image['file'] ) );
		}
		$full = array_unique( $full );
		if ( ! empty( $image['original_image'] ) ) {
			$list[] = $basedir . $dir . $image['original_image'];
		}
		$list = array_unique( $list );
		if ( ! empty( $image['sizes'] ) ) {
			foreach ( $image['sizes'] as $key => $value ) {
				if ( ! empty( $value['file'] ) ) {
					$gene[] = $basedir . $dir . $value['file'];
				}
			}
		}

		// Assess the generated files.
		if ( ! empty( $list ) ) {
			foreach ( $list as $file ) {
				$ext    = pathinfo( $file, PATHINFO_EXTENSION );
				$name   = pathinfo( $file, PATHINFO_FILENAME );
				$path   = pathinfo( $file, PATHINFO_DIRNAME );
				$gene[] = $file;
				$extra  = glob( $path . '/' . $name . '-*x*.' . $ext );
				if ( ! empty( $extra ) ) {
					foreach ( $extra as $kglob => $tmp ) {
						$test = explode( $name, $tmp );
						$test = ( ! empty( $test[1] ) ) ? $test[1] : '';
						$pos  = strrpos( $test, '-' );
						if ( ! empty( $pos ) ) {
							$rest = substr( $test, 0, $pos );
							if ( ! empty( $rest ) ) {
								// Unregister images from other filenames re-iterations.
								unset( $extra[ $kglob ] );
							}
						}
					}
				}
				if ( ! empty( $extra ) ) {
					$gene = array_merge( $gene, $extra );
				}
			}
		}
		$gene = array_unique( $gene );
		$gene = array_diff( $gene, $full );
		$gene = array_diff( $gene, $list );

		// Process lists to see only names.
		$list_names = [];
		if ( ! empty( $list ) ) {
			foreach ( $list as $value ) {
				$list_names[] = str_replace( $basedir, '', $value );
			}
		}
		$full_names = [];
		if ( ! empty( $full ) ) {
			foreach ( $full as $value ) {
				$full_names[] = str_replace( $basedir, '', $value );
			}
		}
		$gene_names = [];
		if ( ! empty( $gene ) ) {
			foreach ( $gene as $value ) {
				$gene_names[] = str_replace( $basedir, '', $value );
			}
		}

		$result = [
			'names' => [
				'original'  => $list_names,
				'full'      => $full_names,
				'generated' => $gene_names,
			],
			'paths' => [
				'original'  => $list,
				'full'      => $full,
				'generated' => $gene,
			],
		];

		return $result;
	}

	/**
	 * Attempt to delete all generate files on delete attachment.
	 *
	 * @param int $post_id Attachment ID.
	 */
	public static function on_delete_attachment( $post_id ) { // phpcs:ignore
		$gene_all = self::assess_files_for_attachment_original( $post_id );
		if ( ! empty( $gene_all['paths']['generated'] ) ) {
			foreach ( $gene_all['paths']['generated'] as $value ) {
				@unlink( $value ); // phpcs:ignore
			}
		}
	}

	/**
	 * Force the custom threshold for WP >= 5.3, when there is a forced original size in the settings.
	 *
	 * @param  int    $initial_value Maximum width.
	 * @param  int    $imagesize     Computed attributes for the file.
	 * @param  string $file          The file.
	 * @param  int    $attachment_id The attachment ID.
	 * @return int|bool
	 */
	public static function big_image_size_threshold_forced( $initial_value, $imagesize, $file, $attachment_id ) { // phpcs:ignore
		if ( ! empty( self::$settings['force_original_to'] ) ) {
			self::load_settings_for_post_id( $attachment_id );
			$size = self::get_all_image_sizes( self::$settings['force_original_to'] );
			if ( empty( $size ) ) {
				return $initial_value;
			}

			if ( empty( $size['width'] ) ) {
				$size['width'] = 0;
			}
			if ( empty( $size['height'] ) ) {
				$size['height'] = 0;
			}

			$estimated = wp_constrain_dimensions( (int) $imagesize[0], (int) $imagesize[1], $size['width'], $size['height'] );
			\SIRSC\Helper\debug( 'Estimated before applying threshold ' . print_r( $estimated, 1 ), true, true ); // phpcs:ignore

			if ( $size['width'] < $size['height'] ) {
				// Portrait.
				$tmp          = self::decide_estimated_size( $imagesize, self::$settings['force_original_to'] );
				$estimated[0] = $tmp[0] ?? 0;
			}

			$threshold = max( $size['width'], $estimated[0] ?? 0 );
			if ( $threshold < $initial_value ) {
				\SIRSC\Helper\debug( 'Force the image threshold to ' . $threshold, true, true );
				return (int) $threshold;
			}
		}
		return $initial_value;
	}

	/**
	 * Get SVG size from the viewport.
	 *
	 * @param  string|false $svg The file path to where the SVG file should be, false otherwise.
	 * @return array|bool
	 */
	public static function svg_dimensions( $svg ) {
		if ( empty( $svg ) ) {
			return false;
		}

		$svg  = @simplexml_load_file( $svg );
		$view = '';
		if ( ! empty( $svg ) ) {
			$attributes = $svg->attributes();
			if ( ! empty( $attributes->viewBox ) ) {
				if ( is_array( $attributes->viewBox ) ) {
					$view = reset( $attributes->viewBox );
				} else {
					$view = $attributes->viewBox;
				}
			}
		}

		if ( empty( $view ) ) {
			$svg = file_get_contents( $svg );
			if ( ! empty( $svg ) && preg_match('/viewBox="([\d.\s-]+)"/', $svg, $matches ) ) {
				$view = $matches[1];
			}
		}

		if ( ! empty( $view ) ) {
			list( $min_x, $min_y, $width, $height ) = explode( ' ', $view );

			return [
				'width'       => (int) $width,
				'height'      => (int) $height,
				'orientation' => ( $width > $height ) ? 'landscape' : 'portrait'
			];
		}

		return false;
	}

	/**
	 * Maybe filter initial metadata.
	 *
	 * @param  array $metadata      Computed metadata.
	 * @param  int   $attachment_id The attachment that is processing.
	 * @return array
	 */
	public static function wp_generate_attachment_metadata( $metadata, $attachment_id ) { // phpcs:ignore
		if ( empty( $attachment_id ) ) {
			// Fail-fast.
			return $metadata;
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			// Fail-fast.
			return $metadata;
		}

		// Attempt to fix the SVG dimension when SSL operation failed due to routines:tls_process_server_certificate:certificate verify.
		$mime = get_post_mime_type( $attachment_id );
		if ( ! empty( $mime ) && substr_count( $mime, 'svg' ) ) {
			$extra = self::svg_dimensions( wp_get_original_image_path( $attachment_id ) );
			if ( ! empty( $extra ) ) {
				$metadata = array_merge( $metadata, $extra );

				// Fail-fast.
				return $metadata;
			}
		}

		if ( self::$wp_ver >= 5.3 ) {
			// Metadata parameter is empty, let's fetch it from the database if existing.
			$metadata = wp_get_attachment_metadata( $attachment_id );
		} else {
			// Initially preserve it.
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );
		}

		$sirsc_meta = get_post_meta( $attachment_id, '_sirsc_attachment_metadata', true );
		if ( ! empty( $sirsc_meta ) ) {
			$metadata = $sirsc_meta;
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $sirsc_meta );
			delete_post_meta( $attachment_id, '_sirsc_attachment_metadata' );
		}

		\SIRSC\Helper\debug( 'PREPARE AND RELEASE THE METADATA FOR ' . $attachment_id, true, true );
		$filter_out = self::cleanup_before_releasing_the_metadata_on_upload( $attachment_id );

		if ( class_exists( 'SIRSC_Adons' ) && ! class_exists( 'SIRSC_Adons_Images_SEO' ) ) {
			\SIRSC_Adons::detect_adons();
		}
		if ( class_exists( 'SIRSC_Adons_Images_SEO' ) ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				update_post_meta( $attachment_id, '_wp_attachment_metadata', $filter_out );
			}
			$filter_out = \SIRSC_Adons_Images_SEO::process_rename_after_file_uploaded( $filter_out, $attachment_id );
		}

		$filter_out = self::maybe_enforce_quality_on_upload( $filter_out, $attachment_id );
		$filter_out = apply_filters( 'sirsc_computed_metadata_after_upload', $filter_out, $attachment_id );

		do_action( 'sirsc_attachment_images_ready', $filter_out, $attachment_id );
		return $filter_out;
	}

	/**
	 * Maybe enforce quality on upload.
	 *
	 * @param  array $metadata Filtred metadata.
	 * @param  int   $id       Attachment ID.
	 * @return array
	 */
	public static function maybe_enforce_quality_on_upload( $metadata, $id ) {
		if ( empty( $metadata ) || empty( $id ) ) {
			// Fail-fast, return intial.
			return $metadata;
		}

		$changed = false;
		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $name => $data ) {
				if ( empty( $data['mime-type'] ) ) {
					continue;
				}

				// Read the quality from the sub-size settings.
				$expected = ! empty( self::$settings['default_quality'][ $name ] )
					? (int) self::$settings['default_quality'][ $name ]
					: self::DEFAULT_QUALITY;

				if ( self::DEFAULT_QUALITY !== $expected && 'image/webp' !== $data['mime-type'] ) {
					$changed = true;
					self::process_single_size_from_file( $id, $name, [], '', $expected );
				}
			}
		}

		if ( $changed ) {
			$metadata = wp_get_attachment_metadata( $id );
		}

		return $metadata;
	}

	/**
	 * Assess and cleanup temporary artefact images.
	 *
	 * @param int $id Attachemnt ID.
	 */
	public static function assess_tmp_artefacts( int $id ) {
		$all_sizes  = self::get_all_image_sizes_plugin();
		$upload_dir = \wp_upload_dir();
		$compute    = \SIRSC\Helper\compute_image_details( $id, '', $upload_dir, $all_sizes );

		if ( ! empty( $compute->info->metadata ) ) {
			$image = $compute->info->metadata;
		}
		if ( empty( $image ) ) {
			$image = \wp_get_attachment_metadata( $id );
			if ( empty( $image ) ) {
				$filename = \get_attached_file( $id );
				$image    = self::attempt_to_create_metadata( $id, $filename );
			}
		}

		$summary = self::general_sizes_and_files_match( $id, $image, $compute );
		if ( ! empty( $summary ) ) {
			foreach ( $summary as $file => $info ) {
				if ( empty( $info['registered'] ) ) {
					// Cleanup temp files.
					if ( file_exists( $upload_dir['basedir'] . '/' . $file ) ) {
						@unlink( $upload_dir['basedir'] . '/' . $file ); // phpcs:ignore
					}
				}
			}
		}
	}

	/**
	 * Cleanup before releasing the atachment metadata on upload.
	 *
	 * @param  int $attachment_id The attachment ID.
	 * @return array
	 */
	public static function cleanup_before_releasing_the_metadata_on_upload( $attachment_id ) { // phpcs:ignore
		self::assess_tmp_artefacts( (int) $attachment_id );
		$filter_out = wp_get_attachment_metadata( $attachment_id );
		if ( defined( 'SIRSC_BRUTE_RENAME' ) && SIRSC_BRUTE_RENAME !== $filter_out['file'] ) {
			$filter_out['file'] = SIRSC_BRUTE_RENAME;
			if ( ! empty( self::$settings['force_original_to'] )
				&& empty( $filter_out['sizes'][ self::$settings['force_original_to'] ] ) ) {
				$uploads  = wp_get_upload_dir();
				$filetype = wp_check_filetype( trailingslashit( $uploads['basedir'] ) . $filter_out['file'] );

				$filter_out['sizes'][ self::$settings['force_original_to'] ] = [
					'file'      => wp_basename( $filter_out['file'] ),
					'width'     => $filter_out['width'],
					'height'    => $filter_out['height'],
					'mime-type' => $filetype['type'],
				];
			}
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $filter_out );
		} else {
			$uploads = wp_get_upload_dir();

			if ( false === apply_filters( 'sirsc_keep_scaled', false ) ) {
				if ( ! empty( $filter_out['file'] )
					&& ( substr_count( $filter_out['file'], '-scaled.' ) || substr_count( $filter_out['file'], '-scaled-' ) ) ) {
					$initial                      = $filter_out['file'];
					$filter_out['file']           = str_replace( '-scaled.', '.', $initial );
					$filter_out['file']           = str_replace( '-scaled-', '-', $filter_out['file'] );
					$filter_out['original_image'] = wp_basename( str_replace( '-scaled.', '.', $initial ) );
					$filter_out['original_image'] = str_replace( '-scaled-', '-', $filter_out['original_image'] );

					update_attached_file( $attachment_id, $filter_out['file'] );
				} else {
					$initial = ! empty( $filter_out['file'] )
						? str_replace( '.', '-scaled.', $filter_out['file'] ) : '';
				}
			}

			if ( ! empty( self::$settings['force_original_to'] ) ) {
				$filetype = wp_check_filetype( trailingslashit( $uploads['basedir'] ) . $filter_out['file'] );

				$filter_out['sizes'][ self::$settings['force_original_to'] ] = [
					'file'      => wp_basename( $filter_out['file'] ),
					'width'     => $filter_out['width'],
					'height'    => $filter_out['height'],
					'mime-type' => $filetype['type'],
				];
			}

			if ( ! empty( $filter_out['sizes'] ) ) {
				foreach ( $filter_out['sizes'] as $size => $size_info ) {
					if ( (int) $size_info['width'] === (int) $filter_out['width']
						&& (int) $size_info['height'] === (int) $filter_out['height']
					) {
						$maybe = str_replace( '.', '-' . $size_info['width'] . 'x' . $size_info['height'] . '.', $filter_out['file'] );
						$maybe = trailingslashit( $uploads['basedir'] ) . $maybe;
						if ( file_exists( $maybe ) ) {
							@unlink( $maybe ); // phpcs:ignore
						}
					}
				}
			}

			if ( ! empty( $initial ) && file_exists( trailingslashit( $uploads['basedir'] ) . $initial ) ) {
				@unlink( trailingslashit( $uploads['basedir'] ) . $initial ); // phpcs:ignore
			}

			update_post_meta( $attachment_id, '_wp_attachment_metadata', $filter_out );
		}

		return $filter_out;
	}

	/**
	 * Execute the removal of an attachment image size and file.
	 *
	 * @param  int    $id    The attachment id.
	 * @param  string $size  The specified image size.
	 * @param  string $fname A specified filename.
	 * @param  array  $image Maybe the previously computed attachment metadata.
	 * @return bool|string
	 */
	public static function execute_specified_attachment_file_delete( $id = 0, $size = '', $fname = '', &$image = [] ) { // phpcs:ignore
		if ( empty( $image ) ) {
			$image = wp_get_attachment_metadata( $id );
		}

		if ( ! empty( $fname ) ) {
			\SIRSC\Action\cleanup_attachment_one_size_file( $id, $size, $fname );
		} else {
			\SIRSC\Action\cleanup_attachment_one_size( $id, $size );
		}
		$image = wp_get_attachment_metadata( $id );

		return true;
	}

	/**
	 * Check if the attached image is required to be replaced with the "Force Original" from the settings.
	 *
	 * @param  int    $meta_id    Post meta id.
	 * @param  int    $post_id    Post ID.
	 * @param  string $meta_key   Post meta key.
	 * @param  array  $meta_value Post meta value.
	 */
	public static function process_filtered_attachments( $meta_id = '', $post_id = '', $meta_key = '', $meta_value = '' ) { // phpcs:ignore
		if ( empty( $post_id ) || empty( $meta_value ) || '_wp_attachment_metadata' !== $meta_key ) {
			// Fail-fast.
			return;
		}

		if ( ! wp_attachment_is_image( $post_id ) ) {
			// Fail-fast.
			return;
		}

		\SIRSC\Helper\notify_doing_sirsc();
		self::load_settings_for_post_id( $post_id );

		\SIRSC\Helper\debug( 'FIRST METADATA SAVED ' . print_r( $meta_value, 1 ), true, true ); // phpcs:ignore

		if ( ! empty( self::$settings['force_original_to'] ) ) {
			/**
			Legacy code.
			if ( self::$wp_ver >= 5.3 ) {
				// Maybe rename the full size with the original.
				$info = self::assess_rename_original( $post_id );
			} else {
				// Maybe swap the forced size with the full.
				$file    = get_attached_file( $post_id );
				$fo_orig = self::$settings['force_original_to'];
				$size    = self::get_all_image_sizes( $fo_orig );
				self::swap_full_with_another_size( $post_id, $file, $fo_orig, $size['crop'], 0 );
			}
			*/

			// Since wp 6.3 this should be run also.
			$file    = get_attached_file( $post_id );
			$fo_orig = self::$settings['force_original_to'];
			$size    = self::get_all_image_sizes( $fo_orig );
			self::swap_full_with_another_size( $post_id, $file, $fo_orig, $size['crop'], 0 );
		}

		if ( ! empty( $info ) && $info['dir'] . $info['name'] !== $meta_value['file'] ) {
			// Brute update and notify other scripts of this.
			$meta         = wp_get_attachment_metadata( $post_id );
			$meta['file'] = $info['dir'] . $info['name'];
			update_post_meta( $post_id, '_wp_attachment_metadata', $meta );
			if ( ! defined( 'SIRSC_BRUTE_RENAME' ) ) {
				define( 'SIRSC_BRUTE_RENAME', $meta['file'] );
			}
		}

		\SIRSC\Helper\debug( 'START PROCESS ALL REMAINING SIZES FOR ' . $post_id, true, true );
		\SIRSC\Helper\make_images_if_not_exists( $post_id, 'all' );

		$meta = wp_get_attachment_metadata( $post_id );
		update_post_meta( $post_id, '_sirsc_attachment_metadata', $meta );
	}

	/**
	 * Prefilter and fix the file name on upload, following the best practice
	 * for SEO and accessibility when it comes to the filenames.
	 *
	 * @param  string        $filename Unique file name.
	 * @param  string        $ext      File extension. Example: ".png".
	 * @param  string        $dir      Directory path.
	 * @param  callable|null $callback Callback function that generates the unique file name.
	 * @param  array         $alt      Array of alternate file names that were checked for collisions.
	 * @param  int|string    $number   The highest number used or an empty string if unused.
	 * @return string
	 */
	public static function upload_name_prefilter( $filename = '', $ext = '', $dir = '', $callback = null, $alt = [], $number = '' ) {

		$ext = strtolower( $ext );
		if ( ! empty( $filename ) && in_array( $ext, [ '.gif', '.png', '.jpg', '.jpeg', '.webp', '.svg', '.avif' ], true ) ) {
			if ( empty( $number ) ) {
				$name = substr( $filename, 0, -1 * strlen( $ext ) );
			} else {
				$name = substr( $filename, 0, -1 * ( strlen( $ext ) + strlen( (string) $number ) + 1 ) );
			}

			$count = 0;
			$name  = strtolower( str_replace( '.', '-', $name ) );
			$name  = str_replace( '_', '-', $name );

			// Scaled is a reserved word.
			if ( '-scaled' === substr( $name, -7 ) ) {
				$name = substr( $name, 0, -7 );
			}

			// Use scandir, just like core does.
			$files = @scandir( $dir ); // phpcs:ignore
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					if ( ! substr_count( $file, $name ) ) {
						continue;
					}

					$fname = explode( '.', $file );
					$fext  = '.' . end( $fname );
					if ( $ext !== $fext ) {
						continue;
					}

					$test = explode( $name, $file );
					if ( ! empty( $test[1] ) && substr_count( $test[1], 'x' ) ) {
						continue;
					}

					++$count;
				}
			}

			if ( $count > 0 ) {
				$filename = strtolower( $name . '-' . $count . $ext );
			} else {
				$filename = strtolower( $name . $ext );
			}
		}

		return $filename;
	}

	/**
	 * Admin featured size.
	 *
	 * @param  string $size         Initial size.
	 * @param  int    $thumbnail_id Attachment ID.
	 * @param  int    $post         Post ID.
	 * @return string
	 */
	public static function admin_featured_size( $size, $thumbnail_id = 0, $post = 0 ) { // phpcs:ignore
		$override = get_option( 'sirsc_admin_featured_size' );
		if ( ! empty( $override ) ) {
			return $override;
		}
		return $size;
	}

	/**
	 * Attempt to refresh extra info in the footer of the image details lightbox.
	 */
	public static function refresh_extra_info_footer() {
		if ( empty( $_REQUEST['sirsc_data'] ) ) { // phpcs:ignore
			// Fail-fast.
			return;
		}

		$data = self::has_sirsc_data();
		$id   = ( ! empty( $data['post_id'] ) ) ? (int) $data['post_id'] : 0;
		if ( empty( $id ) ) {
			// Fail-fast.
			return;
		}

		self::the_document_ready_js( 'sirsc_refresh_extra_info_footer( \'' . (int) $id . '\', \'#sirsc-extra-info-footer-' . (int) $id . '\' );' );
	}

	/**
	 * Add the plugin settings and plugin URL links.
	 *
	 * @param array $links The plugin links.
	 */
	public static function plugin_action_links( $links ) { // phpcs:ignore
		$all   = [];
		$all[] = '<a href="' . esc_url( self::$plugin_url ) . '">' . esc_html__( 'Settings', 'sirsc' ) . '</a>';
		$all[] = '<a href="https://iuliacazan.ro/image-regenerate-select-crop">' . esc_html__( 'Plugin URL', 'sirsc' ) . '</a>';
		$all   = array_merge( $all, $links );
		return $all;
	}
}

// Create the class name alias.
class_alias( 'SIRSC_Image_Regenerate_Select_Crop', 'SIRSC' );

$sirsc = SIRSC::get_instance();
add_action( 'wp_loaded', [ $sirsc, 'filter_ignore_global_image_sizes' ] );
require_once SIRSC_DIR . 'inc/placeholder.php';
