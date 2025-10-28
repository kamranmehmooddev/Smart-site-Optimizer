<?php
/**
 * Plugin activation handler.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator class.
 */
class Activator {

	/**
	 * Activation handler.
	 */
	public static function activate() {
		self::create_tables();
		self::set_default_options();
		self::create_directories();
		self::schedule_events();

		flush_rewrite_rules();

		update_option( 'sso_version', SSO_VERSION );
		update_option( 'sso_activation_time', current_time( 'timestamp' ) );
	}

	/**
	 * Create database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$analytics_table = $wpdb->prefix . 'sso_analytics';
		$cache_table = $wpdb->prefix . 'sso_cache';
		$vitals_table = $wpdb->prefix . 'sso_web_vitals';

		$sql = array();

		$sql[] = "CREATE TABLE IF NOT EXISTS {$analytics_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_url varchar(255) NOT NULL,
			metric_type varchar(50) NOT NULL,
			metric_value decimal(10,2) NOT NULL,
			user_agent varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY page_url (page_url),
			KEY metric_type (metric_type),
			KEY created_at (created_at)
		) $charset_collate;";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$cache_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			cache_key varchar(255) NOT NULL,
			cache_value longtext NOT NULL,
			cache_group varchar(50) DEFAULT 'default',
			expiration bigint(20) UNSIGNED NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY cache_key (cache_key, cache_group),
			KEY expiration (expiration)
		) $charset_collate;";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$vitals_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_url varchar(255) NOT NULL,
			lcp decimal(10,2) DEFAULT NULL,
			fid decimal(10,2) DEFAULT NULL,
			cls decimal(10,4) DEFAULT NULL,
			ttfb decimal(10,2) DEFAULT NULL,
			fcp decimal(10,2) DEFAULT NULL,
			score int(3) DEFAULT NULL,
			device_type varchar(20) DEFAULT 'desktop',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY page_url (page_url),
			KEY created_at (created_at),
			KEY device_type (device_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
	}

	/**
	 * Set default options.
	 */
	private static function set_default_options() {
		$defaults = array(
			'asset_manager' => array(
				'defer_js' => true,
				'defer_css' => true,
				'critical_css' => false,
				'lazy_load_images' => true,
				'lazy_load_iframes' => true,
				'font_display_swap' => true,
				'preload_fonts' => array(),
			),
			'cache' => array(
				'page_cache_enabled' => true,
				'cache_ttl' => 3600,
				'exclude_urls' => array(),
				'cache_logged_in' => false,
				'browser_cache' => true,
			),
			'analytics' => array(
				'enabled' => true,
				'track_web_vitals' => true,
				'track_user_behavior' => true,
				'retention_days' => 90,
			),
			'image_optimizer' => array(
				'auto_optimize' => true,
				'webp_conversion' => true,
				'compression_quality' => 80,
				'max_width' => 2048,
				'max_height' => 2048,
			),
			'minification' => array(
				'minify_html' => true,
				'minify_css' => true,
				'minify_js' => true,
			),
			'dns_prefetch' => array(
				'enabled' => true,
				'domains' => array(),
			),
		);

		add_option( 'sso_options', $defaults );
	}

	/**
	 * Create plugin directories.
	 */
	private static function create_directories() {
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'] . '/smartsite-optimizer';

		$directories = array(
			$base_dir,
			$base_dir . '/cache',
			$base_dir . '/cache/pages',
			$base_dir . '/cache/objects',
			$base_dir . '/optimized-images',
		);

		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );

				$htaccess = $dir . '/.htaccess';
				if ( ! file_exists( $htaccess ) ) {
					file_put_contents( $htaccess, 'Deny from all' );
				}
			}
		}
	}

	/**
	 * Schedule cron events.
	 */
	private static function schedule_events() {
		if ( ! wp_next_scheduled( 'sso_clear_expired_cache' ) ) {
			wp_schedule_event( time(), 'hourly', 'sso_clear_expired_cache' );
		}

		if ( ! wp_next_scheduled( 'sso_cleanup_analytics' ) ) {
			wp_schedule_event( time(), 'daily', 'sso_cleanup_analytics' );
		}
	}
}
