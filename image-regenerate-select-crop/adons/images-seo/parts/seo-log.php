<?php
/**
 * Images SEO extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

?>
<div class="as-box bg-secondary">
	<div class="label-row as-title">
		<button class="button button-secondary has-icon tiny" onclick="refreshLog( 'seo-images' )"><span class="dashicons dashicons-update-alt"></span></button>
		<h2><?php esc_html_e( 'Rename log', 'sirsc' ); ?></h2>
		<button class="button button-neutral" onclick="resetLog( 'seo-images' )"><?php esc_html_e( 'Reset log', 'sirsc' ); ?></button>
	</div>

	<p>
		<?php esc_html_e( 'The SEO Images rename log can be seen below, the most recent events are shown at the bottom of the list. This log will reset if you run the wp-cli commands.', 'sirsc' ); ?>
		<?php \SIRSC\Admin\the_info_icon( 'info_seoreplace' ); ?>
	</p>
	<?php \SIRSC\Admin\the_info_text( 'info_seoreplace', esc_html__( 'Run the following command in your terminal to see more details about this.', 'sirsc' ) . ' <pre class="code sirsc-wpcli">wp help sirsc seorename</pre>' ); ?>

	<div id="sirsc-log-seo-images" class="code">
		<?php echo wp_kses_post( nl2br( \SIRSC\Debug\log_read( 'seo-images' ) ) ); ?>
	</div>

	<p>
		<?php esc_html_e( 'If you cannot run wp-cli commands, you can use the data from this log to make your own find-replace script that replaces the old strings with new strings in your database, if you have hardcoded URLs for the images. Each line represents a string to be replaced, the first part before the comma is the old reference, the second part is the new reference.', 'sirsc' ); ?>
	</p>
</div>
