<?php
/**
 * Cron functions for SIRSC.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Cron;

define( 'SIRSC_JOBS_DB_VER', 2.0 );

\add_action( 'init', __NAMESPACE__ . '\\add_cron_scheduled', 0 );
\add_action( 'init', __NAMESPACE__ . '\\check_cron_scheduled_tasks' );
\add_action( 'init', __NAMESPACE__ . '\\hookup_tasks', 60 );
\add_action( 'shutdown', __NAMESPACE__ . '\\cron_sanity_check' );

/**
 * Add filter for the custom cron schedule.
 */
function add_cron_scheduled() {
	\add_filter( 'cron_schedules', __NAMESPACE__ . '\\custom_cron_frequency' );
}

/**
 * Filter the usable args.
 *
 * @param  array $args The cron task arguments.
 * @return array
 */
function filter_the_args( $args = [] ) { // phpcs:ignore
	$new = [];
	if ( ! empty( $args ) ) {
		foreach ( $args as $k => $v ) {
			if ( in_array( $k, [ 'type', 'cpt', 'size' ], true ) ) {
				$new[ $k ] = $v;
			}
		}
	}

	return $new;
}

/**
 * Get hook for action.
 *
 * @param  string $name Function name.
 * @param  array  $args Function arguments.
 * @return string
 */
function get_hook_string( string $name = '', array $args = [] ): string {
	$args = filter_the_args( $args );
	$info = substr_count( $name, 'raw' ) ? 'RAW' : 'BULK';
	if ( substr_count( $name, 'adon' ) ) {
		$info .= 'A';
	} else {
		$info .= substr_count( $name, 'regenerate' ) ? 'R' : 'C';
	}

	$hook = 'SIRSC-' . $info . '-' . md5( \wp_json_encode( $args ) );
	return $hook;
}

/**
 * Assess if the custom event is scheduled in the cron taks.
 *
 * @param  string $hook The cron task type.
 * @return int
 */
function is_scheduled( $hook ): int {
	return (int) \wp_next_scheduled( $hook );
}

/**
 * Schedule a custom task.
 *
 * @param string $hook Hook handle.
 * @param string $name Function to run.
 * @param array  $args Function arguments.
 */
function trigger_task_schedule( $hook, $name = '', $args = [] ) { // phpcs:ignore
	$tasks = \get_option( 'sirsc_jobs_list', [] );
	if ( empty( $tasks[ $hook ] ) ) {
		$tasks[ $hook ] = [
			'name'  => $name,
			'args'  => $args,
			'start' => time(),
		];
		\update_option( 'sirsc_jobs_list', $tasks );
		\wp_schedule_event( time(), 'every_minute', $hook );

		\SIRSC\Debug\bulk_log_write( 'CRON TASK <b>' . $hook . '</b> ' . $name . ' (' . \wp_json_encode( $args ) . ' ) <div>' . \__( 'the cron task has been scheduled', 'sirsc' ) . '</div>' );
	}
}

/**
 * Assess custom task by name and arguments.
 *
 * @param string $name Function name.
 * @param array  $args Function arguments.
 */
function assess_task( string $name = '', array $args = [] ) {
	if ( isset( $args['start'] ) ) {
		unset( $args['start'] );
	}
	?>
	<div class="lightbox-title label-row">
		<h2><?php \esc_html_e( 'Cron Task', 'sirsc' ); ?></h2>
		<button class="button has-icon tiny close-button" onclick="sirscCloseLightbox();">
			<span class="dashicons dashicons-no"></span>
		</button>
	</div>
	<?php
	$hook = get_hook_string( $name, $args );
	if ( ! is_scheduled( $hook ) ) {
		trigger_task_schedule( $hook, $name, $args );
		$message = \__( 'The action has been scheduled and the cron task will run in the background. You can close the dialog box.', 'sirsc' );
	} else {
		$message = \__( 'The action is currently in progress, the cron task runs in the background. You can close the dialog box.', 'sirsc' );
	}

	$info = get_hook_info( $hook );
	?>
	<div class="inside as-target sirsc-bulk-action sirsc-bulk-regen">
		<div>
			<p class="sirsc-message success"><?php echo \wp_kses_post( $message ); ?></p>
			<div class="sirsc-message info"><?php echo \wp_kses_post( $info['text'] ); ?></div>
			<p><?php \esc_html_e( 'The cron task is scheduled to run every minute, please be patient until the task finishes.', 'sirsc' ); ?> <?php \esc_html_e( 'If you want to cancel the remaining execution and unshedule the task, click the button below.', 'sirsc' ); ?></p>
		</div>
		<div>
			<p><button class="button has-icon" onclick="sirscCancelCronTask( '<?php echo \esc_attr( $hook ); ?>' )"><span class="dashicons dashicons-trash"></span> <?php \esc_html_e( 'Cancel execution', 'sirsc' ); ?></a><p>
		</div>
	</div>
	<?php
	if ( ! empty( $info['data']['name'] ) ) {
		if ( 'cleanup_image_sizes_on_request' === $info['data']['name'] ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscHideCleanupButton( \'' . $info['data']['args']['size'] . '\' );' );
		} elseif ( 'regenerate_image_sizes_on_request' === $info['data']['name'] ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscHideRegenerateButton( \'' . $info['data']['args']['size'] . '\' );' );
		} elseif ( 'raw_cleanup_on_request' === $info['data']['name'] ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscHideRawButton( \'' . $info['data']['args']['type'] . '\' );' );
		}
	}
}

