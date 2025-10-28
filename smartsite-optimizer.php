<?php
/**
 * Plugin Name: SmartSite Optimizer
 * Plugin URI: https://wordpress.org/plugins/smartsite-optimizer/
 * Description: A comprehensive all-in-one performance and user experience optimization solution with intelligent asset management, advanced caching, real-time analytics, and automated optimization features.
 * Version: 1.0.0
 * Author: Kamran Mehmood
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smartsite-optimizer
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSO_VERSION', '1.0.0' );
define( 'SSO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSO_PLUGIN_FILE', __FILE__ );
define( 'SSO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SSO_PLUGIN_DIR . 'includes/class-autoloader.php';

/**
 * Main plugin class.
 */
final class SmartSite_Optimizer {

	/**
	 * Plugin instance.
	 *
	 * @var SmartSite_Optimizer
	 */
	private static $instance = null;

	/**
	 * Asset manager instance.
	 *
	 * @var \SmartSiteOptimizer\Modules\Asset_Manager
	 */
	public $asset_manager;

	/**
	 * Cache manager instance.
	 *
	 * @var \SmartSiteOptimizer\Modules\Cache_Manager
	 */
	public $cache_manager;

	/**
	 * Analytics instance.
	 *
	 * @var \SmartSiteOptimizer\Modules\Analytics
	 */
	public $analytics;

	/**
	 * Image optimizer instance.
	 *
	 * @var \SmartSiteOptimizer\Modules\Image_Optimizer
	 */
	public $image_optimizer;

	/**
	 * Get plugin instance.
	 *
	 * @return SmartSite_Optimizer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		Autoloader::register();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		register_activation_hook( SSO_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( SSO_PLUGIN_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		if ( ! $this->check_requirements() ) {
			return;
		}

		$this->load_modules();
		$this->load_admin();
		$this->load_cli();

		do_action( 'sso_loaded' );
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return bool
	 */
	private function check_requirements() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Load plugin modules.
	 */
	private function load_modules() {
		$options = get_option( 'sso_options', array() );

		if ( ! isset( $options['disable_asset_manager'] ) || ! $options['disable_asset_manager'] ) {
			$this->asset_manager = new Modules\Asset_Manager();
		}

		if ( ! isset( $options['disable_cache'] ) || ! $options['disable_cache'] ) {
			$this->cache_manager = new Modules\Cache_Manager();
		}

		if ( ! isset( $options['disable_analytics'] ) || ! $options['disable_analytics'] ) {
			$this->analytics = new Modules\Analytics();
		}

		if ( ! isset( $options['disable_image_optimizer'] ) || ! $options['disable_image_optimizer'] ) {
			$this->image_optimizer = new Modules\Image_Optimizer();
		}

		do_action( 'sso_modules_loaded' );
	}

	/**
	 * Load admin interface.
	 */
	private function load_admin() {
		if ( is_admin() ) {
			new Admin\Admin_Init();
		}
	}

	/**
	 * Load WP-CLI commands.
	 */
	private function load_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'sso cache', 'SmartSiteOptimizer\CLI\Cache_Command' );
			\WP_CLI::add_command( 'sso optimize', 'SmartSiteOptimizer\CLI\Optimize_Command' );
		}
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		require_once SSO_PLUGIN_DIR . 'includes/class-activator.php';
		Activator::activate();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		require_once SSO_PLUGIN_DIR . 'includes/class-deactivator.php';
		Deactivator::deactivate();
	}

	/**
	 * PHP version notice.
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'SmartSite Optimizer requires PHP 7.4 or higher.', 'smartsite-optimizer' ); ?></p>
		</div>
		<?php
	}

	/**
	 * WordPress version notice.
	 */
	public function wp_version_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'SmartSite Optimizer requires WordPress 5.8 or higher.', 'smartsite-optimizer' ); ?></p>
		</div>
		<?php
	}
}

/**
 * Get main plugin instance.
 *
 * @return SmartSite_Optimizer
 */
function sso() {
	return SmartSite_Optimizer::get_instance();
}

sso();