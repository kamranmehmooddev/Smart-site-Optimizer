<?php
/**
 * Analytics template.
 *
 * @package SmartSiteOptimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap sso-analytics-page">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="sso-analytics-filters">
		<select id="sso-analytics-period">
			<option value="7"><?php esc_html_e( 'Last 7 days', 'smartsite-optimizer' ); ?></option>
			<option value="30" selected><?php esc_html_e( 'Last 30 days', 'smartsite-optimizer' ); ?></option>
			<option value="90"><?php esc_html_e( 'Last 90 days', 'smartsite-optimizer' ); ?></option>
		</select>
	</div>

	<div class="sso-vitals-cards">
		<div class="sso-vital-card">
			<h3><?php esc_html_e( 'LCP', 'smartsite-optimizer' ); ?></h3>
			<p class="sso-vital-value" id="sso-lcp-value">--</p>
			<p class="sso-vital-label"><?php esc_html_e( 'Largest Contentful Paint', 'smartsite-optimizer' ); ?></p>
		</div>
		<div class="sso-vital-card">
			<h3><?php esc_html_e( 'FID', 'smartsite-optimizer' ); ?></h3>
			<p class="sso-vital-value" id="sso-fid-value">--</p>
			<p class="sso-vital-label"><?php esc_html_e( 'First Input Delay', 'smartsite-optimizer' ); ?></p>
		</div>
		<div class="sso-vital-card">
			<h3><?php esc_html_e( 'CLS', 'smartsite-optimizer' ); ?></h3>
			<p class="sso-vital-value" id="sso-cls-value">--</p>
			<p class="sso-vital-label"><?php esc_html_e( 'Cumulative Layout Shift', 'smartsite-optimizer' ); ?></p>
		</div>
		<div class="sso-vital-card">
			<h3><?php esc_html_e( 'TTFB', 'smartsite-optimizer' ); ?></h3>
			<p class="sso-vital-value" id="sso-ttfb-value">--</p>
			<p class="sso-vital-label"><?php esc_html_e( 'Time to First Byte', 'smartsite-optimizer' ); ?></p>
		</div>
	</div>

	<div class="sso-chart-container">
		<h3><?php esc_html_e( 'Performance Trends', 'smartsite-optimizer' ); ?></h3>
		<canvas id="sso-performance-chart"></canvas>
	</div>

	<div class="sso-pages-table">
		<h3><?php esc_html_e( 'Top Pages by Performance', 'smartsite-optimizer' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Page URL', 'smartsite-optimizer' ); ?></th>
					<th><?php esc_html_e( 'Avg Score', 'smartsite-optimizer' ); ?></th>
					<th><?php esc_html_e( 'Views', 'smartsite-optimizer' ); ?></th>
				</tr>
			</thead>
			<tbody id="sso-pages-tbody">
				<tr>
					<td colspan="3"><?php esc_html_e( 'Loading...', 'smartsite-optimizer' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
