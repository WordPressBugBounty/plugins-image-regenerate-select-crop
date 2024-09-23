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

<div class="as-row as-title a-middle">
	<h2><?php \esc_html_e( 'Apply the settings below for the selected option', 'sirsc' ); ?></h2>
	<div class="last auto"><button type="button" class="button button-primary min" name="sirsc-settings-submit" value="submit" data-sirsc-autosubmit="click"><?php \esc_html_e( 'Save Settings', 'sirsc' ); ?></button></div>
</div>

<p class="small-gap"><?php \esc_html_e( 'The options for which you made some settings are marked with * in the dropdown below.', 'sirsc' ); ?> <?php \esc_html_e( 'When you select a post type the general options will not be editable, only these that can apply to the images attached to a post will be available for updates.', 'sirsc' ); ?></p>

<div class="as-row columns-2 small-gap a-middle" <?php has_custom_color(); ?>>
	<?php
	if ( ! empty( $post_types ) ) {
		$ptypes = [];
		$has    = ( ! empty( $default_plugin_settings ) ) ? '* ' : '';
		?>
		<div>
			<select name="sirsc[post_types]" id="sirsc_post_type">
				<option value=""><?php echo \esc_html( $has . \esc_html__( 'General settings (used as default for all images)', 'sirsc' ) ); ?></option>
				<?php
				foreach ( $post_types as $pt => $obj ) {
					array_push( $ptypes, $pt );
					$is_sel = ( $_sirsc_post_types === $pt ) ? 1 : 0;
					$extra  = ( ! empty( $obj->_builtin ) ) ? '' : ' (custom post type)';
					$pt_s   = \maybe_unserialize( \get_option( 'sirsc_settings_' . $pt ) );
					$has    = ( ! empty( $pt_s ) ) ? '* ' : '';
					?>
					<option value="<?php echo \esc_attr( $pt ); ?>"<?php \selected( 1, $is_sel ); ?>><?php echo \esc_html( $has . \esc_html__( 'Settings for images attached to a ', 'sirsc' ) . ' ' . $pt . $extra ); ?></option>
					<?php
				}
				?>
			</select>
		</div>
		<?php
		\update_option( 'sirsc_types_options', $ptypes );
	}
	?>
	<label class="self-center" for="sirsc_post_type"><?php \esc_html_e( 'Select the general settings that applies to all, or only one of the post types if necessary.', 'sirsc' ); ?></label>
</div>
