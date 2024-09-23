<?php
/**
 * SIRSC admin functionality.
 *
 * @package sirsc
 */

declare( strict_types=1 );
namespace SIRSC\Admin;

?>
<p>
	<?php \esc_html_e( 'The advanced custom rules you configure below are global and will override all the other settings you set above.', 'sirsc' ); ?><br><b><?php \esc_html_e( 'Please be aware that the custom rules will apply only if you actually set up the post to use one of the rules below, and only then upload images to that post.', 'sirsc' ); ?></b>
</p>

<div class="label-row as-title"><h3><?php \esc_html_e( 'Advanced custom rules based on the post where the image will be uploaded', 'sirsc' ); ?></h3></div>

<p class="small-gap"><?php \esc_html_e( 'Very important: the order in which the rules are checked and have priority is: post ID, post type, post format, post parent, post tags, post categories, other taxonomies. Any of the rules that match first in this order will apply for the images that are generated when you upload images to that post (and the rest of the rules will be ignored). You can suppress at any time any of the rules and then enable these back as it suits you.', 'sirsc' ); ?></p>

<?php
$select_ims = '';
$checks_ims = '';
if ( ! empty( $all_sizes ) ) {
	$select_ims .= '<option value="**full**">- ' . \esc_attr( 'full/original' ) . ' -</option>';
	foreach ( $all_sizes as $k => $v ) {
		$select_ims .= '<option value="' . \esc_attr( $k ) . '">' . \esc_attr( $k ) . '</option>';
		$checks_ims .= ( ! empty( $checks_ims ) ) ? ' ' : '';
		$checks_ims .= '<label class="label-row" label-for="#ID#_' . \esc_attr( $k ) . '"><input type="checkbox" name="#NAME#" id="#ID#_' . \esc_attr( $k ) . '" value="' . \esc_attr( $k ) . '">' . \esc_attr( $k ) . '</label>';
	}
}
$checks_fea = $checks_ims;
$taxonomies = \get_taxonomies( [ 'public' => 1 ], 'objects' );
$select_tax = '';
if ( ! empty( $taxonomies ) ) {
	foreach ( $taxonomies as $k => $v ) {
		$select_tax .= '<option value="' . \esc_attr( $k ) . '">' . \esc_attr( $v->label ) . '</option>';
	}
}
$select_tax .= '<option value="ID">' . \esc_html__( 'Post ID', 'sirsc' ) . '</option>';
$select_tax .= '<option value="post_parent">' . \esc_html__( 'Post parent ID', 'sirsc' ) . '</option>';
$select_tax .= '<option value="post_type">' . \esc_html__( 'Post type', 'sirsc' ) . '</option>';

the_info_text( 'info_custom_for_featured', \__( 'Set below rules that would apply only for regenerating the featured image. An image becomes a "Featured Image" after the upload was already processed and when the post is saved, hence the rules set below can apply only when regenerating the image.', 'sirsc' ) );
?>

