<?php
/**
 * WP-CLI Cache Commands.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Command class.
 */
class Cache_Command {

	/**
	 * Clear all caches.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sso cache clear
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function clear( $args, $assoc_args ) {
		$cache_manager = \SmartSiteOptimizer\sso()->cache_manager;

		if ( ! $cache_manager ) {
			\WP_CLI::error( 'Cache manager is not available.' );
		}

		\WP_CLI::log( 'Clearing all caches...' );
		$cache_manager->clear_all_cache();
		\WP_CLI::success( 'All caches cleared successfully.' );
	}

	/**
	 * Clear page cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sso cache clear-pages
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function clear_pages( $args, $assoc_args ) {
		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/smartsite-optimizer/cache/pages';

		if ( ! is_dir( $cache_dir ) ) {
			\WP_CLI::error( 'Page cache directory does not exist.' );
		}

		\WP_CLI::log( 'Clearing page cache...' );

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $cache_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		$count = 0;
		foreach ( $files as $file ) {
			if ( $file->isFile() && $file->getFilename() !== '.htaccess' ) {
				// Use WordPress function for file deletion
				wp_delete_file( $file->getRealPath() );
				$count++;
			}
		}

		\WP_CLI::success( sprintf( 'Cleared %d cached pages.', $count ) );
	}

	/**
	 * Get cache statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sso cache stats
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function stats( $args, $assoc_args ) {
		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/smartsite-optimizer/cache';

		$stats = array(
			'page_cache_files' => 0,
			'cache_size'       => 0,
		);

		if ( is_dir( $cache_dir ) ) {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $cache_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
			);

			foreach ( $files as $file ) {
				if ( $file->isFile() ) {
					$stats['page_cache_files']++;
					$stats['cache_size'] += $file->getSize();
				}
			}
		}

		\WP_CLI::log( sprintf( 'Cached pages: %d', $stats['page_cache_files'] ) );
		\WP_CLI::log( sprintf( 'Total cache size: %s', size_format( $stats['cache_size'] ) ) );
	}
}