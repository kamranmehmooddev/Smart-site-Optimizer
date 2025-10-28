<?php
/**
 * Asset Manager Module.
 *
 * Handles CSS/JS deferral, critical CSS inlining, lazy loading,
 * and font optimization.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset Manager class.
 */
class Asset_Manager {

	/**
	 * Options array.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Deferred scripts.
	 *
	 * @var array
	 */
	private $deferred_scripts = array();

	/**
	 * Critical CSS.
	 *
	 * @var string
	 */
	private $critical_css = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$all_options = get_option( 'sso_options', array() );
		$this->options = isset( $all_options['asset_manager'] ) ? $all_options['asset_manager'] : array();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( ! is_admin() && ! $this->is_excluded_page() ) {
			if ( ! isset( $this->options['defer_js'] ) || $this->options['defer_js'] ) {
				add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );
			}

			if ( ! isset( $this->options['defer_css'] ) || $this->options['defer_css'] ) {
				add_filter( 'style_loader_tag', array( $this, 'defer_styles' ), 10, 4 );
			}

			if ( ! isset( $this->options['critical_css'] ) || $this->options['critical_css'] ) {
				add_action( 'wp_head', array( $this, 'inject_critical_css' ), 1 );
			}

			if ( ! isset( $this->options['lazy_load_images'] ) || $this->options['lazy_load_images'] ) {
				add_filter( 'the_content', array( $this, 'add_lazy_loading' ), 999 );
				add_filter( 'post_thumbnail_html', array( $this, 'add_lazy_loading' ), 999 );
			}

			if ( ! isset( $this->options['font_display_swap'] ) || $this->options['font_display_swap'] ) {
				add_action( 'wp_head', array( $this, 'optimize_fonts' ), 1 );
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_lazy_load_script' ) );
		}
	}

	/**
	 * Check if current page should be excluded.
	 *
	 * @return bool
	 */
	private function is_excluded_page() {
		if ( is_user_logged_in() && is_admin_bar_showing() ) {
			return true;
		}

		return apply_filters( 'sso_exclude_asset_optimization', false );
	}

	/**
	 * Defer non-critical scripts.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string
	 */
	public function defer_scripts( $tag, $handle, $src ) {
		$excluded_handles = apply_filters( 'sso_defer_js_excluded', array( 'jquery-core', 'jquery' ) );

		if ( in_array( $handle, $excluded_handles, true ) ) {
			return $tag;
		}

		if ( strpos( $tag, 'defer' ) !== false || strpos( $tag, 'async' ) !== false ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}

	/**
	 * Defer non-critical stylesheets.
	 *
	 * @param string $tag    Style tag.
	 * @param string $handle Style handle.
	 * @param string $href   Style href.
	 * @param string $media  Media attribute.
	 * @return string
	 */
	public function defer_styles( $tag, $handle, $href, $media ) {
		$excluded_handles = apply_filters( 'sso_defer_css_excluded', array() );

		if ( in_array( $handle, $excluded_handles, true ) ) {
			return $tag;
		}

		$tag = str_replace( "media='$media'", "media='print' onload=\"this.media='$media'\"", $tag );
		$tag .= '<noscript>' . str_replace( "media='print' onload=\"this.media='$media'\"", "media='$media'", $tag ) . '</noscript>';

		return $tag;
	}

	/**
	 * Inject critical CSS inline.
	 */
	public function inject_critical_css() {
		$critical_css = $this->get_critical_css();

		if ( ! empty( $critical_css ) ) {
			// Sanitize and escape CSS content
			$sanitized_css = wp_strip_all_tags( $critical_css );
			$sanitized_css = esc_html( $sanitized_css );
			echo '<style id="sso-critical-css">' . $sanitized_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get critical CSS for current page.
	 *
	 * @return string
	 */
	private function get_critical_css() {
		$page_type = $this->get_page_type();
		$cache_key = 'sso_critical_css_' . $page_type;

		$critical_css = wp_cache_get( $cache_key, 'sso' );

		if ( false === $critical_css ) {
			$critical_css = get_option( $cache_key, '' );
			wp_cache_set( $cache_key, $critical_css, 'sso', 3600 );
		}

		return apply_filters( 'sso_critical_css', $critical_css, $page_type );
	}

	/**
	 * Get current page type.
	 *
	 * @return string
	 */
	private function get_page_type() {
		if ( is_front_page() ) {
			return 'front_page';
		} elseif ( is_single() ) {
			return 'single';
		} elseif ( is_page() ) {
			return 'page';
		} elseif ( is_archive() ) {
			return 'archive';
		}

		return 'default';
	}

	/**
	 * Add lazy loading to images.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function add_lazy_loading( $content ) {
		if ( is_feed() || is_admin() ) {
			return $content;
		}

		$content = preg_replace_callback(
			'/<img([^>]+?)(?:\s*\/)?>/i',
			array( $this, 'add_lazy_load_attributes' ),
			$content
		);

		if ( ! empty( $this->options['lazy_load_iframes'] ) ) {
			$content = preg_replace_callback(
				'/<iframe([^>]+?)(?:\s*\/)?>/i',
				array( $this, 'add_lazy_load_attributes' ),
				$content
			);
		}

		return $content;
	}

	/**
	 * Add lazy load attributes to element.
	 *
	 * @param array $matches Regex matches.
	 * @return string
	 */
	private function add_lazy_load_attributes( $matches ) {
		$tag = $matches[0];
		$attrs = $matches[1];

		if ( strpos( $attrs, 'loading=' ) !== false ) {
			return $tag;
		}

		if ( preg_match( '/class=["\']([^"\']*)/i', $attrs, $class_matches ) ) {
			if ( strpos( $class_matches[1], 'no-lazy' ) !== false ) {
				return $tag;
			}
		}

		$updated_attrs = $attrs . ' loading="lazy"';

		if ( preg_match( '/src=["\']([^"\']+)/i', $attrs, $src_matches ) ) {
			$placeholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E';
			$updated_attrs = str_replace( $src_matches[0], 'src="' . $placeholder . '" data-src="' . $src_matches[1] . '"', $updated_attrs );
		}

		return '<' . ( strpos( $tag, '<iframe' ) === 0 ? 'iframe' : 'img' ) . $updated_attrs . '>';
	}

	/**
	 * Optimize web fonts.
	 */
	public function optimize_fonts() {
		if ( ! empty( $this->options['preload_fonts'] ) && is_array( $this->options['preload_fonts'] ) ) {
			foreach ( $this->options['preload_fonts'] as $font_url ) {
				// Escaping URL for security
				echo '<link rel="preload" href="' . esc_url( $font_url ) . '" as="font" type="font/woff2" crossorigin>';
			}
		}

		echo '<style>@font-face { font-display: swap; }</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueue lazy load script.
	 */
	public function enqueue_lazy_load_script() {
		if ( ! empty( $this->options['lazy_load_images'] ) ) {
			wp_enqueue_script(
				'sso-lazy-load',
				SSO_PLUGIN_URL . 'assets/js/lazy-load.js',
				array(),
				SSO_VERSION,
				true
			);
		}
	}
}