<table class="wp-list-table striped widefat pages">
	<thead>
		<tr>
			<td width="12%">
				<h3><?php \esc_html_e( 'The post has', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'Ex: Categories', 'sirsc' ); ?></div>
			</td>
			<td width="12%">
				<h3><?php \esc_html_e( 'Value', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'Ex: gallery,my-photos', 'sirsc' ); ?></div>
			</td>
			<td width="12%">
				<h3><?php \esc_html_e( 'Force original', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'Ex: large', 'sirsc' ); ?></div>
			</td>
			<td>
				<h3><?php \esc_html_e( 'Generate only these image sizes for the rule', 'sirsc' ); ?></h3>
				<div class="row-hint"><?php \esc_html_e( 'Ex: thumbnail, large', 'sirsc' ); ?></div>
			</td>
			<td width="13%" colspan="2">
				<h3><?php \esc_html_e( 'Suppress', 'sirsc' ); ?></h3>
			</td>
		</tr>
	</thead>
	<tbody>
		<?php
		for ( $i = 1; $i <= 10; $i++ ) {
			$class = 'row-hide-rule';
			if ( ! empty( \SIRSC::$user_custom_rules[ $i ]['type'] )
				&& ! empty( \SIRSC::$user_custom_rules[ $i ]['value'] ) ) {
				$class = 'row-use-rule';
			}
			if ( ! empty( \SIRSC::$user_custom_rules[ $i ]['suppress'] )
				&& 'on' === \SIRSC::$user_custom_rules[ $i ]['suppress'] ) {
				$class .= ' row-ignore-rule';
			}

			$supp = ( ! empty( \SIRSC::$user_custom_rules[ $i ]['suppress'] ) && 'on' === \SIRSC::$user_custom_rules[ $i ]['suppress'] ) ? ' checked="checked"' : '';

			$row_class = ( substr_count( $class, 'row-ignore-rule' ) ) ? 'row-ignore-rule' : $class;
			$row_class = ( substr_count( $class, 'row-hide-rule' ) ) ? 'row-hide-rule' : $row_class;
			$row_class = ( substr_count( $class, 'row-use-rule' ) ) ? 'row-use-rule' : $row_class;

			if ( substr_count( $class, 'row-ignore-rule' ) ) {
				$row_class .= ' sirsc-message warning';
			} elseif ( substr_count( $class, 'row-use-rule' ) ) {
				$row_class .= ' sirsc-message success';
			}

			$extra = '';
			if ( substr_count( $class, 'row-ignore' ) ) {
				$extra .= ' sirsc-message warning';
			} else {
				$extra .= ' sirsc-message success';
			}
			?>
			<tr class="advanced-rules <?php echo \esc_attr( $row_class ); ?>">
				<td data-colname="<?php \esc_attr_e( 'The post has', 'sirsc' ); ?>">
					<select name="_user_custom_rule[<?php echo (int) $i; ?>][type]" id="user_custom_rule_<?php echo (int) $i; ?>_type">
						<option value=""><?php \esc_html_e( 'N/A', 'sirsc' ); ?></option>
						<?php
						echo str_replace( // phpcs:ignore
							'value="' . \esc_attr( \SIRSC::$user_custom_rules[ $i ]['type'] ) . '"',
							'value="' . \esc_attr( \SIRSC::$user_custom_rules[ $i ]['type'] ) . '" selected="selected"',
							$select_tax
						);
						?>
					</select>
				</td>
				<td class="option-value" data-colname="<?php \esc_attr_e( 'Value', 'sirsc' ); ?>">
					<input type="text" name="_user_custom_rule[<?php echo (int) $i; ?>][value]" name="user_custom_rule_<?php echo (int) $i; ?>_value" value="<?php echo \esc_attr( \SIRSC::$user_custom_rules[ $i ]['value'] ); ?>" size="20">
				</td>
				<td class="option-original" data-colname="<?php \esc_attr_e( 'Force original', 'sirsc' ); ?>">
					<select name="_user_custom_rule[<?php echo (int) $i; ?>][original]" id="user_custom_rule_<?php echo (int) $i; ?>_original">
						<?php
						$sel = ( ! empty( \SIRSC::$user_custom_rules[ $i ]['original'] ) ) ? \SIRSC::$user_custom_rules[ $i ]['original'] : 'large';
						echo str_replace( // phpcs:ignore
							' value="' . $sel . '"',
							' value="' . $sel . '" selected="selected"',
							$select_ims
						);
						?>
					</select>
				</td>
				<td class="option-sizes" data-colname="<?php \esc_attr_e( 'Generate only these image sizes for the rule', 'sirsc' ); ?>">
					<?php
					if ( ! empty( $class ) && substr_count( $class, 'row-use' ) ) {

						echo '<div class="potential-rule ' . $class . $extra . '">'; // phpcs:ignore
						if ( substr_count( $class, 'row-ignore' ) ) {
							\esc_html_e( 'This rule is SUPPRESSED', 'sirsc' );
						} else {
							\esc_html_e( 'This rule is ACTIVE', 'sirsc' );
						}
						echo ': ';

						// phpcs:disable
						if ( '**full**' === \SIRSC::$user_custom_rules[ $i ]['original'] ) {
							echo sprintf(
								// Translators: %1$s type, %2$s value, %3$s only.
								\esc_html__( 'uploading images to a post that has %1$s as %2$s will generate only the %3$s sizes.', 'sirsc' ),
								'<b>' . \SIRSC::$user_custom_rules[ $i ]['type'] . '</b>',
								'<b>' . \SIRSC::$user_custom_rules[ $i ]['value'] . '</b>',
								'<b>' . implode( ', ', array_unique( \SIRSC::$user_custom_rules[ $i ]['only'] ) ) . '</b>'
							);
						} else {
							echo sprintf(
								// Translators: %1$s type, %2$s value, %3$s original, %4$s only.
								\esc_html__( 'uploading images to a post that has %1$s as %2$s will force the original image to %3$s size and will generate only the %4$s sizes.', 'sirsc' ),
								'<b>' . \SIRSC::$user_custom_rules[ $i ]['type'] . '</b>',
								'<b>' . \SIRSC::$user_custom_rules[ $i ]['value'] . '</b>',
								'<b>' . \SIRSC::$user_custom_rules[ $i ]['original'] . '</b>',
								'<b>' . implode( ', ', array_unique( \SIRSC::$user_custom_rules[ $i ]['only'] ) ) . '</b>'
							);
						}
						echo '</div><br>';
						// phpcs:enable
					}
					?>
					<div class="as-box sirsc-group">
						<div class="as-row columns-3">
							<?php
							$only = str_replace( '#ID#', '_user_custom_rule_' . $i . '_only_', $checks_ims );
							$only = str_replace( '#NAME#', '_user_custom_rule[' . $i . '][only][]', $only );
							$sel  = ( ! empty( \SIRSC::$user_custom_rules[ $i ]['only'] ) ) ? \SIRSC::$user_custom_rules[ $i ]['only'] : [ 'thumbnail', 'large' ];
							foreach ( $sel as $is ) {
								if ( ! empty( $class ) && substr_count( $class, 'row-use' ) ) {
									$only = str_replace( ' value="' . $is . '"', ' value="' . $is . '" checked="checked" class="row-use"', str_replace( ' label-for="' . $is . '"', ' label-for="' . $is . '" class="' . $class . '"', $only ) );
								}
							}
							echo $only; // phpcs:ignore
							?>
						</div>
					</div>
					<br>
					<div class="as-box sirsc-group">
						<div class="label-row">
							<?php the_info_icon( 'info_custom_for_featured' ); ?>
							<h3><?php \esc_html_e( 'Rules that would apply only for regenerating the featured image', 'sirsc' ); ?></h3>
						</div>
						<div class="as-row columns-3">
							<?php
							$featured = str_replace( '#NAME#', '_user_custom_rule[' . $i . '][forfeatured][]', str_replace( '#ID#', '_user_custom_rule_' . $i . '_forfeatured_', $checks_fea ) );

							$sel = ( ! empty( \SIRSC::$user_custom_rules[ $i ]['forfeatured'] ) ) ? \SIRSC::$user_custom_rules[ $i ]['forfeatured'] : [];
							foreach ( $sel as $is ) {
								if ( ! empty( $class ) && substr_count( $class, 'row-use' ) ) {
									$featured = str_replace( ' value="' . $is . '"', ' value="' . $is . '" checked="checked" class="row-use"', str_replace( ' label-for="' . $is . '"', ' label-for="' . $is . '" class="' . $class . '"', $featured ) );
								}
							}
							echo $featured; // phpcs:ignore
							?>
						</div>
					</div>
				</td>
				<td class="option-supress" width="48" data-colname="<?php \esc_attr_e( 'Suppress', 'sirsc' ); ?>">
					<input type="checkbox" name="_user_custom_rule[<?php echo (int) $i; ?>][suppress]" id="user_custom_rule_<?php echo (int) $i; ?>_suppress" <?php echo $supp; // phpcs:ignore ?>>
				</td>
				<td>
					<button type="button" class="button button-primary" name="sirsc-settings-advanced-rules" value="submit" data-sirsc-autosubmit="click"><?php \esc_html_e( 'Save Settings', 'sirsc' ); ?></button>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
