<?php
/**
 * Images SEO extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

?>
<p>
	<?php
	echo wp_kses_post( __( 'The bulk rename process is targeting images set as <b>featured image</b> (for all post types selected that support the featured image feature) or attached as <b>media</b> (uploaded to that posts as children).', 'sirsc' ) );

	if ( in_array( 'product', $types, true ) ) {
		echo wp_kses_post( __( 'For products, the rename will include also the <b>gallery images</b>.', 'sirsc' ) );
	}

	esc_html_e( 'Please note that any of the rename process options (on upload, manual rename, bulk rename) will override the attachment attributes based on the images SEO settings you made.', 'sirsc' );
	?>
</p>

<div class="as-row">
	<div class="as-box bg-secondary small">
		<div class="label-row as-title">
			<span class="dashicons as-icon dashicons-format-gallery"></span>
			<h2><?php esc_html_e( 'Bulk rename images', 'sirsc' ); ?></h2>
		</div>

		<p>
			<?php esc_html_e( 'If you want to start the bulk rename of images, you have to select at least one post type, then click the bulk rename button.', 'sirsc' ); ?>
		</p>

		<div class="as-row columns-2">
			<?php
			foreach ( $types as $ptype ) {
				$type_on = ( ! empty( $types[ $ptype ] ) ) ? 'on' : '';
				?>
				<label class="label-row" class="sirsc_imgseo-label-<?php echo esc_attr( $ptype ); ?>" for="_sirsc_imgseo_bulk_update_<?php echo esc_attr( $ptype ); ?>">
					<input type="checkbox" name="_sirsc_imgseo_bulk_update[<?php echo esc_attr( $ptype ); ?>]" id="_sirsc_imgseo_bulk_update_<?php echo esc_attr( $ptype ); ?>" value="<?php echo esc_attr( $ptype ); ?>" <?php checked( 'on', $type_on ); ?>>
					<?php echo esc_html( self::$post_types[ $ptype ] ?? $ptype ); ?>
				</label>
				<?php
			}
			?>
		</div>

		<?php \SIRSC\Iterator\button_display( 'sirsc-is-rename' ); ?>
	</div>
	<div class="as-box bg-secondary" id="sirsc-listing-wrap">
		<?php self::maybe_rename_form_execute(); ?>
		<?php self::maybe_bulk_rename_form_execute(); ?>
	</div>
</div>
