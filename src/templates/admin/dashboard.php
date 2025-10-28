<?php
/**
 * Dashboard template.
 *
 * @package SmartSiteOptimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap sso-dashboard">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="sso-header">
		<div class="sso-logo">
			<h2><?php esc_html_e( 'SmartSite Optimizer', 'smartsite-optimizer' ); ?></h2>
			<p><?php esc_html_e( 'All-in-one performance and user experience solution', 'smartsite-optimizer' ); ?></p>
		</div>
		<div class="sso-actions">
			<button class="button button-primary button-hero" id="sso-run-optimization">
				<?php esc_html_e( 'Run One-Click Optimization', 'smartsite-optimizer' ); ?>
			</button>
			<button class="button button-secondary" id="sso-clear-cache">
				<?php esc_html_e( 'Clear All Cache', 'smartsite-optimizer' ); ?>
			</button>
		</div>
	</div>

	<div class="sso-metrics-grid">
		<div class="sso-metric-card">
			<div class="sso-metric-icon">
				<span class="dashicons dashicons-performance"></span>
			</div>
			<div class="sso-metric-content">
				<h3><?php esc_html_e( 'Performance Score', 'smartsite-optimizer' ); ?></h3>
				<p class="sso-metric-value" id="sso-performance-score">--</p>
				<p class="sso-metric-description"><?php esc_html_e( 'Average score over last 7 days', 'smartsite-optimizer' ); ?></p>
			</div>
		</div>

		<div class="sso-metric-card">
			<div class="sso-metric-icon">
				<span class="dashicons dashicons-database"></span>
			</div>
			<div class="sso-metric-content">
				<h3><?php esc_html_e( 'Cache Hit Rate', 'smartsite-optimizer' ); ?></h3>
				<p class="sso-metric-value" id="sso-cache-hit-rate">--</p>
				<p class="sso-metric-description"><?php esc_html_e( 'Percentage of cached requests', 'smartsite-optimizer' ); ?></p>
			</div>
		</div>

		<div class="sso-metric-card">
			<div class="sso-metric-icon">
				<span class="dashicons dashicons-images-alt2"></span>
			</div>
			<div class="sso-metric-content">
				<h3><?php esc_html_e( 'Images Optimized', 'smartsite-optimizer' ); ?></h3>
				<p class="sso-metric-value" id="sso-images-optimized">--</p>
				<p class="sso-metric-description"><?php esc_html_e( 'Total images optimized', 'smartsite-optimizer' ); ?></p>
			</div>
		</div>

		<div class="sso-metric-card">
			<div class="sso-metric-icon">
				<span class="dashicons dashicons-chart-line"></span>
			</div>
			<div class="sso-metric-content">
				<h3><?php esc_html_e( 'Page Load Time', 'smartsite-optimizer' ); ?></h3>
				<p class="sso-metric-value" id="sso-page-load-time">--</p>
				<p class="sso-metric-description"><?php esc_html_e( 'Average LCP over last 7 days', 'smartsite-optimizer' ); ?></p>
			</div>
		</div>
	</div>

	<div class="sso-charts">
		<div class="sso-chart-container">
			<h3><?php esc_html_e( 'Core Web Vitals - Last 7 Days', 'smartsite-optimizer' ); ?></h3>
			<canvas id="sso-vitals-chart"></canvas>
		</div>

		<div class="sso-chart-container">
			<h3><?php esc_html_e( 'Top Performing Pages', 'smartsite-optimizer' ); ?></h3>
			<div id="sso-top-pages">
				<p><?php esc_html_e( 'Loading...', 'smartsite-optimizer' ); ?></p>
			</div>
		</div>
	</div>

	<div class="sso-features-overview">
		<h3><?php esc_html_e( 'Active Features', 'smartsite-optimizer' ); ?></h3>
		<div class="sso-features-grid">
			<div class="sso-feature-card">
				<span class="dashicons dashicons-editor-code"></span>
				<h4><?php esc_html_e( 'Asset Management', 'smartsite-optimizer' ); ?></h4>
				<p><?php esc_html_e( 'Intelligent CSS/JS optimization and lazy loading', 'smartsite-optimizer' ); ?></p>
			</div>
			<div class="sso-feature-card">
				<span class="dashicons dashicons-database-view"></span>
				<h4><?php esc_html_e( 'Advanced Caching', 'smartsite-optimizer' ); ?></h4>
				<p><?php esc_html_e( 'Page and object caching with smart invalidation', 'smartsite-optimizer' ); ?></p>
			</div>
			<div class="sso-feature-card">
				<span class="dashicons dashicons-analytics"></span>
				<h4><?php esc_html_e( 'Real-time Analytics', 'smartsite-optimizer' ); ?></h4>
				<p><?php esc_html_e( 'Core Web Vitals and user behavior tracking', 'smartsite-optimizer' ); ?></p>
			</div>
			<div class="sso-feature-card">
				<span class="dashicons dashicons-format-image"></span>
				<h4><?php esc_html_e( 'Image Optimization', 'smartsite-optimizer' ); ?></h4>
				<p><?php esc_html_e( 'Automatic compression and WebP conversion', 'smartsite-optimizer' ); ?></p>
			</div>
		</div>
	</div>
</div>
