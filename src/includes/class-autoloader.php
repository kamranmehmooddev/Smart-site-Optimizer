<?php
/**
 * Autoloader for plugin classes.
 *
 * @package SmartSiteOptimizer
 */

namespace SmartSiteOptimizer;

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Autoloader class.
 */
class Autoloader {

        /**
         * Register autoloader.
         */
        public static function register() {
                spl_autoload_register( array( __CLASS__, 'autoload' ) );
        }

        /**
         * Autoload classes.
         *
         * @param string $class Class name.
         */
        public static function autoload( $class ) {
                if ( strpos( $class, 'SmartSiteOptimizer\\' ) !== 0 ) {
                        return;
                }

                $class = str_replace( 'SmartSiteOptimizer\\', '', $class );
                $class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );

                $parts = explode( DIRECTORY_SEPARATOR, $class );
                
                $directory_map = array(
                        'Modules' => 'includes/modules',
                        'Admin'   => 'admin',
                        'CLI'     => 'cli',
                        'Utils'   => 'includes/utils',
                );

                $base_dir = SSO_PLUGIN_DIR;
                if ( ! empty( $parts ) ) {
                        $namespace = $parts[0];
                        if ( isset( $directory_map[ $namespace ] ) ) {
                                $base_dir .= $directory_map[ $namespace ] . DIRECTORY_SEPARATOR;
                                array_shift( $parts );
                        } else {
                                $base_dir .= 'includes' . DIRECTORY_SEPARATOR;
                        }
                } else {
                        $base_dir .= 'includes' . DIRECTORY_SEPARATOR;
                }

                $class_name = array_pop( $parts );
                
                if ( ! empty( $parts ) ) {
                        $parts_lower = array_map( 'strtolower', $parts );
                        $base_dir .= implode( DIRECTORY_SEPARATOR, $parts_lower ) . DIRECTORY_SEPARATOR;
                }

                $filename = 'class-' . self::format_filename( $class_name ) . '.php';
                $file = $base_dir . $filename;

                if ( file_exists( $file ) ) {
                        require_once $file;
                }
        }

        /**
         * Format filename from class name.
         *
         * @param string $name Class name part.
         * @return string
         */
        private static function format_filename( $name ) {
                return str_replace( '_', '-', strtolower( $name ) );
        }
}
