<?php
/**
 * SIRSC template for the Attachment details, used for example in the sidebar.
 *
 * @package sirsc
 */

// phpcs:disable
$alt_text_description = sprintf(
	/* Translators: 1: Link to tutorial, 2: Additional link attributes, 3: Accessibility text. */
	__( '<a href="%1$s" %2$s>Learn how to describe the purpose of the image%3$s</a>. Leave empty if the image is purely decorative.' ),
	\esc_url( 'https://www.w3.org/WAI/tutorials/images/decision-tree' ),
	'target="_blank" rel="noopener"',
	\sprintf(
		/* Yranslators: Hidden accessibility text. */
		'<span class="screen-reader-text"> %s</span>',
		__( '(opens in a new tab)' )
	)
);
?>

<script type="text/html" id="tmpl-attachment-details_custom">
	<h2>
		<?php _e( 'Attachment Details' ); ?>
		<span class="settings-save-status" role="status">
			<span class="spinner"></span>
			<span class="saved"><?php esc_html_e( 'Saved.' ); ?></span>
		</span>
	</h2>
	<div class="attachment-info">

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
		<# } else { #>
			<div class="thumbnail thumbnail-{{ data.type }}">
				<# if ( data.uploading ) { #>
					<div class="media-progress-bar"><div></div></div>
				<# } else if ( 'image' === data.type && data.size && data.size.url ) { #>
					<img src="{{ data.size.url }}" draggable="false" alt="" />
				<# } else { #>
					<img src="{{ data.icon }}" class="icon" draggable="false" alt="" />
				<# } #>
			</div>
		<# } #>

		<div class="details">
			<div class="filename">{{ data.filename }}</div>
			<div class="uploaded">{{ data.dateFormatted }}</div>

			<div class="file-size">{{ data.filesizeHumanReadable }}</div>
			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions">
						<?php
						/* translators: 1: A number of pixels wide, 2: A number of pixels tall. */
						printf( __( '%1$s by %2$s pixels' ), '{{ data.width }}', '{{ data.height }}' );
						?>
					</div>
				<# } #>

				<# if ( data.originalImageURL && data.originalImageName ) { #>
					<div class="word-wrap-break-word">
						<?php _e( 'Original image:' ); ?>
						<a href="{{ data.originalImageURL }}">{{data.originalImageName}}</a>
					</div>
				<# } #>

				<# if ( data.can.save && data.sizes ) { #>
					<a class="edit-attachment" href="{{ data.editLink }}&amp;image-editor" target="_blank"><?php _e( 'Edit Image' ); ?></a>
				<# } #>
			<# } #>

			<# if ( data.fileLength && data.fileLengthHumanReadable ) { #>
				<div class="file-length"><?php _e( 'Length:' ); ?>
					<span aria-hidden="true">{{ data.fileLength }}</span>
					<span class="screen-reader-text">{{ data.fileLengthHumanReadable }}</span>
				</div>
			<# } #>

			<# if ( data.mediaStates ) { #>
				<div class="media-states"><strong><?php _e( 'Used as:' ); ?></strong> {{ data.mediaStates }}</div>
			<# } #>

			<# if ( ! data.uploading && data.can.remove ) { #>
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

			<div class="compat-meta">
				<# if ( data.compat && data.compat.meta ) { #>
					{{{ data.compat.meta }}}
				<# } #>
			</div>
		</div>
	</div>
	<# if ( 'image' === data.type && ! data.uploading ) { #>
		<span class="setting extra" data-setting="extra">
			<label class="name"><?php _e( 'Sub-sizes', 'sirsc' ); ?></label>
			<?php SIRSC\Admin\media_template_buttons(); ?>
		</span>
	<# } #>
	<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
	<# if ( 'image' === data.type ) { #>
		<span class="setting alt-text has-description" data-setting="alt">
			<label for="attachment-details-alt-text" class="name"><?php _e( 'Alt Text' ); ?></label>
			<textarea id="attachment-details-alt-text" aria-describedby="alt-text-description" {{ maybeReadOnly }}>{{ data.alt }}</textarea>
		</span>
		<p class="description" id="alt-text-description"><?php echo $alt_text_description; ?></p>
	<# } #>
	<?php if ( post_type_supports( 'attachment', 'title' ) ) : ?>
	<span class="setting" data-setting="title">
		<label for="attachment-details-title" class="name"><?php _e( 'Title' ); ?></label>
		<input type="text" id="attachment-details-title" value="{{ data.title }}" {{ maybeReadOnly }} />
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
		<label for="attachment-details-<?php echo esc_attr( $key ); ?>" class="name"><?php echo $label; ?></label>
		<input type="text" id="attachment-details-<?php echo esc_attr( $key ); ?>" value="{{ data.<?php echo $key; ?> || data.meta.<?php echo $key; ?> || '' }}" />
	</span>
	<?php endforeach; ?>
	<# } #>
	<span class="setting" data-setting="caption">
		<label for="attachment-details-caption" class="name"><?php _e( 'Caption' ); ?></label>
		<textarea id="attachment-details-caption" {{ maybeReadOnly }}>{{ data.caption }}</textarea>
	</span>
	<span class="setting" data-setting="description">
		<label for="attachment-details-description" class="name"><?php _e( 'Description' ); ?></label>
		<textarea id="attachment-details-description" {{ maybeReadOnly }}>{{ data.description }}</textarea>
	</span>
	<span class="setting" data-setting="url">
		<label for="attachment-details-copy-link" class="name"><?php _e( 'File URL:' ); ?></label>
		<input type="text" class="attachment-details-copy-link" id="attachment-details-copy-link" value="{{ data.url }}" readonly />
		<div class="copy-to-clipboard-container">
			<button type="button" class="button button-small copy-attachment-url" data-clipboard-target="#attachment-details-copy-link"><?php _e( 'Copy URL to clipboard' ); ?></button>
			<span class="success hidden" aria-hidden="true"><?php _e( 'Copied!' ); ?></span>
		</div>
	</span>
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
	if( typeof wp.media.view.Attachment.Details !== 'undefined' ) {
		wp.media.view.Attachment.Details.prototype.template = wp.media.template( 'attachment-details_custom' );
	}
});
</script>
