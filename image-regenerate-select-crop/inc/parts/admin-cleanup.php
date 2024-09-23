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
	<h2><?php \esc_html_e( 'General Cleanup', 'sirsc' ); ?></h2>
</div>

<p class="small-gap"<?php has_custom_color(); ?>><b><?php \esc_html_e( 'It is recommended to run the cleanup using the command line tools.', 'sirsc' ); ?></b><br><?php \esc_html_e( 'However, if you do not have access to wp-cli on your server, you could run the cleanup actions by using the cron tasks, or, if you have a small set of images to cleanup, by using the plugin dialog.', 'sirsc' ); ?></p>

<div class="as-row columns-2 small-gap"<?php has_custom_color(); ?>>
	<label class="label-row">
		<?php \SIRSC\Helper\settings_button_raw_cleanup( $_sirsc_post_types, 'unused' ); ?>
		<span><?php \esc_html_e( 'Cleanup unused files and keep currently registered sizes files', 'sirsc' ); ?></span>
		<?php the_info_icon( 'info_current_cleanup' ); ?>
	</label>
	<?php the_info_text( 'info_current_cleanup', \__( 'This type of cleanup is performed for all the attachments, and it removes any attachment unused file and keeps only the files associated with the currently registered image sizes. This action is also changing the attachment metadata in the database, and it is irreversible.', 'sirsc' ) . '<br><pre class="code sirsc-wpcli">wp sirsc cleanup</pre>' ); ?>

	<label class="label-row">
		<?php \SIRSC\Helper\settings_button_raw_cleanup( $_sirsc_post_types, 'raw' ); ?>
		<?php \esc_html_e( 'Keep only the original/full size files', 'sirsc' ); ?>
		<?php the_info_icon( 'info_raw_cleanup' ); ?>
	</label>
	<?php the_info_text( 'info_raw_cleanup', \__( 'This type of cleanup is performed for all the attachments, and it keeps only the file associated with the original/full size. This action is also changing the attachment metadata in the database, and it is irreversible. After this process is done, you need to regenerate the files for the desired image sizes.', 'sirsc' ) . '<br><pre class="code sirsc-wpcli">wp sirsc rawcleanup</pre>' ); ?>
</div>
