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
	<h2><?php \esc_html_e( 'Cron Tasks', 'sirsc' ); ?></h2>
</div>

<div class="as-row small-gap">
	<label class="label-row">
		<input type="checkbox" name="sirsc[cron_bulk_execution]" id="sirsc_cron_bulk_execution" <?php \checked( true, $settings['cron_bulk_execution'] ); ?> <?php setting_is_readonly( 'cron_bulk_execution' ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'execute bulk actions using the WordPress cron tasks instead of the default interface', 'sirsc' ); ?>
		<?php the_info_icon( 'info_cron_bulk_execution', ! empty( $settings['cron_bulk_execution'] ) ? 'last' : '' ); ?>
	</label>
	<?php the_info_text( 'info_cron_bulk_execution', \__( 'This option allows you to offload the execution of bulk actions to the WordPress cron tasks. This will run the intended actions as background tasks, instead of using the plugin default interface.', 'sirsc' ) . '<hr><b>' . \__( 'You will have to adjust the batches size based on your server settings and limitations.', 'sirsc' ) . '</b><hr>' . \__( 'If the batch size is big, the whole processing will finish faster, but it can fail if your server runs out of resources. Setting a small size for the batch will encrease the time required for the whole processing to finish, but is less resource intensive.', 'sirsc' ) ); ?>
	<?php
	if ( ! empty( $settings['cron_bulk_execution'] ) ) {
		?>
		<label class="label-row">
			<input type="number" size="3" name="sirsc[cron_batch_regenerate]" id="sirsc_cron_batch_regenerate" value="<?php echo (int) $settings['cron_batch_regenerate']; ?>" <?php setting_is_readonly( 'cron_batch_regenerate' ); ?> data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'the number of images to be regenerated at each cron task iteration', 'sirsc' ); ?>
		</label>
		<label class="label-row small-gap">
			<input type="number" size="3" name="sirsc[cron_batch_cleanup]" id="sirsc_cron_batch_cleanup" value="<?php echo (int) $settings['cron_batch_cleanup']; ?>" <?php setting_is_readonly( 'cron_batch_cleanup' ); ?> data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'the number of images to be cleanup at each cron task iteration', 'sirsc' ); ?>
		</label>
		<label class="label-row">
			<button type="button" class="button has-icon tiny" name="sirsc-settings-cancel-crons" value="submit" data-sirsc-autosubmit="click"><span class="dashicons dashicons-trash"></span><?php \esc_html_e( 'Cancel', 'sirsc' ); ?></button>
			<?php \esc_html_e( 'Cancel all currently scheduled tasks that aim to regenerate or cleanup the images.', 'sirsc' ); ?>
		</label>
		<?php
	}
	?>
</div>
