<?php
/**
 * SIRSC admin functionality.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Admin;

?>

<hr>

<div class="label-row as-title">
	<h2><?php \esc_html_e( 'General Settings', 'sirsc' ); ?></h2>
</div>

<div class="as-row columns-4 a-middle small-gap">
	<label class="label-row">
		<input type="checkbox" name="sirsc[listing_tiny_buttons]" id="sirsc_listing_tiny_buttons"
			<?php \checked( true, $settings['listing_tiny_buttons'] ); ?> <?php setting_is_readonly( 'listing_tiny_buttons' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'show small buttons in the media screen', 'sirsc' ); ?>
	</label>
	<label class="label-row small-gap">
		<input type="checkbox" name="sirsc[media_grid_buttons]" id="sirsc_media_grid_buttons"
			<?php \checked( true, $settings['media_grid_buttons'] ); ?> <?php setting_is_readonly( 'media_grid_buttons' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'show buttons in the media screen in grid mode', 'sirsc' ); ?>
	</label>
	<label class="label-row small-gap">
		<input type="checkbox" name="sirsc[listing_show_summary]" id="sirsc_listing_show_summary"
			<?php \checked( true, $settings['listing_show_summary'] ); ?> <?php setting_is_readonly( 'listing_show_summary' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'show attachment image sizes summary in the media screen', 'sirsc' ); ?>
	</label>

	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<label class="label-row small-gap">
			<input type="checkbox" name="sirsc[disable_woo_thregen]" id="sirsc_disable_woo_thregen"
				<?php \checked( true, $settings['disable_woo_thregen'] ); ?> <?php setting_is_readonly( 'disable_woo_thregen' ); ?> data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'turn off the WooCommerce background thumbnails regenerate', 'sirsc' ); ?>
		</label>
	<?php endif; ?>

	<?php if ( defined( 'EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH' ) ) : ?>
		<label class="label-row small-gap">
			<input type="checkbox" name="sirsc[sync_settings_ewww]" id="sirsc_sync_settings_ewww"
				<?php \checked( true, $settings['sync_settings_ewww'] ); ?> <?php setting_is_readonly( 'sync_settings_ewww' ); ?> data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'sync ignored image sizes with EWWW Image Optimizer plugin', 'sirsc' ); ?>
			<?php the_info_icon( 'info_sync_settings_ewww', 'last' ); ?>
		</label>
		<?php the_info_text( 'info_sync_settings_ewww', \__( 'This option allows you to sync <em>disable creation</em> image sizes from <b>EWWW Image Optimizer</b> plugin with the <em>global ignore</em> image sizes from <b>Image Regenerate & Select Crop</b>. In this way, when you update the settings in one of the plugins, the settings will be synced in the other plugin.', 'sirsc' ) ); ?>
	<?php endif; ?>

	<label class="label-row small-gap">
		<input type="checkbox" name="sirsc[bulk_actions_descending]" id="sirsc_bulk_actions_descending"
			<?php \checked( true, $settings['bulk_actions_descending'] ); ?> <?php setting_is_readonly( 'bulk_actions_descending' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'bulk regenerate/cleanup execution starts from the most recent files', 'sirsc' ); ?>
		<?php the_info_icon( 'info_bulk_actions_descending', 'last' ); ?>
	</label>
	<?php the_info_text( 'info_bulk_actions_descending', \__( 'This option allows you to run the bulk cleanup and bulk regenerate actions starting from the most recent files you have in the media library until the most old image is found. This is useful if you know when you can pause/stop the bulk actions, for example when you already run the bulk actions for older files and you only need to run this for more recent uploads.<hr>By default, the bulk actions will run from the oldest to the newest files.', 'sirsc' ) ); ?>

	<label class="label-row small-gap">
		<input type="checkbox" name="sirsc[leave_settings_behind]" id="sirsc_leave_settings_behind"
			<?php \checked( true, $settings['leave_settings_behind'] ); ?> <?php setting_is_readonly( 'leave_settings_behind' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'do not cleanup the settings after the plugin is deactivated', 'sirsc' ); ?>
	</label>

	<label class="label-row small-gap">
		<input type="checkbox" name="sirsc[enable_debug_log]" id="sirsc_enable_debug_log"
			<?php \checked( true, $settings['enable_debug_log'] ); ?> <?php setting_is_readonly( 'enable_debug_log' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'turn on the custom debug log for monitoring the events execution', 'sirsc' ); ?>
	</label>

	<label class="label-row small-gap double">
		<input type="checkbox" name="sirsc[force_size_choose]" id="sirsc_force_size_choose"
			<?php \checked( true, $settings['force_size_choose'] ); ?> <?php setting_is_readonly( 'force_size_choose' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'filter and expose the image sizes available for the attachment display settings in the media dialog (any registered available size, even when there is no explicit filter applied)', 'sirsc' ); ?>
	</label>
</div>
