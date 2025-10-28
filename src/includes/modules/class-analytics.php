<?php
/**
 * Analytics Module.
 *
 * Handles Core Web Vitals monitoring, performance tracking,
 * and user behavior metrics.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analytics class.
 */
class Analytics {

	/**
	 * Options array.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$all_options = get_option( 'sso_options', array() );
		$this->options = isset( $all_options['analytics'] ) ? $all_options['analytics'] : array();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( ! isset( $this->options['enabled'] ) || $this->options['enabled'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracking_script' ) );
			add_action( 'wp_ajax_sso_track_metric', array( $this, 'track_metric' ) );
			add_action( 'wp_ajax_nopriv_sso_track_metric', array( $this, 'track_metric' ) );
			add_action( 'wp_ajax_sso_track_web_vitals', array( $this, 'track_web_vitals' ) );
			add_action( 'wp_ajax_nopriv_sso_track_web_vitals', array( $this, 'track_web_vitals' ) );
		}

		add_action( 'sso_cleanup_analytics', array( $this, 'cleanup_old_data' ) );
	}

	/**
	 * Enqueue tracking script.
	 */
	public function enqueue_tracking_script() {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script(
			'sso-analytics',
			SSO_PLUGIN_URL . 'assets/js/analytics.js',
			array(),
			SSO_VERSION,
			true
		);

		wp_localize_script(
			'sso-analytics',
			'ssoAnalytics',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'trackVitals'   => ! isset( $this->options['track_web_vitals'] ) || $this->options['track_web_vitals'],
				'trackBehavior' => ! isset( $this->options['track_user_behavior'] ) || $this->options['track_user_behavior'],
				'nonce'         => wp_create_nonce( 'sso_analytics' ),
			)
		);
	}

	/**
	 * Track metric via AJAX.
	 */
	public function track_metric() {
		check_ajax_referer( 'sso_analytics', 'nonce' );

		$metric_type = isset( $_POST['metric_type'] ) ? sanitize_text_field( wp_unslash( $_POST['metric_type'] ) ) : '';
		$metric_value = isset( $_POST['metric_value'] ) ? floatval( $_POST['metric_value'] ) : 0;
		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

		if ( empty( $metric_type ) || empty( $page_url ) ) {
			wp_send_json_error( 'Invalid parameters' );
		}

		// Direct database query is necessary for analytics data insertion.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$wpdb->prefix . 'sso_analytics',
			array(
				'page_url'     => $page_url,
				'metric_type'  => $metric_type,
				'metric_value' => $metric_value,
				'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'ip_address'   => $this->get_user_ip(),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%f', '%s', '%s', '%s' )
		);

		if ( $result ) {
			wp_send_json_success( 'Metric tracked' );
		} else {
			wp_send_json_error( 'Failed to track metric' );
		}
	}

	/**
	 * Track Core Web Vitals.
	 */
	public function track_web_vitals() {
		check_ajax_referer( 'sso_analytics', 'nonce' );

		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		$lcp = isset( $_POST['lcp'] ) ? floatval( $_POST['lcp'] ) : null;
		$fid = isset( $_POST['fid'] ) ? floatval( $_POST['fid'] ) : null;
		$cls = isset( $_POST['cls'] ) ? floatval( $_POST['cls'] ) : null;
		$ttfb = isset( $_POST['ttfb'] ) ? floatval( $_POST['ttfb'] ) : null;
		$fcp = isset( $_POST['fcp'] ) ? floatval( $_POST['fcp'] ) : null;

		if ( empty( $page_url ) ) {
			wp_send_json_error( 'Invalid parameters' );
		}

		$score = $this->calculate_performance_score( $lcp, $fid, $cls );
		$device_type = wp_is_mobile() ? 'mobile' : 'desktop';

		// Direct database query is necessary for web vitals data insertion.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$wpdb->prefix . 'sso_web_vitals',
			array(
				'page_url'    => $page_url,
				'lcp'         => $lcp,
				'fid'         => $fid,
				'cls'         => $cls,
				'ttfb'        => $ttfb,
				'fcp'         => $fcp,
				'score'       => $score,
				'device_type' => $device_type,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%f', '%f', '%f', '%f', '%f', '%d', '%s', '%s' )
		);

		if ( $result ) {
			wp_send_json_success( 'Web vitals tracked' );
		} else {
			wp_send_json_error( 'Failed to track web vitals' );
		}
	}

	/**
	 * Calculate performance score based on Core Web Vitals.
	 *
	 * @param float $lcp Largest Contentful Paint.
	 * @param float $fid First Input Delay.
	 * @param float $cls Cumulative Layout Shift.
	 * @return int
	 */
	private function calculate_performance_score( $lcp, $fid, $cls ) {
		$score = 100;

		if ( $lcp > 2500 ) {
			$score -= 40;
		} elseif ( $lcp > 4000 ) {
			$score -= 60;
		}

		if ( $fid > 100 ) {
			$score -= 20;
		} elseif ( $fid > 300 ) {
			$score -= 40;
		}

		if ( $cls > 0.1 ) {
			$score -= 20;
		} elseif ( $cls > 0.25 ) {
			$score -= 40;
		}

		return max( 0, $score );
	}

	/**
	 * Get analytics data for dashboard.
	 *
	 * @param string $page_url Page URL filter.
	 * @param int    $days     Number of days to retrieve.
	 * @return array
	 */
	public function get_analytics_data( $page_url = '', $days = 30 ) {
		$cache_key = 'sso_analytics_data_' . md5( $page_url . '_' . $days );
		$cached_data = wp_cache_get( $cache_key, 'sso' );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		// Direct database query is necessary for complex analytics data retrieval.
		if ( ! empty( $page_url ) ) {
			// Query with page_url filter.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$vitals = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						AVG(lcp) as avg_lcp,
						AVG(fid) as avg_fid,
						AVG(cls) as avg_cls,
						AVG(ttfb) as avg_ttfb,
						AVG(fcp) as avg_fcp,
						AVG(score) as avg_score,
						COUNT(*) as total_records,
						DATE(created_at) as date
					FROM {$wpdb->prefix}sso_web_vitals
					WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
					AND page_url = %s
					GROUP BY DATE(created_at)
					ORDER BY created_at DESC",
					$days,
					$page_url
				)
			);
		} else {
			// Query without page_url filter.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$vitals = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						AVG(lcp) as avg_lcp,
						AVG(fid) as avg_fid,
						AVG(cls) as avg_cls,
						AVG(ttfb) as avg_ttfb,
						AVG(fcp) as avg_fcp,
						AVG(score) as avg_score,
						COUNT(*) as total_records,
						DATE(created_at) as date
					FROM {$wpdb->prefix}sso_web_vitals
					WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
					GROUP BY DATE(created_at)
					ORDER BY created_at DESC",
					$days
				)
			);
		}

		// Cache the results for 15 minutes.
		wp_cache_set( $cache_key, $vitals, 'sso', 900 );

		return $vitals;
	}

	/**
	 * Get top pages by performance.
	 *
	 * @param int $limit Number of results.
	 * @return array
	 */
	public function get_top_pages( $limit = 10 ) {
		$cache_key = 'sso_top_pages_' . $limit;
		$cached_data = wp_cache_get( $cache_key, 'sso' );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		global $wpdb;

		// Direct database query is necessary for analytics reporting.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
						page_url,
						AVG(score) as avg_score,
						COUNT(*) as views
				FROM {$wpdb->prefix}sso_web_vitals
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
				GROUP BY page_url
				ORDER BY avg_score DESC
				LIMIT %d",
				$limit
			)
		);

		// Cache the results for 15 minutes.
		wp_cache_set( $cache_key, $pages, 'sso', 900 );

		return $pages;
	}

	/**
	 * Cleanup old analytics data.
	 */
	public function cleanup_old_data() {
		$retention_days = ! empty( $this->options['retention_days'] ) ? intval( $this->options['retention_days'] ) : 90;

		global $wpdb;

		// Direct database queries are necessary for data cleanup operations.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}sso_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$retention_days
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}sso_web_vitals WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$retention_days
			)
		);

		// Clear relevant caches after cleanup.
		wp_cache_delete( 'sso_analytics_data_', 'sso' );
		wp_cache_delete( 'sso_top_pages_', 'sso' );

		do_action( 'sso_analytics_cleaned_up' );
	}

	/**
	 * Get user IP address.
	 *
	 * @return string
	 */
	private function get_user_ip() {
		$ip = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Custom event tracking API.
	 *
	 * @param string $event_name Event name.
	 * @param mixed  $event_data Event data.
	 * @return bool
	 */
	public function track_event( $event_name, $event_data = array() ) {
		do_action( 'sso_before_track_event', $event_name, $event_data );

		global $wpdb;

		// Direct database query is necessary for custom event tracking.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$wpdb->prefix . 'sso_analytics',
			array(
				'page_url'     => is_array( $event_data ) && isset( $event_data['url'] ) ? $event_data['url'] : '',
				'metric_type'  => 'custom_event_' . $event_name,
				'metric_value' => is_array( $event_data ) && isset( $event_data['value'] ) ? floatval( $event_data['value'] ) : 1,
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%f', '%s' )
		);

		do_action( 'sso_after_track_event', $event_name, $event_data, $result );

		return (bool) $result;
	}
}