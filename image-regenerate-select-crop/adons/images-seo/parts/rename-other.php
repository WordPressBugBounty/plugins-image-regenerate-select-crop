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
			<span class="dashicons as-icon dashicons-image-rotate-right"></span>
			<h2><?php esc_html_e( 'Rename images', 'sirsc' ); ?></h2>
		</div>

		<p><?php esc_html_e( 'You can change the title below, then click the button to rename the identifies images associated with this post, and their generated image sizes.', 'sirsc' ); ?></p>

		<div class="label-row">
			<input type="text" name="sirsc_imgseo-renamefile-title" id="sirsc_imgseo-renamefile-title" value="<?php echo esc_attr( $post->post_title ); ?>">

			<button type="submit" class="sirsc-button-icon button-primary has-icon tiny" onclick="sirscToggleProcesing( 'sirsc-is-rename-wrap' );" title="<?php esc_attr_e( 'Rename', 'sirsc' ); ?>"><span class="dashicons dashicons-image-rotate-right"></span></button>
		</div>
	</div>

	<div class="as-box bg-secondary ">
		<div class="label-row as-title">
			<h2><?php esc_html_e( 'Images attached to the post', 'sirsc' ); ?></h2>
		</div>

		<hr>

		<ul>
			<?php
			$atts = self::get_attachments_by_post( $id );
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
				| <?php echo esc_html( $post->post_type ); ?>
			</li>
		</ul>

		<?php self::maybe_rename_form_execute(); ?>
	</div>
</div>
