<?php
/**
 * Plugin deactivation handler.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivator class.
 */
class Deactivator {

	/**
	 * Deactivation handler.
	 */
	public static function deactivate() {
		self::clear_scheduled_events();
		self::flush_cache();

		flush_rewrite_rules();
	}

	/**
	 * Clear scheduled cron events.
	 */
	private static function clear_scheduled_events() {
		$events = array(
			'sso_clear_expired_cache',
			'sso_cleanup_analytics',
		);

		foreach ( $events as $event ) {
			$timestamp = wp_next_scheduled( $event );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event );
			}
		}
	}

	/**
	 * Flush all caches.
	 */
	private static function flush_cache() {
		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/smartsite-optimizer/cache';

		if ( file_exists( $cache_dir ) ) {
			self::delete_directory_contents( $cache_dir );
		}

		wp_cache_flush();
	}

	/**
	 * Delete directory contents recursively.
	 *
	 * @param string $dir Directory path.
	 */
	private static function delete_directory_contents( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );

		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			if ( is_dir( $path ) ) {
				self::delete_directory_contents( $path );
			} elseif ( $file !== '.htaccess' ) {
				// Use WordPress function for file deletion
				wp_delete_file( $path );
			}
		}
	}
}