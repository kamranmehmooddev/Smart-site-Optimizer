<?php
/**
 * Asset Manager template.
 *
 * @package SmartSiteOptimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'sso_options', array() );
$asset_options = isset( $options['asset_manager'] ) ? $options['asset_manager'] : array();
?>

<div class="wrap sso-asset-manager">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'sso_options' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Defer JavaScript', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[asset_manager][defer_js]" value="1" <?php checked( ! empty( $asset_options['defer_js'] ) ); ?> />
						<?php esc_html_e( 'Enable JavaScript deferral for non-critical scripts', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Automatically defer non-critical JavaScript to improve page load time.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Defer CSS', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[asset_manager][defer_css]" value="1" <?php checked( ! empty( $asset_options['defer_css'] ) ); ?> />
						<?php esc_html_e( 'Enable CSS deferral for non-critical stylesheets', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Load non-critical CSS asynchronously to speed up initial render.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Critical CSS', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[asset_manager][critical_css]" value="1" <?php checked( ! empty( $asset_options['critical_css'] ) ); ?> />
						<?php esc_html_e( 'Enable critical CSS inlining', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Inline critical above-the-fold CSS for faster first paint.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load Images', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[asset_manager][lazy_load_images]" value="1" <?php checked( ! empty( $asset_options['lazy_load_images'] ) ); ?> />
						<?php esc_html_e( 'Enable image lazy loading with blur-up placeholders', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Load images only when they enter the viewport.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Lazy Load Iframes', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[asset_manager][lazy_load_iframes]" value="1" <?php checked( ! empty( $asset_options['lazy_load_iframes'] ) ); ?> />
						<?php esc_html_e( 'Enable iframe lazy loading', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Defer loading of embedded content like videos.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Font Display Optimization', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[asset_manager][font_display_swap]" value="1" <?php checked( ! empty( $asset_options['font_display_swap'] ) ); ?> />
						<?php esc_html_e( 'Enable font-display: swap for web fonts', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Show fallback fonts while web fonts are loading.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
