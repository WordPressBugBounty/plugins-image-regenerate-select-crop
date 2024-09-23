<?php
/**
 * Uploads inspector extension.
 *
 * @package sirsc
 * @version 8.0.0
 */

// phpcs:disable
$page  = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
$page  = empty( $page ) ? 1 : abs( $page );
$max   = filter_input( INPUT_GET, 'maxpage', FILTER_VALIDATE_INT );
$size  = urlencode( filter_input( INPUT_GET, 'sizename', FILTER_DEFAULT ) );
$mime  = urlencode( filter_input( INPUT_GET, 'mimetype', FILTER_DEFAULT ) );
$valid = filter_input( INPUT_GET, 'valid', FILTER_VALIDATE_INT );
$aid   = filter_input( INPUT_GET, 'aid', FILTER_DEFAULT );
$title = filter_input( INPUT_GET, 'title', FILTER_DEFAULT );
// phpcs:enable

$args = [
	'base'               => '%_%',
	'format'             => '?page=%#%',
	'total'              => $max,
	'current'            => $page,
	'show_all'           => false,
	'end_size'           => 1,
	'mid_size'           => 2,
	'prev_next'          => false,
	'prev_text'          => '&laquo;',
	'next_text'          => '&raquo;',
	'before_page_number' => '<button class="page-item button sirsc-listing-wrap-item" data-parentaid="' . $aid . '">',
	'after_page_number'  => '</button>',
	'add_args'           => false,
];

$pagination = '<div class="pagination">' . paginate_links( $args ) . '</div>';
$pagination = preg_replace( '/\s+/', ' ', $pagination );
$pagination = strip_tags( $pagination, '<span><button>' );
$pagination = str_replace( '<span aria-current="page" class="page-numbers current"><button class="', '<span aria-current="page" class="page-numbers current"><button class="button-primary ', $pagination );
?>

<div class="as-row a-middle pags">
	<h3><?php echo esc_html( $title ); ?></h3>
	<div class="pagination a-middle a-right">
		<span class="pags-numbers">
			<?php
			echo wp_kses_post( sprintf(
				// Translators: %1$s - current page, %2$s - total pages.
				__( 'Page %1$s of %2$s', 'sirsc' ),
				'<b>' . $page . '</b>',
				'<b>' . $max . '</b>'
			) );
			?>
		</span>
		<?php echo wp_kses_post( $pagination ); ?>
	</div>
</div>

<?php
global $wpdb;
$perpag = get_option( 'posts_per_page' );
$perpag = ( empty( $perpag ) ) ? 10 : abs( $perpag );
$offset = ( $page - 1 ) * $perpag;
$args   = [];
$tquery = ' SELECT * FROM ' . self::PLUGIN_TABLE . ' WHERE type = %s ';
$args[] = 'file';
if ( ! empty( $valid ) ) {
	$tquery .= ' and valid = %d ';
	$args[]  = 1;
}
if ( ! empty( $size ) ) {
	$tquery .= ' and size_name = %s ';
	$args[]  = ( 'na' !== $size ) ? $size : '';
	$tquery .= ' and mimetype like %s ';
	$args[]  = 'image/%';
} elseif ( ! empty( $mime ) ) {
	$tquery .= ' and mimetype like %s ';
	$args[]  = ( 'na' !== $mime ) ? '%/' . $mime : '';
}
$tquery .= ' order by id limit %d,%d ';
$args[]  = $offset;
$args[]  = $perpag;
$query   = $wpdb->prepare( $tquery, $args ); // phpcs:ignore
$items   = $wpdb->get_results( $query ); // phpcs:ignore
if ( ! empty( $items ) ) {
	$upls = wp_upload_dir();
	$base = trailingslashit( $upls['baseurl'] );
	?>

	<table class="wp-list-table striped widefat fixed pages">
		<thead>
			<td width="60"></td>
			<td><h3><?php esc_html_e( 'File', 'sirsc' ); ?></h3></td>
			<td width="10%"><h3><?php esc_html_e( 'MIME Type', 'sirsc' ); ?></h3></td>
			<td width="15%" class="a-right"><h3><?php esc_html_e( 'File size', 'sirsc' ); ?></h3></td>
			<td width="15%" class="a-right"><h3><?php esc_html_e( 'Image Size', 'sirsc' ); ?></h3></td>
			<td width="15%"><h3><?php esc_html_e( 'Attachment', 'sirsc' ); ?></h3></td>
		</thead>

		<tbody>
			<?php
			foreach ( $items as $item ) {
				$url      = $base . $item->path;
				$sizename = empty( $item->size_name ) ? '~' . __( 'unknown', 'sirsc' ) . '~' : $item->size_name;
				$mimetype = empty( $item->mimetype ) ? '~' . __( 'unknown', 'sirsc' ) . '~' : $item->mimetype;
				?>
				<tr>
					<td data-colname="<?php esc_attr_e( 'Counter', 'sirsc' ); ?>">
						<?php echo ++ $offset; // phpcs:ignore ?>
					</td>
					<td data-colname="<?php esc_attr_e( 'File', 'sirsc' ); ?>">
						<div class="label-row">
							<?php if ( substr_count( $item->mimetype, 'image/' ) ) : ?>
								<img src="<?php echo esc_url( $url ); ?>" loading="lazy" class="thumb as-icon">
							<?php endif; ?>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="button has-icon tiny"><div class="dashicons dashicons-admin-links"></div></a>
							<?php echo esc_html( $item->path ); ?>
						</div>

						<?php if ( ! empty( $item->in_option ) ) : ?>
							<div class="sirsc-small-font"><?php esc_html_e( 'In option', 'sirsc' ); ?> <?php echo esc_html( $item->in_option ); ?></div>
						<?php endif; ?>
					</td>

					<td data-colname="<?php esc_attr_e( 'MIME Type', 'sirsc' ); ?>">
						<?php echo esc_html( $mimetype ); ?>
					</td>
					<td class="a-right" data-colname="<?php esc_attr_e( 'File size', 'sirsc' ); ?>">
						<b><?php echo esc_html( \SIRSC\Helper\human_filesize( $item->filesize ) ); ?></b>
						<div class="small-font">
							(<?php echo esc_html( $item->filesize ); ?> <?php esc_html_e( 'bytes', 'sirsc' ); ?>)
						</div>
					</td>
					<td class="a-right" data-colname="<?php esc_attr_e( 'Size', 'sirsc' ); ?>">
						<?php
						if ( ! substr_count( $item->mimetype, 'image/' ) ) {
							esc_html_e( 'N/A', 'sirsc' );
						} elseif ( ! empty( $item->size_width ) ) {
							?>
							<div>
							<b><?php echo esc_html( $item->size_width ); ?></b><span class="small-font">x</span><b><?php echo esc_html( $item->size_height ); ?></b><span class="small-font">px</span>
							<div class="small-font">(<?php echo esc_html( $sizename ); ?>)</div></div>
							<?php
						} else {
							echo esc_html( $sizename );
						}
						?>
					</td>
					<td data-colname="<?php esc_attr_e( 'Attachment', 'sirsc' ); ?>">
						<div class="label-row">
							<?php if ( ! empty( $item->attachment_id ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $item->attachment_id . '&action=edit' ) ); ?>" target="_blank" class="button has-icon tiny"><div class="dashicons dashicons-admin-links"></div></a>
								<?php echo (int) $item->attachment_id; ?>
							<?php else : ?>
								<div class="small-font">~<?php esc_html_e( 'unknown', 'sirsc' ); ?>~</div>
							<?php endif; ?>
						</div>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
}
