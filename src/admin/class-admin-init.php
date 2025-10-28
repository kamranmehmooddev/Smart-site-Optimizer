<?php
/**
 * Admin interface initialization.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Init class.
 */
class Admin_Init {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_sso_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_sso_run_optimization', array( $this, 'ajax_run_optimization' ) );
		add_action( 'wp_ajax_sso_get_dashboard_data', array( $this, 'ajax_get_dashboard_data' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_menu_pages() {
		add_menu_page(
			__( 'SmartSite Optimizer', 'smartsite-optimizer' ),
			__( 'SmartSite', 'smartsite-optimizer' ),
			'manage_options',
			'smartsite-optimizer',
			array( $this, 'render_dashboard_page' ),
			'dashicons-performance',
			66
		);

		add_submenu_page(
			'smartsite-optimizer',
			__( 'Dashboard', 'smartsite-optimizer' ),
			__( 'Dashboard', 'smartsite-optimizer' ),
			'manage_options',
			'smartsite-optimizer',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'smartsite-optimizer',
			__( 'Asset Manager', 'smartsite-optimizer' ),
			__( 'Asset Manager', 'smartsite-optimizer' ),
			'manage_options',
			'sso-asset-manager',
			array( $this, 'render_asset_manager_page' )
		);

		add_submenu_page(
			'smartsite-optimizer',
			__( 'Cache Settings', 'smartsite-optimizer' ),
			__( 'Cache', 'smartsite-optimizer' ),
			'manage_options',
			'sso-cache',
			array( $this, 'render_cache_page' )
		);

		add_submenu_page(
			'smartsite-optimizer',
			__( 'Analytics', 'smartsite-optimizer' ),
			__( 'Analytics', 'smartsite-optimizer' ),
			'manage_options',
			'sso-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'smartsite-optimizer',
			__( 'Image Optimizer', 'smartsite-optimizer' ),
			__( 'Images', 'smartsite-optimizer' ),
			'manage_options',
			'sso-images',
			array( $this, 'render_images_page' )
		);

		add_submenu_page(
			'smartsite-optimizer',
			__( 'Settings', 'smartsite-optimizer' ),
			__( 'Settings', 'smartsite-optimizer' ),
			'manage_options',
			'sso-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'smartsite-optimizer' ) === false && strpos( $hook, 'sso-' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'sso-admin',
			SSO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SSO_VERSION
		);

		// Use local Chart.js instead of CDN for WordPress.org compliance
		wp_enqueue_script(
			'chart-js',
			SSO_PLUGIN_URL . 'assets/js/chart.min.js',
			array(),
			'3.9.1',
			true
		);

		wp_enqueue_script(
			'sso-admin',
			SSO_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'chart-js' ),
			SSO_VERSION,
			true
		);

		wp_localize_script(
			'sso-admin',
			'ssoAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sso_admin' ),
			)
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 
			'sso_options', 
			'sso_options', 
			array(
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);
	}

