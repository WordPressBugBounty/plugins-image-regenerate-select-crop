<?php
/**
 * Import/Export extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

?>

<form action="" method="post" autocomplete="off" id="js-sirsc_adon_import_frm">
	<?php wp_nonce_field( '_sirsc_adon_export_settings_action', '_sirsc_adon_export_settings_nonce' ); ?>

	<?php \SIRSC\admin\the_info_text( 'info_export_import', __( 'Please note that the import/export of the settings is in relation with the image sizes that are found on the instance, through the plugins that are activated and also the theme settings. You might need to partially adjust these manually after an import.', 'sirsc' ) ); ?>

	<div class="as-row">
		<div class="as-box bg-secondary">
			<div class="label-row as-title">
				<h2><?php esc_html_e( 'Export Settings', 'sirsc' ); ?> - JSON</h2>
				<?php \SIRSC\admin\the_info_icon( 'info_export_import' ); ?>
			</div>
			<p class="small-gap"><?php esc_html_e( 'Copy the settings and import these into another instance.', 'sirsc' ); ?></p>
			<textarea rows="16" class="code"><?php echo esc_html( $export ); ?></textarea>
		</div>

		<div class="as-box bg-secondary">
			<div class="label-row as-title">
				<h2><?php esc_html_e( 'Import Settings', 'sirsc' ); ?>  - JSON</h2>
				<?php \SIRSC\admin\the_info_icon( 'info_export_import' ); ?>
				<button type="submit" class="button button-primary" name="submit" value="submit" onclick="sirscToggleAdon( 'sirsc-import-settings' );"><?php esc_html_e( 'Import Settings', 'sirsc' ); ?>
				</button>
			</div>
			<p class="small-gap"><?php esc_html_e( 'Paste here the settings and import these into the current instance.', 'sirsc' ); ?></p>
			<textarea name="sirsc-import-settings" rows="16" class="code"></textarea>
		</div>

		<?php if ( ! empty( $snippet ) ) : ?>
			<div class="as-box bg-secondary">
				<div class="label-row as-title">
					<h2><?php esc_html_e( 'Registered Image Sizes', 'sirsc' ); ?></h2>
				</div>
				<p class="small-gap"><?php esc_html_e( 'If you deactivate the plugin but still want to keep the image sizes you registered with this plugin, you can copy the snippet in your theme functions.php file or in a plugin.', 'sirsc' ); ?></p>
				<textarea rows="14" class="code"><?php echo esc_html( $snippet ); ?></textarea>
			</div>
		<?php endif; ?>
	</div>
</form>
