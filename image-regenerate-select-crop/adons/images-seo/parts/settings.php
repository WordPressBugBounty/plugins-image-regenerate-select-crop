<?php
/**
 * Images SEO extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

$dis = empty( $settings['override_filename'] ) ? 'disabled="disabled"' : '';
?>
<div>
	<p>
		<?php esc_html_e( 'The SEO rename process (on upload, manual rename, bulk rename) uses the current settings (does not apply retroactively), and overrides the filenames and attributes in the database.', 'sirsc' ); ?>
		<?php esc_html_e( 'Enable the options you want to be used for images processing.', 'sirsc' ); ?>
	</p>
</div>

<div class="as-row">
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_filename">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_filename]" id="_sirsc_imgseo_settings_override_filename" <?php checked( true, $settings['override_filename'] ); ?>>
			<b><?php esc_html_e( 'rename the files', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Rename the attachment files and the generated sub-sizes.', 'sirsc' ); ?></p>
	</div>

	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_track_initial">
			<input type="checkbox" name="_sirsc_imgseo_settings[track_initial]" id="_sirsc_imgseo_settings_track_initial" <?php checked( true, $settings['track_initial'] ); ?> <?php echo $dis; // phpcs:ignore ?>>
			<b><?php esc_html_e( 'track initial filename', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Keep a record of the initial filename if the file is renamed.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_title">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_title]" id="_sirsc_imgseo_settings_override_title" <?php checked( true, $settings['override_title'] ); ?>>
			<b><?php esc_html_e( 'override the title', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Override the attachment title with the inherited title.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_alt">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_alt]" id="_sirsc_imgseo_settings_override_alt" <?php checked( true, $settings['override_alt'] ); ?>>
			<b><?php esc_html_e( 'override the alternative text', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Override the attachment alternative text with the inherited title.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_caption">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_caption]" id="_sirsc_imgseo_settings_override_caption" <?php checked( true, $settings['override_alt'] ); ?>>
			<b><?php esc_html_e( 'override the caption', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Override the attachment caption (used by the gallery and image blocks) with the inherited title.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_permalink">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_permalink]" id="_sirsc_imgseo_settings_override_permalink" <?php checked( true, $settings['override_permalink'] ); ?>>
			<b><?php esc_html_e( 'override the permalink', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Override the attachment permalink with the inherited title.', 'sirsc' ); ?></p>
	</div>
</div>

<div class="as-row">
	<div class="as-box bg-secondary">
		<h2><?php esc_html_e( 'Show the rename button', 'sirsc' ); ?></h2>
		<p><?php esc_html_e( 'When you edit the selected post types, you will see the rename button in the sidebar.', 'sirsc' ); ?></p>

		<div class="as-row columns-2">
			<?php
			if ( ! empty( $types ) ) {
				foreach ( $types as $ptype => $name ) {
					if ( 'product_variation' === $ptype ) {
						continue;
					}
					?>
					<label class="label-row" for="_sirsc_imgseo_settings_types_<?php echo esc_attr( $ptype ); ?>">
						<input type="checkbox" name="_sirsc_imgseo_settings[types][<?php echo esc_attr( $ptype ); ?>]" id="_sirsc_imgseo_settings_types_<?php echo esc_attr( $ptype ); ?>" <?php checked( true, in_array( $ptype, $settings['types'], true ) ); ?>>
						<?php echo esc_html( $name ); ?>
					</label>
					<?php
				}
			}
			?>
		</div>
	</div>

	<div class="as-box bg-secondary">
		<h2><?php esc_html_e( 'Rename on upload', 'sirsc' ); ?></h2>
		<p><?php esc_html_e( 'Attempt to automatically rename the files when uploaded to the selected post types.', 'sirsc' ); ?></p>

		<div class="as-row columns-2">
			<?php
			unset( $types['attachment'] );
			foreach ( $types as $ptype => $name ) {
				?>
				<label class="label-row" for="_sirsc_imgseo_settings_upload_<?php echo esc_attr( $ptype ); ?>">
					<input type="checkbox" name="_sirsc_imgseo_settings[upload][<?php echo esc_attr( $ptype ); ?>]" id="_sirsc_imgseo_settings_upload_<?php echo esc_attr( $ptype ); ?>" <?php checked( true, in_array( $ptype, $settings['upload'], true ) ); ?>>
					<?php echo esc_html( $name ); ?>
				</label>
				<?php
			}
			?>
		</div>
	</div>

	<div class="as-box bg-secondary">
		<h2><?php esc_html_e( 'Bulk rename for types', 'sirsc' ); ?></h2>
		<p><?php esc_html_e( 'Select the post types to make these available for the bulk rename process.', 'sirsc' ); ?></p>

		<div class="as-row columns-2">
			<?php
			foreach ( $types as $ptype => $name ) {
				?>
				<label class="label-row" for="_sirsc_imgseo_settings_bulk_<?php echo esc_attr( $ptype ); ?>">
					<input type="checkbox" name="_sirsc_imgseo_settings[bulk][<?php echo esc_attr( $ptype ); ?>]" id="_sirsc_imgseo_settings_bulk_<?php echo esc_attr( $ptype ); ?>" <?php checked( true, in_array( $ptype, $settings['bulk'], true ) ); ?>>
					<?php echo esc_html( $name ); ?>
				</label>
				<?php
			}
			?>
		</div>
	</div>
</div>

<div class="label-row">
	<?php
	submit_button( __( 'Save Settings', 'sirsc' ), 'primary', '', false, [
		'onclick' => 'sirscToggleProcesing( \'js-sirsc_imgseo-frm-settings\' );',
	] );
	?>
</div>
