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
	<div class="label-row">
		<span class="dashicons dashicons-feedback as-icon"></span>

		<h2><?php esc_html_e( 'What Does Images SEO Do?', 'sirsc' ); ?></h2>
	</div>

	<p><?php esc_html_e( 'You can enable/disable any of the actions that the SEO rename extension is providing. The ones enabled will be used for processing images on upload, on bulk rename, and on manual rename too.', 'sirsc' ); ?></p>
</div>

<div class="as-row">
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_filename">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_filename]" id="_sirsc_imgseo_settings_override_filename" <?php checked( true, $settings['override_filename'] ); ?>>
			<b><?php esc_html_e( 'Rename Files', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Enable this to rename the attachment files (also the image sizes generated).', 'sirsc' ); ?></p>
	</div>

	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_track_initial">
			<input type="checkbox" name="_sirsc_imgseo_settings[track_initial]" id="_sirsc_imgseo_settings_track_initial" <?php checked( true, $settings['track_initial'] ); ?> <?php echo $dis; // phpcs:ignore ?>>
			<b><?php esc_html_e( 'Track Initial File', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Enable this to keep a record of the initial filename if the file is renamed.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_title">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_title]" id="_sirsc_imgseo_settings_override_title" <?php checked( true, $settings['override_title'] ); ?>>
			<b><?php esc_html_e( 'Override Title', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Enable this to override the attachment title with the inherited title.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_alt">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_alt]" id="_sirsc_imgseo_settings_override_alt" <?php checked( true, $settings['override_alt'] ); ?>>
			<b><?php esc_html_e( 'Override Alternative', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Enable this to override the attachment alternative text with the inherited title.', 'sirsc' ); ?></p>
	</div>
	<div>
		<label class="label-row sirsc-label" for="_sirsc_imgseo_settings_override_permalink">
			<input type="checkbox" name="_sirsc_imgseo_settings[override_permalink]" id="_sirsc_imgseo_settings_override_permalink" <?php checked( true, $settings['override_permalink'] ); ?>>
			<b><?php esc_html_e( 'Override Permalink', 'sirsc' ); ?></b>
		</label>
		<hr>
		<p><?php esc_html_e( 'Enable this to override the attachment permalink with the inherited title.', 'sirsc' ); ?></p>
	</div>
</div>

<div class="as-row">
	<div class="as-box bg-secondary">
		<div class="label-row">
			<span class="dashicons as-icon dashicons-feedback"></span>
			<h2><?php esc_html_e( 'Show rename button', 'sirsc' ); ?></h2>
		</div>

		<p><?php esc_html_e( 'For the selected post types there will be shown a meta box with the button to rename the associated files.', 'sirsc' ); ?></p>

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
		<div class="label-row">
			<span class="dashicons as-icon dashicons-upload"></span>
			<h2><?php esc_html_e( 'Rename images on upload', 'sirsc' ); ?></h2>
		</div>

		<p><?php esc_html_e( 'Attempt to automatically rename the files on upload to these post types (these post parent types).', 'sirsc' ); ?></p>

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
		<div class="label-row">
			<span class="dashicons as-icon dashicons-format-gallery"></span>
			<h2><?php esc_html_e( 'Bulk rename images for types', 'sirsc' ); ?></h2>
		</div>

		<p><?php esc_html_e( 'These will be the post types that will be available to select in the bulk rename process.', 'sirsc' ); ?></p>

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
	<p><?php esc_html_e( 'Please note that any of the rename process options (on upload, manual rename, bulk rename) will take into account the currently enabled settings, this will not apply retroactively.', 'sirsc' ); ?>
	</p>
</div>