/**
 * Cancel custom task dialog.
 *
 * @param string $hook Hook handle.
 */
function cancel_task( string $hook = '' ) {
	$info = get_hook_info( $hook );
	trigger_task_unschedule( $hook );
	?>
	<div class="lightbox-title label-row">
		<h2><?php \esc_html_e( 'Cron Task', 'sirsc' ); ?></h2>
		<button class="button has-icon tiny close-button" onclick="sirscCloseLightbox();">
			<span class="dashicons dashicons-no"></span>
		</button>
	</div>
	<div class="inside as-target sirsc-bulk-action sirsc-bulk-regen">
		<div>
			<p class="sirsc-message success"><?php \esc_html_e( 'The scheduled task has been canceled.', 'sirsc' ); ?></p>
		</div>
	</div>
	<?php
	if ( ! empty( $info['data']['name'] ) ) {
		if ( 'cleanup_image_sizes_on_request' === $info['data']['name'] ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscShowCleanupButton( \'' . $info['data']['args']['size'] . '\' );' );
		} elseif ( 'regenerate_image_sizes_on_request' === $info['data']['name'] ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscShowRegenerateButton( \'' . $info['data']['args']['size'] . '\' );' );
		} elseif ( 'raw_cleanup_on_request' === $info['data']['name'] ) {
			\SIRSC\Helper\the_document_ready_js( 'sirscShowRawButton( \'' . $info['data']['args']['type'] . '\' );' );
		}
	}
}

/**
 * Returns the cleaned list of custom cron tasks.
 *
 * @return array
 */
function custom_list_of_tasks(): array {
	$opt = \get_option( 'sirsc_jobs_list', [] );
	if ( ! empty( $opt ) ) {
		$fin = [];
		foreach ( $opt as $hook => $info ) {
			$args = ! empty( $info['args'] ) ? $info['args'] : [];
			if ( is_scheduled( $hook, $args ) ) {
				$fin[ $hook ] = $info;
			}
		}
		if ( $fin !== $opt ) {
			\update_option( 'sirsc_jobs_list', $fin );
			$opt = $fin;
		}
	}

	return $opt;
}

/**
 * Maybe schedule tasks.
 */
function maybe_schedule_tasks() {
	$tasks = custom_list_of_tasks();
	if ( ! empty( $tasks ) ) {
		foreach ( $tasks as $hook => $attr ) {
			if ( ! is_scheduled( $hook ) ) {
				\wp_schedule_event( time(), 'every_minute', $hook );
			}
		}
	}
}

/**
 * Hookup the custom tasks.
 */
function hookup_tasks() {
	$tasks = custom_list_of_tasks();
	if ( ! empty( $tasks ) ) {
		foreach ( $tasks as $hook => $info ) {
			\add_action( $hook, function() use ( $hook, $info ) { // phpcs:ignore
				$args = filter_the_args( $info['args'] );
				run_task( $hook, $args );
			} );
		}
	}
}

/**
 * Run a custom task.
 *
 * @param string $hook Task hook.
 * @param array  $args The cron task arguments.
 */
