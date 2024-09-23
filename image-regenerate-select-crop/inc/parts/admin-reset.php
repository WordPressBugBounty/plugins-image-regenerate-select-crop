<?php
/**
 * SIRSC admin functionality.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Admin;

?>

<div class="as-row a-middle">
	<div>
		<p><?php \esc_html_e( 'This plugin provides the option to select image sizes that will be excluded from the generation of the new images. By default, all image sizes defined in the system will be allowed (these are programmatically registered by the themes and plugins you activate in your site, without you even knowing about these). You can set up a global configuration, or more specific configuration for all images attached to a particular post type. If no particular settings are made for a post type, then the default general settings will be used.', 'sirsc' ); ?></p>
	</div>
	<div class="small">
		<?php \esc_html_e( 'Click the button to reset all settings for this plugin. The reset will not remove the custom registered image sizes, but only the settings.', 'sirsc' ); ?></div>
	<div class="auto last">
		<button type="button" class="button button-primary has-icon" name="sirsc-settings-reset" value="submit" data-sirsc-autosubmit="click" title="<?php \esc_attr_e( 'Reset', 'sirsc' ); ?>">
			<span class="dashicons as-icon dashicons-trash"></span>
			<span><?php \esc_html_e( 'Reset', 'sirsc' ); ?></span>
		</button>
	</div>
</div>
