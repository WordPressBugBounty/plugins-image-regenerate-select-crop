<?php
/**
 * Images SEO extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

?>
<div id="sirsc-is-rename-wrap" class="as-row">
	<div class="as-box bg-secondary small">
		<div class="label-row as-title">
			<h2><?php esc_html_e( 'Rename', 'sirsc' ); ?></h2>
		</div>

		<p><?php esc_html_e( 'Change the title below to rename the attachment file, and the generated sub-sizes.', 'sirsc' ); ?></p>

		<div class="label-row as-title">
			<input type="text" name="sirsc_imgseo-renamefile-title" id="sirsc_imgseo-renamefile-title" value="<?php echo esc_attr( $post->post_title ); ?>">

			<button type="submit" class="sirsc-button-icon button-primary has-icon tiny" onclick="sirscToggleProcesing( 'sirsc-is-rename-wrap' );" title="<?php esc_attr_e( 'Rename', 'sirsc' ); ?>"><span class="dashicons dashicons-image-rotate-right"></span></button>
		</div>
	</div>

	<div class="as-box bg-secondary">
		<div class="label-row as-title">
			<h2><?php esc_html_e( 'Attachment image', 'sirsc' ); ?></h2>
		</div>

		<hr>

		<ul>
			<?php
			$atts = self::get_attachments_by_id( $id );
			if ( ! empty( $atts ) ) {
				foreach ( $atts as $att ) {
					?>
					<li class="label-row">
						- <?php esc_html_e( 'Go to', 'sirsc' ); ?>
						<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $att['id'] . '&action=edit' ) ); ?>"><em><?php echo esc_attr( $att['id'] ); ?></em></a>
						| <?php echo esc_html( $att['type'] ); ?>
						| <b><?php echo esc_html( $att['filename'] ); ?></b>
					</li>
					<?php
				}
			}
			?>

			<li class="label-row">
				- <?php esc_html_e( 'Go to', 'sirsc' ); ?>
				<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ); ?>"><em><?php echo esc_attr( $post->post_title ); ?></em></a>
			</li>
		</ul>

		<?php self::maybe_rename_form_execute(); ?>
	</div>
</div>
