<?php
/**
 * Uploads inspector extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

global $wpdb;
?>

<div class="as-row columns-3">
	<?php
	$query = $wpdb->prepare( ' SELECT mimetype, COUNT(id) as total_files FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s GROUP BY mimetype ', 'file' ); // phpcs:ignore
	$items = $wpdb->get_results( $query ); // phpcs:ignore
	if ( ! empty( $items ) ) {
		?>
		<div>
			<h3 class="heading"><?php esc_html_e( 'MIME type', 'sirsc' ); ?></h3>
			<hr>
			<ul class="files-info-wrap small">
				<?php
				foreach ( $items as $item ) {
					$max_page = ceil( (int) $item->total_files / $perpag );
					$mimetype = empty( $item->mimetype ) ? '~' . __( 'unknown', 'sirsc' ) . '~' : $item->mimetype;
					$v_mtype  = empty( $item->mimetype ) ? 'na' : ltrim( strstr( $item->mimetype, '/', false ), '/' );
					?>
					<li class="file-info">
						<?php if ( empty( $item->mimetype ) ) : ?>
							<em>~<?php esc_html_e( 'unknown', 'sirsc' ); ?>~</em>
						<?php else : ?>
							<span><?php echo esc_html( $item->mimetype ); ?></span>
						<?php endif; ?>
						<?php self::file_info_button( 'js-sirsc-adon-improf-list-mime-' . $v_mtype . '-0', $max_page, '', $v_mtype, __( 'MIME type', 'sirsc' ) . ': ' . $mimetype, $item->total_files ); ?>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}

	$query = $wpdb->prepare( ' SELECT size_name, COUNT(id) as total_files FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s and mimetype like %s  GROUP BY size_name', 'file', 'image/%' ); // phpcs:ignore
	$items = $wpdb->get_results( $query ); // phpcs:ignore
	if ( ! empty( $items ) ) {
		?>
		<div>
			<h3 class="heading"><?php esc_html_e( 'Images sizes', 'sirsc' ); ?></h3>
			<hr>
			<ul class="files-info-wrap small">
				<?php
				foreach ( $items as $item ) {
					$max_page  = ceil( (int) $item->total_files / $perpag );
					$size_name = empty( $item->size_name ) ? '~' . __( 'unknown', 'sirsc' ) . '~' : $item->size_name;
					$v_sname   = empty( $item->size_name ) ? 'na' : $item->size_name;
					?>
					<li class="file-info">
						<?php if ( empty( $item->size_name ) ) : ?>
							<em>~<?php esc_html_e( 'unknown', 'sirsc' ); ?>~</em>
						<?php else : ?>
							<span><?php echo esc_html( $item->size_name ); ?></span>
						<?php endif; ?>
						<?php self::file_info_button( 'js-sirsc-adon-improf-list-size-' . $v_sname . '-0', $max_page, $v_sname, '', __( 'Images sizes', 'sirsc' ) . ': ' . $size_name, $item->total_files ); ?>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}

	$query = $wpdb->prepare( ' SELECT size_name, COUNT(id) as total_files FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s AND valid = 1 and mimetype like %s  GROUP BY size_name', 'file', 'image/%' ); // phpcs:ignore
	$items = $wpdb->get_results( $query ); // phpcs:ignore
	if ( ! empty( $items ) ) {
		?>
		<div>
			<h3 class="heading"><?php esc_html_e( 'Valid images', 'sirsc' ); ?></h3>
			<hr>
			<ul class="files-info-wrap small">
				<?php
				foreach ( $items as $item ) {
					$max_page  = ceil( (int) $item->total_files / $perpag );
					$size_name = empty( $item->size_name ) ? '~' . __( 'unknown', 'sirsc' ) . '~' : $item->size_name;
					$v_sname   = empty( $item->size_name ) ? 'na' : $item->size_name;
					?>
					<li class="file-info">
						<?php if ( empty( $item->size_name ) ) : ?>
							<em>~<?php esc_html_e( 'unknown', 'sirsc' ); ?>~</em>
						<?php else : ?>
							<span><?php echo esc_html( $item->size_name ); ?></span>
						<?php endif; ?>
						<?php self::file_info_button( 'js-sirsc-adon-improf-list-size-' . $v_sname . '-1', $max_page, $v_sname, '', __( 'Valid images', 'sirsc' ) . ': ' . $size_name, $item->total_files ); ?>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}
	?>
</div>