function run_task( $hook = '', $args = [] ) { // phpcs:ignore
	ob_start();

	$run   = false;
	$tasks = custom_list_of_tasks();
	$name  = ! empty( $tasks[ $hook ]['name'] ) ? $tasks[ $hook ]['name'] : '';
	$args  = ! empty( $tasks[ $hook ]['args'] ) ? $tasks[ $hook ]['args'] : [];

	switch ( $name ) {
		case 'regenerate_image_sizes_on_request':
			\SIRSC\Helper\regenerate_image_sizes_on_request( 'continue', $args['size'], $args['cpt'] );
			$run = true;
			break;

		case 'cleanup_image_sizes_on_request':
			\SIRSC\Helper\cleanup_image_sizes_on_request( 'continue', $args['size'], $args['cpt'] );
			$run = true;
			break;

		case 'raw_cleanup_on_request':
			\SIRSC\Helper\raw_cleanup_on_request( 'continue', $args['type'], $args['cpt'] );
			$run = true;
			break;

		case 'sirsc_adon_ui_execute_assess':
			if ( class_exists( '\SIRSC_Adons_Uploads_Inspector' ) ) {
				$info = \SIRSC_Adons_Uploads_Inspector::run_assessment_step();
				$run  = true;
			}
			break;

		default:
			break;
	}

	if ( true === $run ) {
		if ( empty( $info ) ) {
			$info = get_hook_info( $hook );
		}

		if ( empty( $info['total'] ) ) {
			trigger_task_unschedule( $hook );
		}
	}

	ob_get_clean();
}

/**
 * Register the custom frequency for tasks.
 *
 * @param  array $schedules The avalable frequencies of tasks.
 * @return array
 */
function custom_cron_frequency( array $schedules ): array {
	if ( ! isset( $schedules['every_minute'] ) ) {
		$schedules['every_minute'] = [
			'interval' => 60,
			'display'  => \__( 'Every minute', 'sirsc' ),
		];
	}

	return $schedules;
}

/**
 * Cron sanity check.
 */
function cron_sanity_check() {
	$checked = \get_transient( 'sirsc_cron_sanity_check' );
	if ( false === $checked ) {
		maybe_schedule_tasks();
		\set_transient( 'sirsc_cron_sanity_check', time(), 1 * HOUR_IN_SECONDS );
	}
}

/**
 * This method is intended for scheduling the cron task only once, not at each runtime.
 * To do so, we set in the database an option we check against, and only schedule the daily
 * cron task if that is different.
 *
 * Doing so, we can then force the engine to re-create the cron task later, if needed,
 * by changing the option value.
 *
 * @param bool $force True to force the reschedule of the cron job regardless of the context.
 */
function check_cron_scheduled_tasks( bool $force = false ) {
	$db_version = (float) \get_site_option( 'sirsc_jobs_db_ver', '' );
	if ( (float) SIRSC_JOBS_DB_VER !== $db_version || true === $force ) {
		// Update the database option, so that the rest of the execution to take place only if needed.
		\update_site_option( 'sirsc_jobs_db_ver', SIRSC_JOBS_DB_VER );
		maybe_remove_tasks();
		maybe_schedule_tasks();
	}
}

/**
 * Remove the custom event from the cron task.
 */
function maybe_remove_tasks() {
	$tasks = \get_option( 'sirsc_jobs_list', [] );
	foreach ( $tasks as $task => $info ) {
		\wp_unschedule_hook( $task );
		\wp_clear_scheduled_hook( $task );
	}

	$all = \_get_cron_array();
	if ( ! empty( $all ) ) {
		foreach ( $all as $time => $event ) {
			$hook = is_array( $event ) ? array_keys( $event ) : [];
			$hook = is_array( $hook ) ? reset( $hook ) : '';
			if ( substr_count( $hook, 'SIRSC-' ) ) {
				\wp_unschedule_hook( $hook );
				\wp_clear_scheduled_hook( $hook );
			}
		}
	}
}

/**
 * Unschedule a custom task.
 *
 * @param string $hook Hook handle.
 */
