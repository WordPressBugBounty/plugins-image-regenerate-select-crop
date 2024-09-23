<?php
/**
 * SIRSC notice features.
 *
 * @package sirsc
 */

$maybe_trans = \get_transient( SIRSC_NOTICE );
if ( ! empty( $maybe_trans ) ) {
	$slug   = md5( SIRSC_SLUG );
	$ptitle = \__( 'Image Regenerate & Select Crop', 'sirsc' );

	// Translators: %1$s - plugin name.
	$activated = sprintf( \__( '%1$s plugin was activated!', 'sirsc' ), '<b>' . $ptitle . '</b>' );

	$donate = 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JJA37EHZXWUTJ&item_name=Support for development and maintenance (' . \rawurlencode( $ptitle ) . ')';

	$maybe_pro = sprintf(
		// Translators: %1$s - extensions URL.
		\__( '<a href="%1$s" rel="noreferrer">%2$s Premium extensions</a> are available for this plugin.', 'sirsc' ) . ' ',
		\esc_url( \admin_url( 'admin.php?page=image-regenerate-select-crop-extensions' ) ),
		'<span class="dashicons dashicons-admin-plugins"></span>'
	);

	$other_notice = sprintf(
		// Translators: %1$s - extensions URL.
		\__( '%5$sCheck out my other <a href="%1$s" target="_blank" rel="noreferrer">%2$s free plugins</a> on WordPress.org and the <a href="%3$s" target="_blank" rel="noreferrer">%4$s other extensions</a> available!', 'sirsc' ),
		'https://profiles.wordpress.org/iulia-cazan/#content-plugins',
		'<span class="dashicons dashicons-heart"></span>',
		'https://iuliacazan.ro/shop/',
		'<span class="dashicons dashicons-star-filled"></span>',
		$maybe_pro
	);
	?>
	<div id="item-<?php echo \esc_attr( $slug ); ?>" class="notice is-dismissible">
		<div class="content">
			<a class="icon" href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . SIRSC_PAGE ) ); ?>"><img src="<?php echo \esc_url( SIRSC_URL . 'assets/images/icon-128x128.gif' ); ?>"></a>
			<div class="details">
				<div>
					<h3><?php echo \wp_kses_post( $activated ); ?></h3>
					<div class="notice-other-items"><?php echo \wp_kses_post( $other_notice ); ?></div>
				</div>
				<div><?php echo \wp_kses_post( \SIRSC\Admin\donate_text() ); ?></div>
				<a class="notice-plugin-donate" href="<?php echo \esc_url( $donate ); ?>" target="_blank"><img src="<?php echo \esc_url( SIRSC_URL . 'assets/images/buy-me-a-coffee.png?v=' . SIRSC_VER ); ?>" width="200"></a>
			</div>
		</div>
		<button type="button" class="notice-dismiss" onclick="dismiss_notice_for_<?php echo \esc_attr( $slug ); ?>()"><span class="screen-reader-text"><?php \esc_html_e( 'Dismiss this notice.', 'sirsc' ); ?></span></button>
	</div>
	<?php
	$style = '#trans123super{--color-bg:rgba(144,202,233,.1); --color-border:#90cae9; border-left-color:var(--color-border);padding:0 38px 0 0!important}#trans123super *{margin:0}#trans123super .dashicons{color:var(--color-border)}#trans123super a{text-decoration:none}#trans123super img{display:flex;}#trans123super .content,#trans123super .details{display:flex;gap:1rem;padding-block:.5em}#trans123super .details{align-items:center;flex-wrap:wrap;padding-block:0}#trans123super .details>*{flex:1 1 35rem}#trans123super .details .notice-plugin-donate{flex:1 1 auto}#trans123super .details .notice-plugin-donate img{max-width:100%}#trans123super .icon{background:var(--color-bg);flex:0 0 4rem;margin:-.5em 0;padding:1rem}#trans123super .icon img{display:flex;height:auto;width:4rem} #trans123super h3{margin-bottom:0.5rem;text-transform:none}';
	$style = str_replace( '#trans123super', '#item-' . \esc_attr( $slug ), $style );
	echo '<style>' . $style . '</style>'; // phpcs:ignore
	?>
	<script>function dismiss_notice_for_<?php echo \esc_attr( $slug ); ?>() { document.getElementById( 'item-<?php echo \esc_attr( $slug ); ?>' ).style='display:none'; fetch( '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>?action=plugin-deactivate-notice-<?php echo \esc_attr( SIRSC_SLUG ); ?>' ); }</script>
	<?php
}
