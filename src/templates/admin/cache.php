<?php
/**
 * Cache settings template.
 *
 * @package SmartSiteOptimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'sso_options', array() );
$cache_options = isset( $options['cache'] ) ? $options['cache'] : array();
?>

<div class="wrap sso-cache">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'sso_options' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Page Cache', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[cache][page_cache_enabled]" value="1" <?php checked( ! empty( $cache_options['page_cache_enabled'] ) ); ?> />
						<?php esc_html_e( 'Enable page caching', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Cache entire page output for faster delivery.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Cache TTL', 'smartsite-optimizer' ); ?></th>
				<td>
					<input type="number" name="sso_options[cache][cache_ttl]" value="<?php echo esc_attr( $cache_options['cache_ttl'] ?? 3600 ); ?>" min="60" step="60" />
					<p class="description"><?php esc_html_e( 'Cache lifetime in seconds. Default: 3600 (1 hour)', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Cache Logged-in Users', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[cache][cache_logged_in]" value="1" <?php checked( ! empty( $cache_options['cache_logged_in'] ) ); ?> />
						<?php esc_html_e( 'Enable caching for logged-in users', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Not recommended unless you have user-agnostic content.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Browser Cache', 'smartsite-optimizer' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="sso_options[cache][browser_cache]" value="1" <?php checked( ! empty( $cache_options['browser_cache'] ) ); ?> />
						<?php esc_html_e( 'Set browser cache headers', 'smartsite-optimizer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Configure optimal browser caching headers.', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude URLs', 'smartsite-optimizer' ); ?></th>
				<td>
					<textarea name="sso_options[cache][exclude_urls]" rows="5" class="large-text"><?php echo esc_textarea( implode( "\n", $cache_options['exclude_urls'] ?? array() ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One URL pattern per line (supports wildcards: /cart/*, /checkout/*)', 'smartsite-optimizer' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="sso-cache-actions">
		<h2><?php esc_html_e( 'Cache Management', 'smartsite-optimizer' ); ?></h2>
		<button class="button button-secondary" id="sso-clear-page-cache">
			<?php esc_html_e( 'Clear Page Cache', 'smartsite-optimizer' ); ?>
		</button>
		<button class="button button-secondary" id="sso-clear-all-cache">
			<?php esc_html_e( 'Clear All Caches', 'smartsite-optimizer' ); ?>
		</button>
	</div>
</div>
