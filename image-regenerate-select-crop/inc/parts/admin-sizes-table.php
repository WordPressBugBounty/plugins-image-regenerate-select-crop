<?php
/**
 * SIRSC admin functionality.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Admin;

the_info_text( 'info_global_ignore', \__( 'This option allows you to exclude globally from the application some of the image sizes that are registered through various plugins and themes options, that you perhaps do not need at all in your application (these are just stored in your folders and database but not actually used/visible on the front-end).', 'sirsc' ) . '<hr>' . \__( 'By excluding these, the unnecessary image sizes will not be generated at all.', 'sirsc' ) );

the_info_text( 'info_default_quality', \__( 'The quality option is allowing you to control the quality of the images that are generated for each of the image sizes, starting from the quality of the image you upload. This can be useful for performance.', 'sirsc' ) . '<hr><b>' . \__( 'However, please be careful not to change the quality of the full image or the quality of the image size that you set as the forced original.', 'sirsc' ) . '</b><hr>' . \__( 'Setting a lower quality is recommended for smaller images sizes, that are generated from the full/original file.', 'sirsc' ) );

the_info_text( 'info_force_original', \__( 'This option means that when uploading an image, the original image will be replaced completely by the image size you select (the image generated, scaled or cropped to a specific width and height will become the full size for that image going further).', 'sirsc' ) . '<hr>' . \__( 'This can be very useful if you do not use the original image in any of the layouts at the full size, and this might save some storage space.', 'sirsc' ) . '<hr>' . \__( 'Leave "nothing selected" to keep the full/original image as the file you upload (default WordPress behavior).', 'sirsc' ) );

the_info_text( 'info_exclude', \__( 'This option allows you to hide from the "Image Regenerate & Select Crop Settings" lightbox the details and options for the selected image sizes (when you or other admins are checking the image details, the hidden image sizes will not be shown).', 'sirsc' ) . '<hr>' . \__( 'This is useful when you want to restrict from other users the functionality of crop or resize for particular image sizes, or to just hide the image sizes you added to global ignore.', 'sirsc' ) . '<hr>' . \__( 'If you set the image size as ignored or unavailable, this will not be listed in the media screen when the dropdown of image sizes will be shown.', 'sirsc' ) );

the_info_text( 'info_default_crop', \__( 'This option allows you to set a default crop position for the images generated for a particular image size. This option will be applied when you chose to regenerate an individual image or all of these, and also when a new image is uploaded.', 'sirsc' ) );

the_info_text( 'info_clean_up', \__( 'This option allows you to clean up all the image generated for a particular image size you already have in the application, and that you do not use or do not want to use anymore on the front-end.', 'sirsc' ) . '<hr><b>' . \__( 'Please be careful, once you click to remove the images for a selected image size, the action is irreversible, the images generated up this point will be deleted from your folders and database records.', 'sirsc' ) . '</b><hr>' . \__( 'You can regenerate these later if you click the Regenerate button.', 'sirsc' ) );

the_info_text( 'info_regenerate', \__( 'This option allows you to regenerate the images for the selected image size.', 'sirsc' ) . '<hr><b>' . \__( 'Please be careful, once you click the button to regenerate the selected image size, the action is irreversible, the images already generated will be overwritten.', 'sirsc' ) . '</b><br><pre class="code sirsc-wpcli">wp sirsc regenerate</pre>' );

?>

<hr>

<div class="as-row auto a-middle">
	<div class="label-row as-title">
		<h2><?php \esc_html_e( 'Image Sizes Options', 'sirsc' ); ?></h2>
	</div>
	<label class="label-row last">
		<?php the_info_icon( 'info_global_ignore' ); ?>
		<?php \esc_html_e( 'ignore image size', 'sirsc' ); ?>
	</label>
	<label class="label-row">
		<?php the_info_icon( 'info_default_quality' ); ?>
		<?php \esc_html_e( 'image quality', 'sirsc' ); ?>
	</label>
	<label class="label-row">
		<?php the_info_icon( 'info_force_original' ); ?>
		<?php \esc_html_e( 'force original option', 'sirsc' ); ?>
	</label>
	<label class="label-row">
		<?php the_info_icon( 'info_exclude' ); ?>
		<?php \esc_html_e( 'hide image preview from info view', 'sirsc' ); ?>
	</label>
	<label class="label-row">
		<?php the_info_icon( 'info_default_crop' ); ?>
		<?php \esc_html_e( 'default crop position', 'sirsc' ); ?>
	</label>
	<label class="label-row">
		<?php the_info_icon( 'info_clean_up' ); ?>
		<?php \esc_html_e( 'cleanup sub-sizes', 'sirsc' ); ?>
	</label>
	<label class="label-row">
		<?php the_info_icon( 'info_regenerate' ); ?>
		<?php \esc_html_e( 'regenerate sub-sizes', 'sirsc' ); ?>
	</label>
</div>

<table class="wp-list-table striped widefat fixed pages"<?php has_custom_color(); ?>>
	<thead>
		<tr>
			<td width="10%">
				<h3><?php \esc_html_e( 'Ignore', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'disable sub-size', 'sirsc' ); ?></div>
			</td>
			<td>
				<h3><?php \esc_html_e( 'Image Size Info', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'name, scale/crop, dimension', 'sirsc' ); ?></div>
			</td>
			<td width="10%" class="a-right">
				<h3><?php \esc_html_e( 'Quality', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'file compression', 'sirsc' ); ?></div>
			</td>
			<td width="15%">
				<h3><?php \esc_html_e( 'Force original', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'use as original', 'sirsc' ); ?></div>
			</td>
			<td width="12%">
				<h3><?php \esc_html_e( 'Hide Preview', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'hide from views', 'sirsc' ); ?></div>
			</td>
			<td width="12%">
				<h3><?php \esc_html_e( 'Default Crop', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'initial position', 'sirsc' ); ?></div>
			</td>
			<td width="8%" class="a-right">
				<h3><?php \esc_html_e( 'Cleanup', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'removal', 'sirsc' ); ?></div>
			</td>
			<td width="10%" class="a-right">
				<h3><?php \esc_html_e( 'Regenerate', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'scale/crop', 'sirsc' ); ?></div>
			</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td colspan="3" class="a-right">
				<a onclick="sirscResetAllQuality('<?php echo (int) \SIRSC::DEFAULT_QUALITY; ?>')" class="row-hint" tabindex="0"><?php \esc_html_e( 'reset to default quality', 'sirsc' ); ?><span class="dashicons dashicons-no"></span></a>
			</td>
			<td colspan="5">
				<label class="label-row">
					<input type="radio" name="sirsc[force_original]" id="sirsc_force_original_0" value="0" <?php \checked( 1, 1 ); ?> data-sirsc-autosubmit="change" onchange="sirscToggleRowClass( 'sirsc-settings-for-0', 'row-original' );">
					<?php \esc_html_e( 'nothing selected', 'sirsc' ); ?>
				</label>
			</td>
		</tr>

		<?php
		$all_sizes = \SIRSC::get_all_image_sizes();
		if ( ! empty( $all_sizes ) ) :
			foreach ( $all_sizes as $k => $v ) :
				$use  = get_usable_info( $k, $settings );
				$clon = '';
				if ( ! substr_count( $use['line_class'], '_sirsc_included' ) ) {
					$clon .= ' row-hide';
				}
				if ( substr_count( $use['line_class'], '_sirsc_ignored' ) ) {
					$clon .= ' row-ignore';
				}
				if ( substr_count( $use['line_class'], '_sirsc_force_original' ) ) {
					$clon .= ' row-original';
				}

				$tr_id = 'sirsc-settings-for-' . \esc_attr( $k );
				?>
				<tr id="<?php echo \esc_attr( $tr_id ); ?>"
					class="<?php echo \esc_attr( $clon ); ?>">
					<td class="option-ignore" data-colname="<?php \esc_attr_e( 'Ignore', 'sirsc' ); ?>">
						<label class="label-row as-title">
							<input type="checkbox" name="sirsc[global_ignore][<?php echo \esc_attr( $k ); ?>]" id="sirsc_global_ignore_<?php echo \esc_attr( $k ); ?>" value="<?php echo \esc_attr( $k ); ?>" <?php \checked( 1, $use['is_ignored'] ); ?> data-sirsc-autosubmit="change" onchange="sirscToggleRowClass( '<?php echo \esc_attr( $tr_id ); ?>', 'row-ignore' );" />
							<?php \esc_html_e( 'global ignore', 'sirsc' ); ?>
						</label>
					</td>
					<td class="option-main" data-colname="<?php \esc_attr_e( 'Image Size Info', 'sirsc' ); ?>">
						<b class="label-row as-title"><?php echo \esc_html( $k ); ?></b>
						<label class="label-row space-between">
							<p><?php echo \wp_kses_post( \SIRSC\Helper\size_to_text( $v ) ); ?></p>
							<?php
							if ( ! empty( $settings['placeholders'] ) ) {
								\SIRSC\Placeholder\the_placeholder_preview( $k );
							}
							?>
						</label>
					</td>
					<td class="option-quality a-right" data-colname="<?php \esc_attr_e( 'Quality', 'sirsc' ); ?>">
						<div class="sirsc-size-quality-wrap">
							<label class="label-row">
								<input type="number" name="sirsc[default_quality][<?php echo \esc_attr( $k ); ?>]" id="sirsc_default_quality_<?php echo \esc_attr( $k ); ?>" max="100" min="1" size="2" value="<?php echo (int) $use['quality']; ?>" data-sirsc-autosubmit="change" onchange="alert('<?php \esc_attr_e( 'Please be aware that your are changing the quality of the images going further for this images size!', 'sirsc' ); ?>');"
									class="sirsc-size-quality">
								<?php \esc_html_e( 'quality', 'sirsc' ); ?>
							</label>
						</div>
					</td>
					<td class="option-original" data-colname="<?php \esc_attr_e( 'Force original', 'sirsc' ); ?>">
						<label class="label-row as-title">
							<input type="radio" name="sirsc[force_original]" id="sirsc_force_original_<?php echo \esc_attr( $k ); ?>" value="<?php echo \esc_attr( $k ); ?>" <?php \checked( 1, $use['is_forced'] ); ?> data-sirsc-autosubmit="change" onchange="sirscToggleRowClass( '<?php echo \esc_attr( $tr_id ); ?>', 'row-original' );">
							<?php \esc_html_e( 'force original', 'sirsc' ); ?>
						</label>
					</td>
					<td class="option-exclude" data-colname="<?php \esc_attr_e( 'Hide Preview', 'sirsc' ); ?>">
						<label class="label-row as-title">
							<input type="checkbox" name="sirsc[exclude_size][<?php echo \esc_attr( $k ); ?>]" id="sirsrc_exclude_size_<?php echo \esc_attr( $k ); ?>" value="<?php echo \esc_attr( $k ); ?>" <?php \checked( 1, $use['is_checked'] ); ?> data-sirsc-autosubmit="change" onchange="sirscToggleRowClass( '<?php echo \esc_attr( $tr_id ); ?>', 'row-hide' );">
							<?php \esc_html_e( 'hide', 'sirsc' ); ?>
						</label>

						<label class="label-row as-title">
							<input type="checkbox" name="sirsc[unavailable_size][<?php echo \esc_attr( $k ); ?>]" id="sirsrc_unavailable_size_<?php echo \esc_attr( $k ); ?>" value="<?php echo \esc_attr( $k ); ?>" <?php \checked( 1, $use['is_unavailable'] ); ?> data-sirsc-autosubmit="change" />
							<?php \esc_html_e( 'unavailable', 'sirsc' ); ?>
						</label>
					</td>
					<td class="option-crop" data-colname="<?php \esc_attr_e( 'Default Crop', 'sirsc' ); ?>">
						<div class="crop settings">
							<?php
							if ( ! empty( $v['crop'] ) ) {
								echo \SIRSC\Helper\make_generate_images_crop( 0, $k, false, $use['has_crop'] ); // phpcs:ignore
							}
							?>
						</div>
					</td>
					<td class="option-cleanup a-right" data-colname="<?php \esc_attr_e( 'Cleanup', 'sirsc' ); ?>">
						<?php \SIRSC\Helper\settings_button_size_cleanup( $_sirsc_post_types, $k ); ?>
					</td>
					<td class="option-regenerate a-right" data-colname="<?php \esc_attr_e( 'Regenerate', 'sirsc' ); ?>">
						<?php \SIRSC\Helper\settings_button_size_regenerate( $_sirsc_post_types, $k ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