	/**
	 * Sanitize plugin options.
	 *
	 * @param array $input The input options.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		// Sanitize analytics options
		if ( isset( $input['analytics'] ) && is_array( $input['analytics'] ) ) {
			$sanitized['analytics'] = array(
				'enabled'             => ! empty( $input['analytics']['enabled'] ),
				'track_web_vitals'    => ! empty( $input['analytics']['track_web_vitals'] ),
				'track_user_behavior' => ! empty( $input['analytics']['track_user_behavior'] ),
				'retention_days'      => absint( $input['analytics']['retention_days'] ?? 90 ),
			);
		}

		// Sanitize cache options
		if ( isset( $input['cache'] ) && is_array( $input['cache'] ) ) {
			$sanitized['cache'] = array(
				'page_cache_enabled' => ! empty( $input['cache']['page_cache_enabled'] ),
				'browser_cache'      => ! empty( $input['cache']['browser_cache'] ),
				'cache_logged_in'    => ! empty( $input['cache']['cache_logged_in'] ),
				'cache_ttl'          => absint( $input['cache']['cache_ttl'] ?? 3600 ),
				'exclude_urls'       => $this->sanitize_url_patterns( $input['cache']['exclude_urls'] ?? array() ),
			);
		}

		// Sanitize asset manager options
		if ( isset( $input['asset_manager'] ) && is_array( $input['asset_manager'] ) ) {
			$sanitized['asset_manager'] = array(
				'defer_js'           => ! empty( $input['asset_manager']['defer_js'] ),
				'defer_css'          => ! empty( $input['asset_manager']['defer_css'] ),
				'critical_css'       => ! empty( $input['asset_manager']['critical_css'] ),
				'lazy_load_images'   => ! empty( $input['asset_manager']['lazy_load_images'] ),
				'lazy_load_iframes'  => ! empty( $input['asset_manager']['lazy_load_iframes'] ),
				'font_display_swap'  => ! empty( $input['asset_manager']['font_display_swap'] ),
				'preload_fonts'      => $this->sanitize_urls( $input['asset_manager']['preload_fonts'] ?? array() ),
			);
		}

		// Sanitize image optimizer options
		if ( isset( $input['image_optimizer'] ) && is_array( $input['image_optimizer'] ) ) {
			$sanitized['image_optimizer'] = array(
				'auto_optimize'        => ! empty( $input['image_optimizer']['auto_optimize'] ),
				'webp_conversion'      => ! empty( $input['image_optimizer']['webp_conversion'] ),
				'compression_quality'  => absint( $input['image_optimizer']['compression_quality'] ?? 80 ),
				'max_width'            => absint( $input['image_optimizer']['max_width'] ?? 2048 ),
				'max_height'           => absint( $input['image_optimizer']['max_height'] ?? 2048 ),
			);
		}

		// Sanitize module disable options
		$sanitized['disable_asset_manager']   = ! empty( $input['disable_asset_manager'] );
		$sanitized['disable_cache']           = ! empty( $input['disable_cache'] );
		$sanitized['disable_analytics']       = ! empty( $input['disable_analytics'] );
		$sanitized['disable_image_optimizer'] = ! empty( $input['disable_image_optimizer'] );

		return $sanitized;
	}

	/**
	 * Sanitize URL patterns.
	 *
	 * @param array $patterns URL patterns.
	 * @return array Sanitized patterns.
	 */
	private function sanitize_url_patterns( $patterns ) {
		if ( ! is_array( $patterns ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $patterns as $pattern ) {
			$clean_pattern = sanitize_text_field( $pattern );
			if ( ! empty( $clean_pattern ) ) {
				$sanitized[] = $clean_pattern;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize URLs.
	 *
	 * @param array $urls URLs to sanitize.
	 * @return array Sanitized URLs.
	 */
	private function sanitize_urls( $urls ) {
		if ( ! is_array( $urls ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $urls as $url ) {
			$clean_url = esc_url_raw( $url );
			if ( ! empty( $clean_url ) ) {
				$sanitized[] = $clean_url;
			}
		}

		return $sanitized;
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page() {
		include SSO_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Render asset manager page.
	 */
	public function render_asset_manager_page() {
		include SSO_PLUGIN_DIR . 'templates/admin/asset-manager.php';
	}

	/**
	 * Render cache page.
	 */
	public function render_cache_page() {
		include SSO_PLUGIN_DIR . 'templates/admin/cache.php';
	}

	/**
	 * Render analytics page.
	 */
	public function render_analytics_page() {
		include SSO_PLUGIN_DIR . 'templates/admin/analytics.php';
	}

	/**
	 * Render images page.
	 */
	public function render_images_page() {
		include SSO_PLUGIN_DIR . 'templates/admin/images.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		include SSO_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * AJAX: Clear cache.
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'sso_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$cache_manager = \SmartSiteOptimizer\sso()->cache_manager;

		if ( $cache_manager ) {
			$cache_manager->clear_all_cache();
			wp_send_json_success( 'Cache cleared successfully' );
		}

		wp_send_json_error( 'Cache manager not available' );
	}

	/**
	 * AJAX: Run one-click optimization.
	 */
	public function ajax_run_optimization() {
		check_ajax_referer( 'sso_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$current_options = get_option( 'sso_options', array() );
		
		$optimized_options = array(
			'analytics' => array(
				'enabled' => true,
				'track_web_vitals' => true,
				'track_user_behavior' => true,
			),
			'cache' => array(
				'page_cache_enabled' => true,
				'browser_cache' => true,
				'cache_ttl' => 3600,
			),
			'asset_manager' => array(
				'defer_js' => true,
				'defer_css' => true,
				'lazy_load_images' => true,
				'font_display_swap' => true,
			),
			'image_optimizer' => array(
				'auto_optimize' => true,
				'webp_conversion' => true,
				'compression_quality' => 85,
			),
		);

		$merged_options = array_replace_recursive( $current_options, $optimized_options );
		update_option( 'sso_options', $merged_options );

		$results = array(
			'cache_cleared'  => false,
			'settings_updated' => true,
		);

		$cache_manager = \SmartSiteOptimizer\sso()->cache_manager;
		if ( $cache_manager ) {
			$cache_manager->clear_all_cache();
			$results['cache_cleared'] = true;
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Get dashboard data.
	 */
	public function ajax_get_dashboard_data() {
		check_ajax_referer( 'sso_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$analytics = \SmartSiteOptimizer\sso()->analytics;
		$data = array();

		if ( $analytics ) {
			$data['vitals'] = $analytics->get_analytics_data( '', 7 );
			$data['top_pages'] = $analytics->get_top_pages( 5 );
		}

		wp_send_json_success( $data );
	}
}