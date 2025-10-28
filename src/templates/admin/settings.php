<?php
/**
 * Settings template.
 *
 * @package SmartSiteOptimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'sso_options', array() );
?>

<div class="wrap sso-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'sso_options' ); ?>

		<h2><?php esc_html_e( 'Module Settings', 'smartsite-optimizer' ); ?></h2>
		<p><?php esc_html_e( 'Enable or disable individual modules. Disabled modules will not be loaded.', 'smartsite-optimizer' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Asset Manager', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[disable_asset_manager]" value="1" <?php checked( ! empty( $options['disable_asset_manager'] ) ); ?> />
						<?php esc_html_e( 'Disable Asset Manager module', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Cache Manager', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[disable_cache]" value="1" <?php checked( ! empty( $options['disable_cache'] ) ); ?> />
						<?php esc_html_e( 'Disable Cache Manager module', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Analytics', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[disable_analytics]" value="1" <?php checked( ! empty( $options['disable_analytics'] ) ); ?> />
						<?php esc_html_e( 'Disable Analytics module', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Image Optimizer', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[disable_image_optimizer]" value="1" <?php checked( ! empty( $options['disable_image_optimizer'] ) ); ?> />
						<?php esc_html_e( 'Disable Image Optimizer module', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Advanced Settings', 'smartsite-optimizer' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minification', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[minification][minify_html]" value="1" <?php checked( ! empty( $options['minification']['minify_html'] ?? true ) ); ?> />
						<?php esc_html_e( 'Minify HTML', 'smartsite-optimizer' ); ?>
					</label><br>
					<label>
						<input type="checkbox" name="sso_options[minification][minify_css]" value="1" <?php checked( ! empty( $options['minification']['minify_css'] ?? true ) ); ?> />
						<?php esc_html_e( 'Minify CSS', 'smartsite-optimizer' ); ?>
					</label><br>
					<label>
						<input type="checkbox" name="sso_options[minification][minify_js]" value="1" <?php checked( ! empty( $options['minification']['minify_js'] ?? true ) ); ?> />
						<?php esc_html_e( 'Minify JavaScript', 'smartsite-optimizer' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'DNS Prefetch', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[dns_prefetch][enabled]" value="1" <?php checked( ! empty( $options['dns_prefetch']['enabled'] ?? true ) ); ?> />
						<?php esc_html_e( 'Enable DNS prefetching', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Automatically detect and prefetch external domains.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
