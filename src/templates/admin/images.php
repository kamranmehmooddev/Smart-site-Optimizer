<?php
/**
 * Image Optimizer template.
 *
 * @package SmartSiteOptimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'sso_options', array() );
$image_options = isset( $options['image_optimizer'] ) ? $options['image_optimizer'] : array();
?>

<div class="wrap sso-images">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'sso_options' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto Optimize', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[image_optimizer][auto_optimize]" value="1" <?php checked( ! empty( $image_options['auto_optimize'] ) ); ?> />
						<?php esc_html_e( 'Automatically optimize images on upload', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'WebP Conversion', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[image_optimizer][webp_conversion]" value="1" <?php checked( ! empty( $image_options['webp_conversion'] ) ); ?> />
						<?php esc_html_e( 'Create WebP versions of images', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Compression Quality', 'smartsite-optimizer' ); ?></th>
				<td>
					<input type="number" name="sso_options[image_optimizer][compression_quality]" value="<?php echo esc_attr( $image_options['compression_quality'] ?? 80 ); ?>" min="1" max="100" />
					<p class="description"><?php esc_html_e( 'Image quality (1-100). Recommended: 80', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Max Width', 'smartsite-optimizer' ); ?></th>
				<td>
					<input type="number" name="sso_options[image_optimizer][max_width]" value="<?php echo esc_attr( $image_options['max_width'] ?? 2048 ); ?>" min="100" step="1" />
					<p class="description"><?php esc_html_e( 'Maximum image width in pixels', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Max Height', 'smartsite-optimizer' ); ?></th>
				<td>
					<input type="number" name="sso_options[image_optimizer][max_height]" value="<?php echo esc_attr( $image_options['max_height'] ?? 2048 ); ?>" min="100" step="1" />
					<p class="description"><?php esc_html_e( 'Maximum image height in pixels', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="sso-bulk-optimize">
		<h2><?php esc_html_e( 'Bulk Operations', 'smartsite-optimizer' ); ?></h2>
		<p><?php esc_html_e( 'Optimize existing images in your media library.', 'smartsite-optimizer' ); ?></p>
		<button class="button button-primary" id="sso-bulk-optimize-images">
			<?php esc_html_e( 'Optimize All Images', 'smartsite-optimizer' ); ?>
		</button>
		<div id="sso-optimize-progress" style="display:none;">
			<progress max="100" value="0"></progress>
			<p id="sso-optimize-status"></p>
		</div>
	</div>
</div>
