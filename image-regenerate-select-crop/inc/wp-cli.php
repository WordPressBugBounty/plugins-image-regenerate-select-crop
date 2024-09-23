<?php
/**
 * Description: The WP-CLI component of the Image Regenerate & Select Crop plugin.
 *
 * @package sirsc
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Quick WP-CLI command to for SIRSC plugin that allows to regenerate and remove images.
	 */
	class SIRSC_Image_Regenerate_Select_Crop_CLI_Command extends WP_CLI_Command {
		/**
		 * Prepare command arguments.
		 *
		 * @param  array $args Command default arguments.
		 * @return mixed
		 */
		private static function prepare_args( $args ) { // phpcs:ignore
			\SIRSC\Helper\notify_doing_sirsc_cli();

			$rez = [
				'site_id'   => 1,
				'post_type' => '',
				'size_name' => '',
				'parent_id' => '',
				'all_sizes' => [],
			];

			if ( ! isset( $args[0] ) ) {
				WP_CLI::error( esc_html__( 'Please specify the site id (1 if not multisite).', 'sirsc' ) );
				return;
			} else {
				$rez['site_id'] = intval( $args[0] );
			}

			if ( is_multisite() ) {
				switch_to_blog( $rez['site_id'] );
			}

			WP_CLI::line( '******* SIRSC EXECUTE OPERATION ON SITE ' . $rez['site_id'] . ' *******' );
			if ( ! isset( $args[1] ) ) {
				$pt = get_option( 'sirsc_types_options', [] );
				if ( ! empty( $pt ) ) {
					$av = '';
					foreach ( $pt as $k => $v ) {
						$av .= ( '' === $av ) ? '' : ', ';
						$av .= $v;
					}
				} else {
					$pt = \SIRSC::get_all_post_types_plugin();
					$av = '';
					foreach ( $pt as $k => $v ) {
						$av .= ( '' === $av ) ? '' : ', ';
						$av .= $k;
					}
				}
				WP_CLI::error( 'Please specify the post type (one of: ' . $av . ', etc).' );
				return;
			} else {
				$rez['post_type'] = trim( $args[1] );
			}

			if ( empty( $args['the_command'] ) ) {
				$args['the_command'] = 'regenerate';
			}

			if ( ! empty( $args['the_command'] )
				&& ( 'rawcleanup' === $args['the_command'] || 'resetcleanup' === $args['the_command'] ) ) {
				// This is always all.
				$args[2] = 'all';
			}

			if ( 'seorename' !== $args['the_command'] ) {
				$all_sizes        = \SIRSC::get_all_image_sizes();
				$rez['all_sizes'] = $all_sizes;
				if ( ! isset( $args[2] ) ) {
					$ims = '';
					foreach ( $all_sizes as $k => $v ) {
						$ims .= ( '' === $ims ) ? '' : ', ';
						$ims .= $k;
					}
					WP_CLI::error( 'Please specify the image size name (one of: ' . $ims . ').' );
					return;
				} elseif ( 'all' === $args[2] || ! empty( $all_sizes[ $args[2] ] )
					|| ! empty( $args['is_cleanup'] ) ) {
					$rez['size_name'] = trim( $args[2] );
				} else {
					WP_CLI::error( 'Please specify a valid image size name.' );
					return;
				}
			}

			if ( isset( $args[3] ) ) {
				$rez['parent_id'] = (int) $args[3];
			}

			return $rez;
		}

		/**
		 * Assess the command arguments to see if this should be verbose.
		 *
		 * @param  array $assoc_args Command associated arguments.
		 * @return bool
		 */
		private static function is_verbose( $assoc_args ) { // phpcs:ignore
			if ( ! empty( $assoc_args['verbose'] ) ) {
				return true;
			}
			if ( ! empty( $assoc_args['v'] ) ) {
				return true;
			}
			if ( ! empty( $assoc_args['show-info'] ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Arguments order and types: (int)site_id (string)post_type (string)size_name (int)parent_id. This command targets images based on the relations with the specified post type and perhaps a parent, and performs the sub-sizes regeneration for all or the specified sub-sizes (delimited with comma).
		 *
		 * ## OPTIONS
		 *
		 * <site_id>
		 * : The site where the actions will run.
		 *
		 * <post_type>
		 * : The post type slug for targeting the images. If multiple, separate with comma.
		 *
		 * <size_name>
		 * : The image sub-size slug. If multiple, separate with comma.
		 *
		 * <parent_id>
		 * : The image parent.
		 *
		 * [--verbose]
		 * : Whether or not to display informations during the execution.
		 *
		 * @param array $args       Command default arguments.
		 * @param array $assoc_args Command associated arguments.
		 */
		public function regenerate( $args, $assoc_args ) { // phpcs:ignore
			$config = self::prepare_args( $args );
			if ( ! is_array( $config ) ) {
				return;
			}

			if ( ! defined( 'DOING_SIRSC' ) ) {
				// Maybe indicate to other scrips/threads that SIRSC is processing.
				define( 'DOING_SIRSC', true );
			}

			$verbose = self::is_verbose( $assoc_args );
			extract( $config ); // phpcs:ignore
			if ( ! empty( $post_type ) && ! empty( $size_name ) && ! empty( $all_sizes ) ) {
				global $wpdb;

				delete_transient( \SIRSC\Admin\get_count_trans_name( 'cleanup', $post_type, $size_name ) );
				delete_transient( \SIRSC\Admin\get_count_trans_name( 'cleanup', '', $size_name ) );

				$execute_sizes = [];
				if ( 'all' === $size_name ) {
					$execute_sizes = $all_sizes;
				} elseif ( ! empty( $all_sizes[ $size_name ] ) ) {
					$execute_sizes[ $size_name ] = $size_name;
				}
				$rows = self::make_query( $post_type, $parent_id, 'REGENERATE' );
				if ( ! empty( $rows ) && is_array( $rows ) ) {
					if ( ! empty( $execute_sizes ) ) {
						foreach ( $execute_sizes as $sn => $sv ) {
							$progress = \WP_CLI\Utils\make_progress_bar( '------- REGENERATE ' . $sn, count( $rows ) );
							foreach ( $rows as $v ) {
								\SIRSC::load_settings_for_post_id( $v['ID'] );
								if ( ! empty( \SIRSC::$settings['restrict_sizes_to_these_only'] )
									&& ! in_array( $sn, \SIRSC::$settings['restrict_sizes_to_these_only'], true ) ) {
									// This might be restricted from the theme or the plugin custom rules.
									continue;
								}

								\SIRSC\Debug\bulk_log_write( 'WP-CLI regenerate ' . $v['ID'] . ' ' . $sn );
								$filename = get_attached_file( $v['ID'] );
								if ( ! empty( $filename ) && file_exists( $filename ) ) {
									$before = \SIRSC\Helper\get_last_update_time( $v['ID'] );
									\SIRSC\Helper\make_images_if_not_exists( $v['ID'], $sn );
									$after = \SIRSC\Helper\get_last_update_time( $v['ID'] );

									$show_processed = false;
									if ( $before !== $after ) {
										$show_processed = true;
									}

									$th = wp_get_attachment_image_src( $v['ID'], $sn );
									if ( ! empty( $th[0] ) ) {
										if ( $verbose && $show_processed ) {
											WP_CLI::success( $th[0] );
										}
									} else {
										$text = $filename . ' <em>' . esc_html__( 'Could not generate, the original is too small.', 'sirsc' ) . '</em>';
										WP_CLI::line( wp_strip_all_tags( $text ) );
										\SIRSC\Debug\bulk_log_write( 'WP-CLI * ' . $text );
									}
								} else {
									$text = $filename . ' <em>' . esc_html__( 'Could not generate, the original file is missing.', 'sirsc' ) . '</em>';
									WP_CLI::line( wp_strip_all_tags( $text ) );
									\SIRSC\Debug\bulk_log_write( 'WP-CLI * ' . $text );
								}
								$progress->tick();
							}
							$progress->finish();
						}
					}
				}
				WP_CLI::success( 'ALL DONE!' );
			} else {
				WP_CLI::error( 'Unexpected ERROR' );
			}
		}

		/**
		 * Arguments order and types: (int)site_id (string)post_type (string)size_name (int)parent_id. This command targets images based on the relations with the specified post type and perhaps a parent, and performs the sub-sizes cleanup for all or the specified sub-sizes (delimited with comma).
		 *
		 * ## OPTIONS
		 *
		 * <site_id>
		 * : The site where the actions will run.
		 *
		 * <post_type>
		 * : The post type slug for targeting the images. If multiple, separate with comma.
		 *
		 * <size_name>
		 * : The image sub-size slug. If multiple, separate with comma.
		 *
		 * <parent_id>
		 * : The image parent.
		 *
		 * [--verbose]
		 * : Whether or not to display informations during the execution.
		 *
		 * [--force]
		 * : Whether or not to force the execution (also for sub-sizes that are no longer registered).
		 *
		 * @param array $args       Command default arguments.
		 * @param array $assoc_args Command associated arguments.
		 */
		public function cleanup( $args, $assoc_args ) { // phpcs:ignore
			$is_forced = ( ! empty( $assoc_args['force'] ) ) ? true : false;
			$config    = self::prepare_args( array_merge( $args, [ 'is_cleanup' => true ] ) );
			if ( ! is_array( $config ) ) {
				return;
			}

			$verbose = self::is_verbose( $assoc_args );
			extract( $config ); // phpcs:ignore
			if ( ! empty( $post_type ) && ! empty( $size_name ) && ! empty( $all_sizes ) ) {
				global $wpdb;

				delete_transient( \SIRSC\Admin\get_count_trans_name( 'cleanup', $post_type, $size_name ) );
				delete_transient( \SIRSC\Admin\get_count_trans_name( 'cleanup', '', $size_name ) );

				$execute_sizes = [];
				if ( 'all' === $size_name ) {
					$execute_sizes = $all_sizes;
				} elseif ( ! empty( $all_sizes[ $size_name ] ) || $is_forced ) {
					$execute_sizes[ $size_name ] = $size_name;
				}

				$rows = self::make_query( $post_type, $parent_id, 'REMOVE' );
				if ( ! empty( $rows ) && is_array( $rows ) ) {
					if ( ! empty( $execute_sizes ) ) {
						foreach ( $execute_sizes as $sn => $sv ) {
							$progress = \WP_CLI\Utils\make_progress_bar( '------- REMOVE ' . $sn, count( $rows ) );
							foreach ( $rows as $v ) {

								WP_CLI::error( 'trans id ' . \SIRSC\Admin\get_count_trans_name( 'cleanup', $v['post_type'], $sn ) );

								\SIRSC\Debug\bulk_log_write( 'WP-CLI cleanup ' . $v['ID'] . ' ' . $sn );
								\SIRSC\Action\cleanup_attachment_one_size( $v['ID'], $sn, true, $verbose );
								$progress->tick();
							}
							$progress->finish();
						}
					}
				}
				WP_CLI::success( 'ALL DONE!' );
			} else {
				WP_CLI::error( 'Unexpected ERROR' );
			}
		}

		/**
		 * Arguments order and types: (int)site_id (string)post_type (int)parent_id. This command targets images based on the relations with the specified post type and perhaps a parent, and performs the raw cleanup for all the files, excepting the full/original sub-size.
		 *
		 * ## OPTIONS
		 *
		 * <site_id>
		 * : The site where the actions will run.
		 *
		 * <post_type>
		 * : The post type slug for targeting the images. If multiple, separate with comma.
		 *
		 * <parent_id>
		 * : The image parent.
		 *
		 * [--verbose]
		 * : Whether or not to display informations during the execution.
		 *
		 * @param array $args       Command default arguments.
		 * @param array $assoc_args Command associated arguments.
		 */
		public function rawcleanup( $args, $assoc_args ) { // phpcs:ignore
			$config = self::prepare_args(
				array_merge(
					$args,
					[
						'is_cleanup'  => true,
						'the_command' => 'rawcleanup',
					]
				)
			);
			if ( ! is_array( $config ) ) {
				return;
			}

			$verbose = self::is_verbose( $assoc_args );
			extract( $config ); // phpcs:ignore
			if ( ! empty( $post_type ) ) {
				$rows = self::make_query( $post_type, $parent_id, 'REMOVE' );
				if ( ! empty( $rows ) && is_array( $rows ) ) {
					$progress = \WP_CLI\Utils\make_progress_bar(
						'------- RAW REMOVE FILES (keep only originals)',
						count( $rows )
					);
					foreach ( $rows as $v ) {
						\SIRSC\Debug\bulk_log_write( 'WP-CLI rawcleanup ' . $v['ID'] );
						\SIRSC\Action\cleanup_attachment_all_sizes( $v['ID'], true, $verbose );
						$progress->tick();
					}
					$progress->finish();
				}
				WP_CLI::success( 'ALL DONE!' );
			} else {
				WP_CLI::error( 'Unexpected ERROR' );
			}
		}

		/**
		 * Arguments order and types: (int)site_id (string)post_type (int)parent_id. This command targets images based on the relations with the specified post type and perhaps a parent, and performs the reset cleanup for all the sub-sizes, keeping the full/original + only the registered image sizes at the moment when the command runs.
		 *
		 * ## OPTIONS
		 *
		 * <site_id>
		 * : The site where the actions will run.
		 *
		 * <post_type>
		 * : The post type slug for targeting the images. If multiple, separate with comma.
		 *
		 * <parent_id>
		 * : The image parent.
		 *
		 * [--verbose]
		 * : Whether or not to display informations during the execution.
		 *
		 * @param array $args       Command default arguments.
		 * @param array $assoc_args Command associated arguments.
		 */
		public function resetcleanup( $args, $assoc_args ) { // phpcs:ignore
			$config = self::prepare_args(
				array_merge(
					$args,
					[
						'is_cleanup'  => true,
						'the_command' => 'resetcleanup',
					]
				)
			);
			if ( ! is_array( $config ) ) {
				return;
			}

			$verbose = self::is_verbose( $assoc_args );
			extract( $config ); // phpcs:ignore
			if ( ! empty( $post_type ) ) {
				global $wpdb;

				$rows = self::make_query( $post_type, $parent_id, 'REMOVE' );
				if ( ! empty( $rows ) && is_array( $rows ) ) {
					$progress = \WP_CLI\Utils\make_progress_bar(
						'------- RESET REMOVE FILES (keep only registered image sizes)',
						count( $rows )
					);

					$reg   = get_intermediate_image_sizes();
					$sizes = \SIRSC::get_all_image_sizes_plugin();
					$upls  = wp_upload_dir();
					$pref  = trailingslashit( $upls['basedir'] );
					foreach ( $rows as $v ) {
						\SIRSC\Debug\bulk_log_write( 'WP-CLI resetcleanup ' . $v['ID'] );

						$compute = \SIRSC\Helper\compute_image_details( $v['ID'], 'full', $upls, $sizes, true );
						$meta    = '';
						if ( ! empty( $compute ) ) {
							if ( is_array( $compute ) && ! empty( $compute['metadata'] ) ) {
								$meta = $compute['metadata'];
							} elseif ( is_object( $compute ) && ! empty( $compute->metadata ) ) {
								$meta = $compute->metadata;
							}
						}
						if ( empty( $meta ) ) {
							$meta = wp_get_attachment_metadata( $v['ID'] );
						}

						$initial = $meta;
						$summary = \SIRSC::general_sizes_and_files_match( $v['ID'], $meta, $compute );
						if ( ! empty( $summary ) ) {
							foreach ( $summary as $sfn => $info ) {
								if ( empty( $info['registered'] ) ) {
									if ( ! empty( $info['match'] ) ) {
										foreach ( $info['match'] as $sn ) {
											if ( isset( $meta['sizes'][ $sn ] ) ) {
												unset( $meta['sizes'][ $sn ] );
											}
										}
									}
									$removable = $pref . $sfn;
									if ( file_exists( $removable ) ) {
										@unlink( $removable ); // phpcs:ignore
										// Make sure not to delete the original file.
										if ( $verbose ) {
											WP_CLI::success( $removable . ' ' . esc_html__( 'was removed', 'sirsc' ) );
											do_action( 'sirsc_image_file_deleted', $v['ID'], $removable );
										}
									} elseif ( $verbose ) {
										$text = $removable . ' <em>' . esc_html__( 'Could not remove', 'sirsc' ) . ' . ' . esc_html__( 'The image is missing or it is the original file.', 'sirsc' ) . '</em>';
										WP_CLI::line( wp_strip_all_tags( $text ) );
										\SIRSC\Debug\bulk_log_write( 'WP-CLI * ' . $text );
									}
								} elseif ( $verbose ) {
									WP_CLI::line( esc_html__( 'No cleanup necessary for', 'sirsc' ) . ' ' . $v['ID'] . '.' );
								}
							}
						} elseif ( $verbose ) {
							WP_CLI::line( esc_html__( 'No cleanup necessary for', 'sirsc' ) . ' ' . $v['ID'] . '.' );
						}

						if ( $initial !== $meta ) {
							// Update the cleaned meta.
							wp_update_attachment_metadata( $v['ID'], $meta );

							// Re-fetch the meta.
							$image_meta = wp_get_attachment_metadata( $v['ID'] );
							do_action( 'sirsc_attachment_images_ready', $image_meta, $v['ID'] );
						}
						$progress->tick();
					}
					$progress->finish();
				}
				WP_CLI::success( 'ALL DONE!' );
			} else {
				WP_CLI::error( 'Unexpected ERROR' );
			}
		}

		/**
		 * Return the posts that match the SIRSC criteria.
		 *
		 * @param  string         $post_type Maybe a post type.
		 * @param  string|integer $parent_id Attachment parents (numeric or * for all).
		 * @param  string         $action    Action title, regenerate or remove.
		 * @return mixed
		 */
		private function make_query( $post_type = '', $parent_id = 0, $action = 'REGENERATE' ) { // phpcs:ignore
			global $wpdb;
			$args  = [];
			$query = ' SELECT p.ID FROM ' . $wpdb->posts . ' as p ';
			if ( ! empty( $post_type ) && 'all' !== $post_type ) {
				$query .= ' INNER JOIN ' . $wpdb->posts . ' as parent ON( parent.ID = p.post_parent ) ';
			}

			if ( ! empty( \SIRSC::$settings['regenerate_only_featured'] ) ) {
				$query .= ' INNER JOIN ' . $wpdb->postmeta . ' as pm ON( pm.meta_value = p.ID and pm.meta_key = \'_thumbnail_id\' ) ';
			}

			$query .= ' WHERE ( p.post_mime_type like %s AND p.post_mime_type not like %s ) ';
			$args[] = '%' . $wpdb->esc_like( 'image/' ) . '%';
			$args[] = '%' . $wpdb->esc_like( 'image/svg' ) . '%';

			if ( ! empty( $post_type ) && 'all' !== $post_type ) {
				$query .= ' AND parent.post_type = %s ';
				$args[] = $post_type;
				if ( ! empty( $parent_id ) ) {
					$query .= ' AND parent.ID = %d ';
					$args[] = $parent_id;
					WP_CLI::line( '------- EXECUTE ' . $action . ' FOR IMAGES ASSOCIATED TO ' . $post_type . ' WITH ID = ' . $parent_id . ' -------' );
				} else {
					$query .= ' AND parent.ID IS NOT NULL ';
					WP_CLI::line( '------- EXECUTE ' . $action . ' FOR ALL IMAGES ASSOCIATED TO ' . $post_type . ' -------' );
				}
			}

			if ( 'all' === $post_type ) {
				WP_CLI::line( '------- EXECUTE ' . $action . ' FOR ALL IMAGES ASSOCIATED TO ALL TYPES -------' );
			}

			if ( ! empty( \SIRSC::$settings['bulk_actions_descending'] ) ) {
				$query .= ' ORDER BY p.ID DESC LIMIT 0, 500000';
			} else {
				$query .= ' ORDER BY p.ID ASC LIMIT 0, 500000';
			}

			$rows = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A ); // phpcs:ignore
			return $rows;
		}

		/**
		 * Arguments order and types : (int)site_id (string)post_type. This command targets images based on the relations with the specified post type, then SEO renames the files assciated with these. Additionally, if the --content-replace flag is passed to the command, then the database content replacement will be triggered too.
		 *
		 * ## OPTIONS
		 *
		 * <site_id>
		 * : The site where the actions will run.
		 *
		 * <post_type>
		 * : The post type slug for targeting the images. If multiple, separate with comma.
		 *
		 * [--content-replace=<all|each|only>]
		 * : Whether or not to replacing hardcoded URLs in the database content, and which type of replace (use this if your content is not dynamical, and images are not embedded programmaticaaly).
		 *
		 * ---
		 * default: each
		 * options:
		 *   - all: the content replacement is initiated after the execution of all the renames
		 *   - each: the content replacement is initiated after each rename (recommended)
		 *   - only: no file renames performend, executes only the content replacement for the data in the SEO Images log, if that is not empty
		 * ---
		 *
		 * [--verbose]
		 * : Whether or not to display informations during the execution.
		 *
		 * ## EXAMPLES
		 *
		 * wp sirsc seorename 1 product
		 *
		 * wp sirsc seorename 1 product,product_variation --content-replace
		 *
		 * @param array $args       Command default arguments.
		 * @param array $assoc_args Command associated arguments.
		 */
		public function seorename( $args, $assoc_args ) { // phpcs:ignore
			if ( ! class_exists( 'SIRSC_Adons' ) ) {
				WP_CLI::error( 'The Images SEO adon is not available.' );
				return;
			}

			\SIRSC_Adons::detect_adons();
			if ( ! class_exists( 'SIRSC_Adons_Images_SEO' ) ) {
				WP_CLI::error( 'The Images SEO adon is not available.' );
				return;
			}

			$config = self::prepare_args(
				array_merge(
					$args,
					[
						'is_cleanup'  => true,
						'the_command' => 'seorename',
						'size_name'   => 'all',
					]
				)
			);
			if ( ! is_array( $config ) ) {
				return;
			}

			if ( ! defined( 'DOING_SIRSC' ) ) {
				// Maybe indicate to other scrips/threads that SIRSC is processing.
				define( 'DOING_SIRSC', true );
			}

			$verbose = self::is_verbose( $assoc_args );

			$db_replace_type = '';
			if ( isset( $assoc_args['content-replace'] ) ) {
				if ( 'all' === $assoc_args['content-replace'] ) {
					$db_replace_type = 'all';
				} elseif ( 'only' === $assoc_args['content-replace'] ) {
					$db_replace_type = 'only';
				} else {
					$db_replace_type = 'each';
				}
			}

			if ( 'only' === $db_replace_type ) {
				self::process_seo_rename_log_file( $db_replace_type, $verbose );
				\WP_CLI::line( '🟦 SEO IMAGES - ' . \esc_html__( 'Permalinks refresh', 'sirsc' ) );
				\WP_CLI::runcommand( 'rewrite flush --quiet', [] );
				\WP_CLI::line( '🟦 SEO IMAGES - ' . \esc_html__( 'Cache refresh', 'sirsc' ) );
				\WP_CLI::runcommand( 'cache flush --quiet', [] );
				\WP_CLI::success( '>>>> ALL DONE!' );
				return;
			}

			extract( $config ); // phpcs:ignore
			if ( ! empty( $post_type ) ) {
				global $wpdb;

				$seo    = \SIRSC_Adons_Images_SEO::get_instance();
				$option = get_option( 'sirsc_adons_is_bulk_rename', [] );
				$total  = $wpdb->get_var( $seo::rename_get_query( $post_type, 0, true ) ); // phpcs:ignore
				$option = [
					'types'     => $post_type,
					'total'     => $total,
					'last_id'   => 0,
					'processed' => 0,
				];
				update_option( 'sirsc_adons_is_bulk_rename', $option );
				$option = get_option( 'sirsc_adons_is_bulk_rename', [] );
				\SIRSC\Debug\log_delete( 'seo-images' );

				if ( ! empty( $option['total'] ) ) {
					if ( 'each' === $db_replace_type ) {
						$hint = '🟦 SEO IMAGES - RENAME FILES FOR ' . $total . ' ATTACHMENTS & FIND & REPLACE DATABASE CONTENT';
					} else {
						$hint = '🟦 SEO IMAGES - RENAME FILES FOR ' . $total . ' ATTACHMENTS';
					}
					$progress = \WP_CLI\Utils\make_progress_bar( $hint, $total );

					$query = $seo::rename_get_query( $option['types'], $option['last_id'] );
					$query = str_replace( ' LIMIT 0, ' . $seo::PROCESS_BATCH_SIZE . ' ', '', $query );
					$rows  = $wpdb->get_results( $query ); // phpcs:ignore
					if ( ! empty( $rows ) ) {
						foreach ( $rows as $row ) {
							$option['last_id'] = (int) $row->ID;

							ob_start();
							$info = $seo::assess_attachment_title( $row, $option['types'] );
							$seo::rename_image_filename( $row->ID, $info['title'], 0, $info['parent_type'], true, $info['message'] );
							ob_get_clean();

							++$option['processed'];
							update_option( 'sirsc_adons_is_bulk_rename', $option );

							if ( $verbose ) {
								$text = esc_html__( 'Rename files for attachment', 'sirsc' );
								\SIRSC\Debug\bulk_log_write( 'WP-CLI * ' . $row->ID . ' <em>' . $text . '</em> ' );
								WP_CLI::line( $text . ' ' . $row->ID );
							}

							if ( 'each' === $db_replace_type ) {
								if ( $verbose ) {
									WP_CLI::line( esc_html__( 'Find and replace database content for attachment files', 'sirsc' ) );
								}
								self::process_seo_rename_log_file( $db_replace_type, $verbose );
							}

							$progress->tick();
						}
					}
					$progress->finish();
				}

				if ( 'all' === $db_replace_type ) {
					self::process_seo_rename_log_file( $db_replace_type, $verbose );
				}

				\WP_CLI::line( '🟦 SEO IMAGES - ' . esc_html__( 'Permalinks refresh', 'sirsc' ) );
				\WP_CLI::runcommand( 'rewrite flush --quiet', [] );
				\WP_CLI::line( '🟦 SEO IMAGES - ' . esc_html__( 'Cache refresh', 'sirsc' ) );
				\WP_CLI::runcommand( 'cache flush --quiet', [] );
				\WP_CLI::success( '>>>> ALL DONE!' );
			} else {
				\WP_CLI::error( 'Unexpected ERROR' );
			}
		}

		/**
		 * Process the SEO Images log file.
		 *
		 * @param  string  $db_replace_type Replace type.
		 * @param  boolean $verbose         Show info.
		 * @return void
		 */
		private static function process_seo_rename_log_file( $db_replace_type = 'all', $verbose = false ) { // phpcs:ignore
			$fake_progress = true;
			$replacements  = \SIRSC\Debug\log_read( 'seo-images' );

			if ( 'each' === $db_replace_type ) {
				$hint = '';
			} else {
				$hint = '🟦 SEO IMAGES - FIND & REPLACE DATABASE CONTENT';
			}

			$matches = [];
			if ( ! empty( $replacements ) ) {
				$lines = explode( PHP_EOL, $replacements );
				if ( ! empty( $lines ) ) {
					foreach ( $lines as $line ) {
						$old = strstr( $line, '","', true );
						$old = str_replace( '"', '', $old );
						$new = strstr( $line, '","', false );
						$new = str_replace( '","', '', $new );
						$new = str_replace( '"', '', $new );
						if ( ! empty( $old ) && ! empty( $new ) ) {
							$matches[ $old ] = $new;
						}
					}
				}
			}

			$use_progress = ( ! empty( $hint ) ) ? true : false;
			if ( $use_progress ) {
				$progress = \WP_CLI\Utils\make_progress_bar( $hint, count( $matches ) );
			}

			if ( ! empty( $matches ) ) {
				foreach ( $matches as $old => $new ) {
					if ( $verbose ) {
						WP_CLI::line( 'Replace: ' . $old . ' -> ' . $new );
					}
					\WP_CLI::runcommand( 'search-replace "' . $old . '" "' . $new . '" --precise --quiet', [] );
					if ( $use_progress ) {
						$progress->tick();
					}
				}
			}
			if ( $use_progress ) {
				$progress->finish();
			}

			\SIRSC\Debug\log_delete( 'seo-images' );
		}
	}

	WP_CLI::add_command( 'sirsc', 'SIRSC_Image_Regenerate_Select_Crop_CLI_Command' );
}
