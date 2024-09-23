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

<div class="as-row">
	<div class="label-row">
		<?php the_info_icon( 'info_developer_mode' ); ?>
		<h2><?php \esc_html_e( 'Option to Enable Placeholders', 'sirsc' ); ?></h2>
	</div>

	<div class="as-row auto last">
		<label class="label-row">
			<input type="radio" name="sirsc[placeholders]" id="sirsc_placeholders_none" value=""
				<?php \checked( true, ( empty( $settings['placeholders'] ) ) ); ?>
				<?php setting_is_readonly( 'placeholders' ); ?> data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'no placeholder', 'sirsc' ); ?>
		</label>
		<label class="label-row">
			<input type="radio" name="sirsc[placeholders]"
				id="sirsc_placeholders_force_global" value="force_global"
				<?php \checked( true, ( ! empty( $settings['placeholders']['force_global'] ) ) ); ?>
				<?php setting_is_readonly( 'placeholders' ); ?>data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'force global', 'sirsc' ); ?>
		</label>
		<label class="label-row">
			<input type="radio" name="sirsc[placeholders]" id="sirsc_placeholders_only_missing"
				value="only_missing" <?php \checked( true, ( ! empty( $settings['placeholders']['only_missing'] ) ) ); ?> <?php setting_is_readonly( 'placeholders' ); ?> data-sirsc-autosubmit="change">
			<?php \esc_html_e( 'only missing images', 'sirsc' ); ?>
		</label>
	</div>
</div>

<p class="small-gap">
	<?php \esc_html_e( 'This option allows you to display placeholders for the front-side images called programmatically (the images that are not embedded in the content with their src, but exposed using WordPress native functions). If there is no placeholder set, then the WordPress default behavior would be to display the full-size image instead of a missing image size, hence your pages might load slower, and when using grids, the items would not look even.', 'sirsc' ); ?>
	<?php
	if ( ! \wp_is_writable( SIRSC_PLACEHOLDER_DIR ) ) {
		\esc_html_e( 'This feature might not work properly, your placeholders folder is not writtable.', 'sirsc' );
	}
	?>
</p>
<?php the_info_text( 'info_developer_mode', \__( 'If you activate the "force global" option, all the images on the front-side that are related to posts will be replaced with the placeholders that mention the image size required. This is useful for debugging, to quickly identify the image sizes used for each layout and perhaps to help you regenerate the mission ones or decide what to keep or what to remove.', 'sirsc' ) . '<hr>' . \__( 'If you activate the "only missing images" option, all the programmatically called images on the front-side that are related to posts and do not have the requested image size generated will be replaced with the placeholders that mention the image size required. This is useful for showing smaller images instead of the full-size images (as WordPress does by default), hence for speeding up the pages loading.', 'sirsc' ) ); ?>