function trigger_task_unschedule( string $hook ) {
	$opt = \get_option( 'sirsc_jobs_list', [] );
	if ( ! empty( $opt[ $hook ] ) ) {
		// Attempt to reset the action.
		get_hook_info( $hook, true );

		$name = isset( $opt[ $hook ]['name'] ) ? $opt[ $hook ]['name'] : '';
		$args = isset( $opt[ $hook ]['args'] ) ? $opt[ $hook ]['args'] : [];
		$diff = time() - $opt[ $hook ]['start'];

		$h = floor( $diff / 3600 );
		$m = (int) gmdate( 'i', $diff % 3600 );
		$s = (int) gmdate( 's', $diff % 3600 );

		$time = [];
		if ( ! empty( $h ) ) {
			$time[] = $h . ' ' . \__( 'hours', 'sirsc' );
		}
		if ( ! empty( $m ) ) {
			$time[] = $m . ' ' . \__( 'minutes', 'sirsc' );
		}
		if ( ! empty( $s ) ) {
			$time[] = $s . ' ' . \__( 'seconds', 'sirsc' );
		}

		$time = implode( ', ', $time );

		\SIRSC\Debug\bulk_log_write( 'CRON TASK <b>' . $hook . '</b> ' . $name
			. ' (' . \wp_json_encode( $args ) . ' ) <div>'
			// Translators: %s - duration.
			. sprintf( \__( 'the cron task has been unscheduled and run for %s.', 'sirsc' ), $time )
			. '</div>'
		);

		unset( $opt[ $hook ] );
		\update_option( 'sirsc_jobs_list', $opt );
	}

	\wp_unschedule_hook( $hook );
	\wp_clear_scheduled_hook( $hook );
}

/**
 * Get hook info.
 *
 * @param  string $hook  Hook handle.
 * @param  bool   $reset Reset counters.
 * @return array
 */
function get_hook_info( string $hook = '', bool $reset = false ): array {
	$info = [
		'text'  => '',
		'total' => 0,
	];

	$tasks = custom_list_of_tasks();
	if ( ! empty( $tasks[ $hook ] ) ) {
		$name = $tasks[ $hook ]['name'] ?? '';
		$args = $tasks[ $hook ]['args'] ?? [];

		$info['data'] = $tasks[ $hook ];

		switch ( $name ) {
			case 'regenerate_image_sizes_on_request':
				if ( true === $reset ) {
					// Start from the beginning of the list.
					\SIRSC\Helper\reset_bulk_action_last_id( $args['size'], $args['cpt'] );
				}
				$limit = \SIRSC::$settings['cron_batch_regenerate'];
				$step  = \SIRSC\Helper\bulk_action_query( $args['size'], $args['cpt'], $limit );

				$info['text']  = \__( 'Remaining to regenerate', 'sirsc' ) . ': <b>' . (int) $step['total'] . '</b>';
				$info['total'] = (int) $step['total'];
				break;

			case 'cleanup_image_sizes_on_request':
				if ( true === $reset ) {
					// Start from the beginning of the list.
					\SIRSC\Helper\reset_bulk_action_last_id( $args['size'], $args['cpt'], 'c-' );
				}
				$limit = \SIRSC::$settings['cron_batch_cleanup'];
				$step  = \SIRSC\Helper\bulk_action_query( $args['size'], $args['cpt'], $limit, 'c-' );

				$info['text']  = \__( 'Remaining to clean up', 'sirsc' ) . ': <b>' . (int) $step['total'] . '</b>';
				$info['total'] = (int) $step['total'];
				break;

			case 'raw_cleanup_on_request':
				if ( true === $reset ) {
					// Start from the beginning of the list.
					\SIRSC\Helper\reset_bulk_action_last_id( $args['type'], $args['cpt'], 'rc-' );
				}
				$limit = \SIRSC::$settings['cron_batch_cleanup'];
				$step  = \SIRSC\Helper\bulk_action_query( $args['type'], $args['cpt'], $limit, 'rc-' );

				$info['text']  = \__( 'Remaining to clean up', 'sirsc' ) . ': <b>' . (int) $step['total'] . '</b>';
				$info['total'] = (int) $step['total'];
				break;

			case 'sirsc_adon_ui_execute_assess':
				if ( class_exists( '\SIRSC_Adons_Uploads_Inspector' ) ) {
					if ( true === $reset ) {
						// Start from the beginning of the list.
						\SIRSC_Adons_Uploads_Inspector::start_over();
						\SIRSC_Adons_Uploads_Inspector::reset_assess_counters();
					}

					$total = \SIRSC_Adons_Uploads_Inspector::compute_remaining_to_process();

					ob_start();
					\SIRSC_Adons_Uploads_Inspector::compute_progress_bar();
					$text = ob_get_clean();
					$text = str_replace( ' class="sirsc-progress-text"', ' class="sirsc-progress-text" style="font-size: 11px; line-height: 1; text-align: center;"', $text );

					$info['text']  = $text;
					$info['total'] = (int) $total;
				}
				break;

			default:
				break;
		}
	}

	return $info;
}
