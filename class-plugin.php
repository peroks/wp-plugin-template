<?php
/**
 * Plugin Name:       [Your plugin name]
 * Description:       [Your plugin description].
 * Plugin URI:        https://github.com/peroks/wp-plugin-template
 * Update URI:        false
 * Text Domain:       [your-plugin-text-domain]
 * Domain Path:       /languages
 * Author:            Per Egil Roksvaag
 * Author URI:        https://github.com/peroks
 * License:           MIT
 * Version:           0.2.0
 * Stable tag:        0.2.0
 * Requires at least: 6.6
 * Tested up to:      6.8
 * Requires PHP:      8.2
 */

declare( strict_types = 1 );
namespace Peroks\WP\Plugin\Name;

// Require the singleton trait.
require_once __DIR__ . '/inc/trait-singleton.php';

/**
 * The plugin main class.
 */
class Plugin {
	use Singleton;

	/**
	 * The full path to this file.
	 *
	 * @var string The plugin file.
	 */
	const FILE = __FILE__;

	/**
	 * The plugin prefix, Use lowercase and underscores as word separator.
	 *
	 * @var string The plugin prefix (underscore).
	 */
	const PREFIX = '[your_plugin_prefix]';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->autoload();
		$this->run();
	}

	/**
	 * Registers autoloading.
	 */
	protected function autoload(): void {
		$classes = [
			__NAMESPACE__ . '\\Admin' => static::path( 'inc/class-admin.php' ),
			__NAMESPACE__ . '\\Setup' => static::path( 'inc/class-setup.php' ),
		];

		spl_autoload_register( function ( $name ) use ( $classes ) {
			if ( array_key_exists( $name, $classes ) ) {
				require $classes[ $name ];
			}
		} );
	}

	/**
	 * Loads and runs the plugin classes.
	 * You must register your classes for autoloading (above) before you can run them here.
	 */
	protected function run(): void {
		Setup::instance();

		if ( is_admin() ) {
			Admin::instance();
		}
	}

	/**
	 * Gets the current plugin version.
	 */
	public static function version(): string {
		$version = wp_cache_get( 'version', self::PREFIX ) ?: '';

		if ( empty( $version ) ) {
			if ( empty( function_exists( 'get_plugin_data' ) ) ) {
				if ( empty( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) ) {
					return '';
				}
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$data    = get_plugin_data( self::FILE, false, false );
			$version = $data['Version'];
			wp_cache_set( 'version', $version, self::PREFIX );
		}
		return $version;
	}

	/**
	 * Gets a full filesystem path from a local path.
	 *
	 * @param string $path The local path relative to this plugin's root directory.
	 *
	 * @return string The full filesystem path.
	 */
	public static function path( string $path = '' ): string {
		$path = ltrim( trim( $path ), '/' );
		return plugin_dir_path( self::FILE ) . $path;
	}

	/**
	 * Gets the URL to the given local path.
	 *
	 * @param string $path The local path relative to this plugin's root directory.
	 *
	 * @return string The URL.
	 */
	public static function url( string $path = '' ): string {
		$path = ltrim( trim( $path ), '/' );
		return plugins_url( $path, self::FILE );
	}
}

// Initialize the plugin and notify that it's fully loaded and ready.
if ( defined( 'ABSPATH' ) && ABSPATH ) {
	add_action( 'plugins_loaded', function () {
		do_action( Plugin::PREFIX . '_loaded', Plugin::instance() );
	}, 20 );
}
