<?php
/**
 * WP-CLI Optimize Commands.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optimize Command class.
 */
class Optimize_Command {

	/**
	 * Optimize all images.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Number of images to optimize. Default: all
	 *
	 * ## EXAMPLES
	 *
	 *     wp sso optimize images
	 *     wp sso optimize images --limit=50
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function images( $args, $assoc_args ) {
		$image_optimizer = \SmartSiteOptimizer\sso()->image_optimizer;

		if ( ! $image_optimizer ) {
			\WP_CLI::error( 'Image optimizer is not available.' );
		}

		$limit = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : -1;

		$query_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/gif' ),
			'posts_per_page' => $limit,
			'post_status'    => 'any',
		);

		$attachments = get_posts( $query_args );

		if ( empty( $attachments ) ) {
			\WP_CLI::warning( 'No images found to optimize.' );
			return;
		}

		\WP_CLI::log( sprintf( 'Found %d images to optimize...', count( $attachments ) ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Optimizing images', count( $attachments ) );

		$attachment_ids = wp_list_pluck( $attachments, 'ID' );
		$results = $image_optimizer->bulk_optimize( $attachment_ids );

		$progress->finish();

		\WP_CLI::success( sprintf(
			'Optimization complete. Success: %d, Failed: %d, Skipped: %d',
			$results['success'],
			$results['failed'],
			$results['skipped']
		) );
	}

	/**
	 * Run all optimizations.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sso optimize all
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function all( $args, $assoc_args ) {
		\WP_CLI::log( 'Running all optimizations...' );

		\WP_CLI::runcommand( 'sso cache clear' );
		\WP_CLI::runcommand( 'sso optimize images --limit=100' );

		\WP_CLI::success( 'All optimizations completed.' );
	}
}
