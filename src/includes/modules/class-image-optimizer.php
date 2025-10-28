<?php
/**
 * Image Optimizer Module.
 *
 * Handles automatic image compression, WebP conversion,
 * and image optimization on upload.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Optimizer class.
 */
class Image_Optimizer {

	/**
	 * Options array.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Optimized images directory.
	 *
	 * @var string
	 */
	private $optimized_dir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$all_options = get_option( 'sso_options', array() );
		$this->options = isset( $all_options['image_optimizer'] ) ? $all_options['image_optimizer'] : array();

		$upload_dir = wp_upload_dir();
		$this->optimized_dir = $upload_dir['basedir'] . '/smartsite-optimizer/optimized-images';

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( ! isset( $this->options['auto_optimize'] ) || $this->options['auto_optimize'] ) {
			add_filter( 'wp_handle_upload', array( $this, 'optimize_on_upload' ) );
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'optimize_thumbnails' ), 10, 2 );
		}

		if ( ! isset( $this->options['webp_conversion'] ) || $this->options['webp_conversion'] ) {
			add_filter( 'wp_get_attachment_url', array( $this, 'maybe_serve_webp' ), 10, 2 );
		}
	}

	/**
	 * Optimize image on upload.
	 *
	 * @param array $upload Upload data.
	 * @return array
	 */
	public function optimize_on_upload( $upload ) {
		if ( ! isset( $upload['file'] ) || ! isset( $upload['type'] ) ) {
			return $upload;
		}

		if ( ! $this->is_supported_image_type( $upload['type'] ) ) {
			return $upload;
		}

		$optimized = $this->optimize_image( $upload['file'] );

		if ( $optimized && ! empty( $this->options['webp_conversion'] ) ) {
			$this->create_webp_version( $upload['file'] );
		}

		return $upload;
	}

	/**
	 * Optimize generated thumbnails.
	 *
	 * @param array $metadata Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function optimize_thumbnails( $metadata, $attachment_id ) {
		if ( ! isset( $metadata['file'] ) ) {
			return $metadata;
		}

		$upload_dir = wp_upload_dir();
		$base_file = $upload_dir['basedir'] . '/' . $metadata['file'];
		$base_dir = dirname( $base_file );

		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$thumbnail_file = $base_dir . '/' . $size_data['file'];

				if ( file_exists( $thumbnail_file ) ) {
					$this->optimize_image( $thumbnail_file );

					if ( ! empty( $this->options['webp_conversion'] ) ) {
						$this->create_webp_version( $thumbnail_file );
					}
				}
			}
		}

		return $metadata;
	}

	/**
	 * Optimize image file.
	 *
	 * @param string $file_path Image file path.
	 * @return bool
	 */
	private function optimize_image( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$image_type = exif_imagetype( $file_path );
		$quality = ! empty( $this->options['compression_quality'] ) ? intval( $this->options['compression_quality'] ) : 80;

		$image = null;

		switch ( $image_type ) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg( $file_path );
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng( $file_path );
				break;
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif( $file_path );
				break;
			default:
				return false;
		}

		if ( ! $image ) {
			return false;
		}

		$original_size = filesize( $file_path );

		$width = imagesx( $image );
		$height = imagesy( $image );

		$max_width = ! empty( $this->options['max_width'] ) ? intval( $this->options['max_width'] ) : 2048;
		$max_height = ! empty( $this->options['max_height'] ) ? intval( $this->options['max_height'] ) : 2048;

		if ( $width > $max_width || $height > $max_height ) {
			$ratio = min( $max_width / $width, $max_height / $height );
			$new_width = intval( $width * $ratio );
			$new_height = intval( $height * $ratio );

			$resized = imagecreatetruecolor( $new_width, $new_height );

			if ( $image_type === IMAGETYPE_PNG || $image_type === IMAGETYPE_GIF ) {
				imagealphablending( $resized, false );
				imagesavealpha( $resized, true );
				$transparent = imagecolorallocatealpha( $resized, 255, 255, 255, 127 );
				imagefilledrectangle( $resized, 0, 0, $new_width, $new_height, $transparent );
			}

			imagecopyresampled( $resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
			imagedestroy( $image );
			$image = $resized;
		}

		$temp_file = $file_path . '.tmp';

		switch ( $image_type ) {
			case IMAGETYPE_JPEG:
				imagejpeg( $image, $temp_file, $quality );
				break;
			case IMAGETYPE_PNG:
				$png_quality = intval( ( 100 - $quality ) / 10 );
				imagepng( $image, $temp_file, $png_quality );
				break;
			case IMAGETYPE_GIF:
				imagegif( $image, $temp_file );
				break;
		}

		imagedestroy( $image );

		if ( file_exists( $temp_file ) ) {
			$new_size = filesize( $temp_file );

			if ( $new_size < $original_size ) {
				// Use WP_Filesystem for file operations
				if ( $this->move_file( $temp_file, $file_path ) ) {
					do_action( 'sso_image_optimized', $file_path, $original_size, $new_size );
					return true;
				}
			} else {
				// Use WordPress function for file deletion
				wp_delete_file( $temp_file );
			}
		}

		return false;
	}

	/**
	 * Move file using WP_Filesystem if available, fallback to rename().
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 * @return bool
	 */
	private function move_file( $source, $destination ) {
		// Initialize WP_Filesystem
		global $wp_filesystem;
		
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem && $wp_filesystem->method === 'direct' ) {
			// Use WP_Filesystem move
			return $wp_filesystem->move( $source, $destination, true );
		} else {
			// Fallback to rename() with error suppression
			return @rename( $source, $destination ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
		}
	}

	/**
	 * Create WebP version of image.
	 *
	 * @param string $file_path Image file path.
	 * @return bool
	 */
	private function create_webp_version( $file_path ) {
		if ( ! function_exists( 'imagewebp' ) ) {
			return false;
		}

		$image_type = exif_imagetype( $file_path );
		$image = null;

		switch ( $image_type ) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg( $file_path );
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng( $file_path );
				break;
			default:
				return false;
		}

		if ( ! $image ) {
			return false;
		}

		$webp_file = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file_path );
		$quality = ! empty( $this->options['compression_quality'] ) ? intval( $this->options['compression_quality'] ) : 80;

		$result = imagewebp( $image, $webp_file, $quality );
		imagedestroy( $image );

		if ( $result ) {
			do_action( 'sso_webp_created', $file_path, $webp_file );
		}

		return $result;
	}

	/**
	 * Maybe serve WebP version if available and supported.
	 *
	 * @param string $url          Attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 */
	public function maybe_serve_webp( $url, $attachment_id ) {
		if ( is_admin() || ! $this->browser_supports_webp() ) {
			return $url;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path ) {
			return $url;
		}

		$webp_path = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file_path );

		if ( file_exists( $webp_path ) ) {
			return preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $url );
		}

		return $url;
	}

	/**
	 * Check if browser supports WebP.
	 *
	 * @return bool
	 */
	private function browser_supports_webp() {
		if ( ! isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			return false;
		}

		// Sanitize and validate HTTP_ACCEPT header
		$http_accept = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) );
		return strpos( $http_accept, 'image/webp' ) !== false;
	}

	/**
	 * Check if image type is supported.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool
	 */
	private function is_supported_image_type( $mime_type ) {
		$supported_types = array( 'image/jpeg', 'image/png', 'image/gif' );
		return in_array( $mime_type, $supported_types, true );
	}

	/**
	 * Bulk optimize images.
	 *
	 * @param array $attachment_ids Array of attachment IDs.
	 * @return array Results.
	 */
	public function bulk_optimize( $attachment_ids ) {
		$results = array(
			'success' => 0,
			'failed'  => 0,
			'skipped' => 0,
		);

		foreach ( $attachment_ids as $attachment_id ) {
			$file_path = get_attached_file( $attachment_id );

			if ( ! $file_path || ! file_exists( $file_path ) ) {
				$results['skipped']++;
				continue;
			}

			$optimized = $this->optimize_image( $file_path );

			if ( $optimized ) {
				$results['success']++;

				if ( ! empty( $this->options['webp_conversion'] ) ) {
					$this->create_webp_version( $file_path );
				}
			} else {
				$results['failed']++;
			}
		}

		return $results;
	}
}