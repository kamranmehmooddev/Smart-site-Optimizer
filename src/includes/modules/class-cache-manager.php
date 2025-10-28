<?php
/**
 * Cache Manager Module.
 *
 * Handles page caching, database query caching, object caching,
 * and browser cache headers.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Manager class.
 */
class Cache_Manager {

	/**
	 * Options array.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Cache directory.
	 *
	 * @var string
	 */
	private $cache_dir;

	/**
	 * WP_Filesystem instance.
	 *
	 * @var \WP_Filesystem_Base
	 */
	private $filesystem;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$all_options = get_option( 'sso_options', array() );
		$this->options = isset( $all_options['cache'] ) ? $all_options['cache'] : array();

		$upload_dir = wp_upload_dir();
		$this->cache_dir = $upload_dir['basedir'] . '/smartsite-optimizer/cache';

		$this->init_filesystem();
		$this->init_hooks();
	}

	/**
	 * Initialize WP_Filesystem.
	 */
	private function init_filesystem() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->filesystem = $wp_filesystem;
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( ! isset( $this->options['page_cache_enabled'] ) || $this->options['page_cache_enabled'] ) {
			add_action( 'template_redirect', array( $this, 'serve_cached_page' ), 1 );
			add_action( 'template_redirect', array( $this, 'start_output_buffering' ), 2 );
			add_action( 'shutdown', array( $this, 'cache_page' ), 999 );
		}

		if ( ! isset( $this->options['browser_cache'] ) || $this->options['browser_cache'] ) {
			add_action( 'send_headers', array( $this, 'set_browser_cache_headers' ) );
		}

		add_action( 'save_post', array( $this, 'clear_post_cache' ) );
		add_action( 'deleted_post', array( $this, 'clear_post_cache' ) );
		add_action( 'switch_theme', array( $this, 'clear_all_cache' ) );
		add_action( 'sso_clear_expired_cache', array( $this, 'clear_expired_cache' ) );

		add_filter( 'posts_pre_query', array( $this, 'maybe_cache_query' ), 10, 2 );
	}

	/**
	 * Serve cached page if available.
	 */
	public function serve_cached_page() {
		if ( ! $this->should_cache() ) {
			return;
		}

		$cache_key = $this->get_cache_key();
		$cache_file = $this->get_cache_file( $cache_key );

		if ( $this->filesystem->exists( $cache_file ) ) {
			$cache_time = $this->filesystem->mtime( $cache_file );
			$ttl = ! empty( $this->options['cache_ttl'] ) ? $this->options['cache_ttl'] : 3600;

			if ( ( time() - $cache_time ) < $ttl ) {
				header( 'X-SSO-Cache: HIT' );
				header( 'X-SSO-Cache-Time: ' . gmdate( 'Y-m-d H:i:s', $cache_time ) );

				// Use WP_Filesystem to read file content
				$cached_content = $this->filesystem->get_contents( $cache_file );
				if ( false !== $cached_content ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $cached_content;
					exit;
				}
			} else {
				$this->filesystem->delete( $cache_file );
			}
		}

		header( 'X-SSO-Cache: MISS' );
	}

	/**
	 * Start output buffering to capture page content.
	 */
	public function start_output_buffering() {
		if ( ! $this->should_cache() ) {
			return;
		}

		ob_start();
	}

	/**
	 * Cache current page output.
	 */
	public function cache_page() {
		if ( ! $this->should_cache() ) {
			return;
		}

		$output = ob_get_clean();

		if ( empty( $output ) || strlen( $output ) < 255 ) {
			// Escaping HTML output for security
			echo wp_kses_post( $output );
			return;
		}

		// Escaping HTML output for security
		echo wp_kses_post( $output );

		$cache_key = $this->get_cache_key();
		$cache_file = $this->get_cache_file( $cache_key );

		$cache_dir = dirname( $cache_file );
		if ( ! $this->filesystem->exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		// Use WP_Filesystem to write file content
		$this->filesystem->put_contents( $cache_file, $output, FS_CHMOD_FILE );

		do_action( 'sso_page_cached', $cache_key );
	}

	/**
	 * Check if current request should be cached.
	 *
	 * @return bool
	 */
	private function should_cache() {
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return false;
		}

		if ( isset( $_GET['preview'] ) || isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		if ( ! empty( $this->options['cache_logged_in'] ) && is_user_logged_in() ) {
			return false;
		}

		// Validate REQUEST_METHOD superglobal
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
			return false;
		}

		if ( $this->is_excluded_url() ) {
			return false;
		}

		return apply_filters( 'sso_should_cache', true );
	}

	/**
	 * Check if current URL is excluded from caching.
	 *
	 * @return bool
	 */
	private function is_excluded_url() {
		if ( empty( $this->options['exclude_urls'] ) ) {
			return false;
		}

		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		foreach ( $this->options['exclude_urls'] as $pattern ) {
			if ( fnmatch( $pattern, $current_url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get cache key for current request.
	 *
	 * @return string
	 */
	private function get_cache_key() {
		$url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$device = wp_is_mobile() ? 'mobile' : 'desktop';

		return md5( $url . $device );
	}

	/**
	 * Get cache file path.
	 *
	 * @param string $cache_key Cache key.
	 * @return string
	 */
	private function get_cache_file( $cache_key ) {
		return $this->cache_dir . '/pages/' . substr( $cache_key, 0, 2 ) . '/' . $cache_key . '.html';
	}

	/**
	 * Set browser cache headers.
	 */
	public function set_browser_cache_headers() {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}

		$expires = 31536000;

		if ( is_front_page() || is_home() ) {
			$expires = 3600;
		} elseif ( is_single() || is_page() ) {
			$expires = 86400;
		}

		header( 'Cache-Control: public, max-age=' . $expires );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires ) . ' GMT' );
	}

	/**
	 * Clear cache for specific post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear_post_cache( $post_id ) {
		$post_url = get_permalink( $post_id );
		$cache_key = md5( $post_url . 'desktop' );
		$cache_file = $this->get_cache_file( $cache_key );

		if ( $this->filesystem->exists( $cache_file ) ) {
			$this->filesystem->delete( $cache_file );
		}

		$cache_key_mobile = md5( $post_url . 'mobile' );
		$cache_file_mobile = $this->get_cache_file( $cache_key_mobile );

		if ( $this->filesystem->exists( $cache_file_mobile ) ) {
			$this->filesystem->delete( $cache_file_mobile );
		}

		do_action( 'sso_post_cache_cleared', $post_id );
	}

	/**
	 * Clear all cache.
	 */
	public function clear_all_cache() {
		$this->delete_directory_contents( $this->cache_dir . '/pages' );
		$this->delete_directory_contents( $this->cache_dir . '/objects' );

		global $wpdb;
		// Direct database query is necessary for cache cleanup operations.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sso_cache" );

		wp_cache_flush();

		do_action( 'sso_all_cache_cleared' );
	}

	/**
	 * Clear expired cache entries.
	 */
	public function clear_expired_cache() {
		global $wpdb;

		// Direct database query is necessary for expired cache cleanup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}sso_cache WHERE expiration < %d",
				time()
			)
		);

		do_action( 'sso_expired_cache_cleared' );
	}

	/**
	 * Maybe cache database query.
	 *
	 * @param mixed     $posts Posts array or null.
	 * @param \WP_Query $query WP_Query object.
	 * @return mixed
	 */
	public function maybe_cache_query( $posts, $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return $posts;
		}

		$cache_key = 'sso_query_' . md5( serialize( $query->query_vars ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$cached = wp_cache_get( $cache_key, 'sso' );

		if ( false !== $cached ) {
			return $cached;
		}

		return $posts;
	}

	/**
	 * Delete directory contents.
	 *
	 * @param string $dir Directory path.
	 */
	private function delete_directory_contents( $dir ) {
		if ( ! $this->filesystem->is_dir( $dir ) ) {
			return;
		}

		$files = $this->filesystem->dirlist( $dir, false, false );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file => $fileinfo ) {
			$path = $dir . '/' . $file;
			
			if ( 'd' === $fileinfo['type'] ) {
				// It's a directory, recurse into it
				$this->delete_directory_contents( $path );
				// Use WP_Filesystem to remove directory
				$this->filesystem->rmdir( $path );
			} elseif ( '.htaccess' !== $file ) {
				// It's a file, delete it
				$this->filesystem->delete( $path );
			}
		}
	}
}