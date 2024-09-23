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
	<h2><?php \esc_html_e( 'Other Settings', 'sirsc' ); ?></h2>
</div>

<?php
if ( ! empty( $_sirsc_post_types ) ) {
	?>
	<p class="small-gap"<?php has_custom_color(); ?>><?php \esc_html_e( 'These settings are targetting only the post type you selected above.', 'sirsc' ); ?></p>
	<?php
}
?>

<div class="as-row a-middle small-gap"<?php has_custom_color(); ?>>
	<label class="label-row">
		<input type="checkbox" name="sirsc[enable_perfect]" id="sirsc_enable_perfect" <?php \checked( true, $settings['enable_perfect'] ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'generate only perfect fit sizes', 'sirsc' ); ?>
		<?php the_info_icon( 'info_perfect_fit' ); ?>
	</label>
	<?php the_info_text( 'info_perfect_fit', \__( 'This option allows you to generate only images that match exactly the width and height of the crop/resize requirements, when the option is enabled. Otherwise, the script will generate anything possible for smaller images.', 'sirsc' ) ); ?>

	<label class="label-row">
		<input type="checkbox" name="sirsc[enable_upscale]" id="sirsc_enable_upscale" <?php \checked( true, $settings['enable_upscale'] ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'attempt to upscale when generating only perfect fit sizes', 'sirsc' ); ?>
		<?php the_info_icon( 'info_perfect_fit_upscale' ); ?>
	</label>
	<?php the_info_text( 'info_perfect_fit_upscale', \__( 'This option allows you to upscale the images when using the perfect fit option. This allows that images that have at least the original width close to the expected width or the original height close to the expected height (for example, the original image has 800x600 and the crop size 700x700) to be generated from a upscaled image.', 'sirsc' ) ); ?>

	<label class="label-row">
		<input type="checkbox" name="sirsc[regenerate_missing]" id="sirsc_regenerate_missing" <?php checked( true, $settings['regenerate_missing'] ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'regenerate only missing files', 'sirsc' ); ?>
		<?php the_info_icon( 'info_regenerate_missing' ); ?>
	</label>
	<?php the_info_text( 'info_regenerate_missing', \__( 'When using the bulk actions, this option allows you to regenerate only the images that do not exist, without overriding the existing ones.', 'sirsc' ) ); ?>

	<label class="label-row">
		<input type="checkbox" name="sirsc[regenerate_only_featured]" id="sirsc_regenerate_only_featured" <?php \checked( true, $settings['regenerate_only_featured'] ); ?> data-sirsc-autosubmit="change">
		<?php \esc_html_e( 'regenerate/cleanup only featured images', 'sirsc' ); ?>
		<?php the_info_icon( 'info_regenerate_only_featured' ); ?>
	</label>
	<?php the_info_text( 'info_regenerate_only_featured', \__( 'When using the bulk actions, this option allows you to regenerate/cleanup only the images that are set as featured image for any of the posts.', 'sirsc' ) ); ?>
</div>
