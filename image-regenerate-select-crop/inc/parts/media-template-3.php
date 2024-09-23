<?php
/**
 * SIRSC template for the Attachment Details two columns layout.
 *
 * @package sirsc
 */

// phpcs:disable
$alt_text_description = sprintf(
	/* Translators: 1: Link to tutorial, 2: Additional link attributes, 3: Accessibility text. */
	__( '<a href="%1$s" %2$s>Learn how to describe the purpose of the image%3$s</a>. Leave empty if the image is purely decorative.' ),
	\esc_url( 'https://www.w3.org/WAI/tutorials/images/decision-tree' ),
	'target="_blank" rel="noopener"',
	/* Translators: Hidden accessibility text. */
	\sprintf( '<span class="screen-reader-text"> %s</span>', __( '(opens in a new tab)' ) )
);
?>

<script type="text/html" id="tmpl-attachment-details-two-column_custom">
	<div class="attachment-media-view {{ data.orientation }}">
		<?php
		if ( isset( $_GET['error'] ) && 'deprecated' === $_GET['error'] ) {
			wp_admin_notice(
				__( 'The Edit Media screen is deprecated as of WordPress 6.3. Please use the Media Library instead.' ),
				array(
					'id'                 => 'message',
					'additional_classes' => array( 'error' ),
				)
			);
		}
		?>
		<h2 class="screen-reader-text"><?php /* translators: Hidden accessibility text. */ _e( 'Attachment Preview' ); ?></h2>
		<div class="thumbnail thumbnail-{{ data.type }}">
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div></div></div>
			<# } else if ( data.sizes && data.sizes.full ) { #>
				<img class="details-image" src="{{ data.sizes.full.url }}" draggable="false" alt="" />
			<# } else if ( data.sizes && data.sizes.large ) { #>
				<img class="details-image" src="{{ data.sizes.large.url }}" draggable="false" alt="" />
			<# } else if ( -1 === jQuery.inArray( data.type, [ 'audio', 'video' ] ) ) { #>
				<img class="details-image icon" src="{{ data.icon }}" draggable="false" alt="" />
			<# } #>

			<# if ( 'audio' === data.type ) { #>
			<div class="wp-media-wrapper wp-audio">
				<audio style="visibility: hidden" controls class="wp-audio-shortcode" width="100%" preload="none">
					<source type="{{ data.mime }}" src="{{ data.url }}" />
				</audio>
			</div>
			<# } else if ( 'video' === data.type ) {
				var w_rule = '';
				if ( data.width ) {
					w_rule = 'width: ' + data.width + 'px;';
				} else if ( wp.media.view.settings.contentWidth ) {
					w_rule = 'width: ' + wp.media.view.settings.contentWidth + 'px;';
				}
			#>
			<div style="{{ w_rule }}" class="wp-media-wrapper wp-video">
				<video controls="controls" class="wp-video-shortcode" preload="metadata"
					<# if ( data.width ) { #>width="{{ data.width }}"<# } #>
					<# if ( data.height ) { #>height="{{ data.height }}"<# } #>
					<# if ( data.image && data.image.src !== data.icon ) { #>poster="{{ data.image.src }}"<# } #>>
					<source type="{{ data.mime }}" src="{{ data.url }}" />
				</video>
			</div>
			<# } #>

			<div class="attachment-actions">
				<# if ( 'image' === data.type && ! data.uploading && data.sizes && data.can.save ) { #>
				<button type="button" class="button edit-attachment"><?php _e( 'Edit Image' ); ?></button>
				<# } else if ( 'pdf' === data.subtype && data.sizes ) { #>
				<p><?php _e( 'Document Preview' ); ?></p>
				<# } #>
			</div>
		</div>
	</div>
	<div class="attachment-info">
		<span class="settings-save-status" role="status">
			<span class="spinner"></span>
			<span class="saved"><?php esc_html_e( 'Saved.' ); ?></span>
		</span>
		<div class="details">
			<h2 class="screen-reader-text">
				<?php
				/* translators: Hidden accessibility text. */
				_e( 'Details' );
				?>
			</h2>
			<div class="uploaded"><strong><?php _e( 'Uploaded on:' ); ?></strong> {{ data.dateFormatted }}</div>
			<div class="uploaded-by">
				<strong><?php _e( 'Uploaded by:' ); ?></strong>
					<# if ( data.authorLink ) { #>
						<a href="{{ data.authorLink }}">{{ data.authorName }}</a>
					<# } else { #>
						{{ data.authorName }}
					<# } #>
			</div>
			<# if ( data.uploadedToTitle ) { #>
				<div class="uploaded-to">
					<strong><?php _e( 'Uploaded to:' ); ?></strong>
					<# if ( data.uploadedToLink ) { #>
						<a href="{{ data.uploadedToLink }}">{{ data.uploadedToTitle }}</a>
					<# } else { #>
						{{ data.uploadedToTitle }}
					<# } #>
				</div>
			<# } #>
			<div class="filename"><strong><?php _e( 'File name:' ); ?></strong> {{ data.filename }}</div>
			<div class="file-type"><strong><?php _e( 'File type:' ); ?></strong> {{ data.mime }}</div>
			<div class="file-size"><strong><?php _e( 'File size:' ); ?></strong> {{ data.filesizeHumanReadable }}</div>
			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions"><strong><?php _e( 'Dimensions:' ); ?></strong>
						<?php
						/* translators: 1: A number of pixels wide, 2: A number of pixels tall. */
						printf( __( '%1$s by %2$s pixels' ), '{{ data.width }}', '{{ data.height }}' );
						?>
					</div>
				<# } #>

				<# if ( data.originalImageURL && data.originalImageName ) { #>
					<div class="word-wrap-break-word">
						<strong><?php _e( 'Original image:' ); ?></strong>
						<a href="{{ data.originalImageURL }}">{{data.originalImageName}}</a>
					</div>
				<# } #>
			<# } #>

			<# if ( data.fileLength && data.fileLengthHumanReadable ) { #>
				<div class="file-length"><strong><?php _e( 'Length:' ); ?></strong>
					<span aria-hidden="true">{{ data.fileLength }}</span>
					<span class="screen-reader-text">{{ data.fileLengthHumanReadable }}</span>
				</div>
			<# } #>

			<# if ( 'audio' === data.type && data.meta.bitrate ) { #>
				<div class="bitrate">
					<strong><?php _e( 'Bitrate:' ); ?></strong> {{ Math.round( data.meta.bitrate / 1000 ) }}kb/s
					<# if ( data.meta.bitrate_mode ) { #>
					{{ ' ' + data.meta.bitrate_mode.toUpperCase() }}
					<# } #>
				</div>
			<# } #>

			<# if ( data.mediaStates ) { #>
				<div class="media-states"><strong><?php _e( 'Used as:' ); ?></strong> {{ data.mediaStates }}</div>
			<# } #>

			<div class="compat-meta">
				<# if ( data.compat && data.compat.meta ) { #>
					{{{ data.compat.meta }}}
				<# } #>
			</div>
		</div>

		<div class="settings">
			<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
			<# if ( 'image' === data.type ) { #>
				<span class="setting extra" data-setting="extra">
					<label class="name"><?php _e( 'Sub-sizes', 'sirsc' ); ?></label>
					<?php SIRSC\Admin\media_template_buttons(); ?>
				</span>
			<# } #>
			<# if ( 'image' === data.type ) { #>
				<span class="setting alt-text has-description" data-setting="alt">
					<label for="attachment-details-two-column-alt-text" class="name"><?php _e( 'Alternative Text' ); ?></label>
					<textarea id="attachment-details-two-column-alt-text" aria-describedby="alt-text-description" {{ maybeReadOnly }}>{{ data.alt }}</textarea>
				</span>
				<p class="description" id="alt-text-description"><?php echo $alt_text_description; ?></p>
			<# } #>
			<?php if ( post_type_supports( 'attachment', 'title' ) ) : ?>
			<span class="setting" data-setting="title">
				<label for="attachment-details-two-column-title" class="name"><?php _e( 'Title' ); ?></label>
				<input type="text" id="attachment-details-two-column-title" value="{{ data.title }}" {{ maybeReadOnly }} />
			</span>
			<?php endif; ?>
			<# if ( 'audio' === data.type ) { #>
			<?php
			foreach ( array(
				'artist' => __( 'Artist' ),
				'album'  => __( 'Album' ),
			) as $key => $label ) :
				?>
			<span class="setting" data-setting="<?php echo esc_attr( $key ); ?>">
				<label for="attachment-details-two-column-<?php echo esc_attr( $key ); ?>" class="name"><?php echo $label; ?></label>
				<input type="text" id="attachment-details-two-column-<?php echo esc_attr( $key ); ?>" value="{{ data.<?php echo $key; ?> || data.meta.<?php echo $key; ?> || '' }}" />
			</span>
			<?php endforeach; ?>
			<# } #>
			<span class="setting" data-setting="caption">
				<label for="attachment-details-two-column-caption" class="name"><?php _e( 'Caption' ); ?></label>
				<textarea id="attachment-details-two-column-caption" {{ maybeReadOnly }}>{{ data.caption }}</textarea>
			</span>
			<span class="setting" data-setting="description">
				<label for="attachment-details-two-column-description" class="name"><?php _e( 'Description' ); ?></label>
				<textarea id="attachment-details-two-column-description" {{ maybeReadOnly }}>{{ data.description }}</textarea>
			</span>
			<span class="setting" data-setting="url">
				<label for="attachment-details-two-column-copy-link" class="name"><?php _e( 'File URL:' ); ?></label>
				<input type="text" class="attachment-details-copy-link" id="attachment-details-two-column-copy-link" value="{{ data.url }}" readonly />
				<span class="copy-to-clipboard-container">
					<button type="button" class="button button-small copy-attachment-url" data-clipboard-target="#attachment-details-two-column-copy-link"><?php _e( 'Copy URL to clipboard' ); ?></button>
					<span class="success hidden" aria-hidden="true"><?php _e( 'Copied!' ); ?></span>
				</span>
			</span>
			<div class="attachment-compat"></div>
		</div>

		<div class="actions">
			<# if ( data.link ) { #>
				<?php
				$view_media_text = ( '1' === get_option( 'wp_attachment_pages_enabled' ) ) ? __( 'View attachment page' ) : __( 'View media file' );
				?>
				<a class="view-attachment" href="{{ data.link }}"><?php echo $view_media_text; ?></a>
			<# } #>
			<# if ( data.can.save ) { #>
				<# if ( data.link ) { #>
					<span class="links-separator">|</span>
				<# } #>
				<a href="{{ data.editLink }}"><?php _e( 'Edit more details' ); ?></a>
			<# } #>
			<# if ( data.can.save && data.link ) { #>
				<span class="links-separator">|</span>
				<a href="{{ data.url }}" download><?php _e( 'Download file' ); ?></a>
			<# } #>
			<# if ( ! data.uploading && data.can.remove ) { #>
				<# if ( data.link || data.can.save ) { #>
					<span class="links-separator">|</span>
				<# } #>
				<?php if ( MEDIA_TRASH ) : ?>
					<# if ( 'trash' === data.status ) { #>
						<button type="button" class="button-link untrash-attachment"><?php _e( 'Restore from Trash' ); ?></button>
					<# } else { #>
						<button type="button" class="button-link trash-attachment"><?php _e( 'Move to Trash' ); ?></button>
					<# } #>
				<?php else : ?>
					<button type="button" class="button-link delete-attachment"><?php _e( 'Delete permanently' ); ?></button>
				<?php endif; ?>
			<# } #>
		</div>
	</div>
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
	if( typeof wp.media.view.Attachment.Details.TwoColumn !== 'undefined' ) {
		wp.media.view.Attachment.Details.TwoColumn.prototype.template = wp.media.template( 'attachment-details-two-column_custom' );
	}
});
</script>
