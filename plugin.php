<?php namespace peroks\plugin_customer\plugin_package;
/*
 * Plugin Name:       [This Plugin Name]
 * Plugin URI:        https://github.com/peroks/wp-plugin-template
 * Description:       [This plugin description]
 *
 * Text Domain:       [plugin-text-domain]
 * Domain Path:       /languages
 *
 * Author:            Per Egil Roksvaag
 * Author URI:        https://codeable.io/developers/per-egil-roksvaag/
 *
 * Version:           0.2.0
 * Stable tag:        0.2.0
 * Requires at least: 5.0
 * Tested up to:      5.9
 * Requires PHP:      7.4
 * Update URI:        false
 */

/**
 * The [This Plugin Name] plugin main class.
 *
 * @author Per Egil Roksvaag
 * @version 0.2.0
 */
class Plugin {
	/**
	 * @var string The plugin file.
	 */
	const FILE = __FILE__;

	/**
	 * Should be identical to "Plugin Name" in the plugin header comment above.
	 *
	 * @var string The plugin name.
	 * @todo Globally search and replace this with your own plugin name.
	 */
	const NAME = '[This Plugin Name]';

	/**
	 * Must be identical to "Domain Path" in the plugin header comment above.
	 * Use lowercase and hyphens as word breaker.
	 *
	 * @var string The plugin text domain (hyphen).
	 * @todo Globally search and replace this with your own unique text domain
	 */
	const DOMAIN = '[plugin-text-domain]';

	/**
	 * Should be similar to self::DOMAIN, only with underscores instead of hyphens.
	 * Use lowercase and underscores as word breaker.
	 *
	 * @var string The plugin prefix (underscore).
	 * @todo Replace this constant with your own unique plugin prefix.
	 */
	const PREFIX = 'plugin_prefix';

	/**
	 * Should contain the "Version" field in the plugin header comment above.
	 *
	 * @var string The plugin version.
	 * @todo Set your plugin version number.
	 */
	const VERSION = '0.2.0';

	/**
	 * Only requirement constants > '0' will be checked.
	 *
	 * @var string The system environment requirements.
	 * @todo Replace this with the system requirements of your owe plugin.
	 * @see Plugin::check() below and possibly add/remove system checks and constants.
	 */
	const REQUIRE_PHP  = '7.4'; //  Required PHP version
	const REQUIRE_WP   = '5.0'; //  Required WordPress version
	const REQUIRE_ACF  = '0';   //	Required Advanced Custom Fields version
	const REQUIRE_WOO  = '0';   //	Required WooCommerce version
	const REQUIRE_WPML = '0';   //	Required WordPress Multilingual version

	/**
	 * @var string The plugin global action hooks.
	 */
	const ACTION_LOADED     = self::PREFIX . '_loaded';
	const ACTION_AUTOLOADED = self::PREFIX . '_autoloaded';
	const ACTION_UPDATE     = self::PREFIX . '_update';
	const ACTION_ACTIVATE   = self::PREFIX . '_activate';
	const ACTION_DEACTIVATE = self::PREFIX . '_deactivate';
	const ACTION_DELETE     = self::PREFIX . '_delete';

	/**
	 * @var string The plugin global filter hooks.
	 */
	const FILTER_CLASS_CREATE  = self::PREFIX . '_class_create';
	const FILTER_CLASS_CREATED = self::PREFIX . '_class_created';
	const FILTER_CLASS_PATH    = self::PREFIX . '_class_path';
	const FILTER_SYSTEM_CHECK  = self::PREFIX . '_system_check';
	const FILTER_PLUGIN_PREFIX = self::PREFIX . '_plugin_prefix';
	const FILTER_PLUGIN_PATH   = self::PREFIX . '_plugin_path';
	const FILTER_PLUGIN_URL    = self::PREFIX . '_plugin_url';

	/**
	 * @var string The plugin global options.
	 */
	const OPTION_VERSION = self::PREFIX . '_version';

	/**
	 * @var Plugin[] The plugin class singletons.
	 */
	private static array $inst;

	/**
	 * @return Plugin The plugin class singleton.
	 */
	public static function instance(): ?object {
		$class = apply_filters( self::FILTER_CLASS_CREATE, static::class );

		if ( empty( self::$inst[ $class ] ) && static::check() ) {
			self::$inst[ $class ] = apply_filters( self::FILTER_CLASS_CREATED, new $class(), $class, static::class );
			do_action( self::ACTION_LOADED, self::$inst[ $class ] );
		}
		return self::$inst[ $class ] ?? null;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->autoload();
		$this->run();
		$this->update();
	}

	/**
	 * Registers autoloading.
	 *
	 * @todo Add your own plugin classes and their file system paths here.
	 */
	protected function autoload() {
		$classes = apply_filters( self::FILTER_CLASS_PATH, [
			__NAMESPACE__ . '\\Setup' => Plugin::path( 'includes/setup.php' ),
			__NAMESPACE__ . '\\Admin' => Plugin::path( 'includes/admin.php' ),

			__NAMESPACE__ . '\\Singleton'  => Plugin::path( 'includes/tools/singleton.php' ),
			__NAMESPACE__ . '\\Asset'      => Plugin::path( 'includes/tools/asset.php' ),
			__NAMESPACE__ . '\\Modal'      => Plugin::path( 'includes/tools/modal.php' ),
			__NAMESPACE__ . '\\Utils'      => Plugin::path( 'includes/tools/utils.php' ),
			__NAMESPACE__ . '\\Form'       => Plugin::path( 'includes/tools/form.php' ),
			__NAMESPACE__ . '\\Download'   => Plugin::path( 'includes/tools/download.php' ),
			__NAMESPACE__ . '\\Repository' => Plugin::path( 'includes/tools/repository.php' ),
		] );

		spl_autoload_register( function( $name ) use ( $classes ) {
			if ( array_key_exists( $name, $classes ) ) {
				include $classes[ $name ];
			}
		} );

		do_action( self::ACTION_AUTOLOADED, $this );
	}

	/**
	 * Loads and runs the plugin classes.
	 * You must register your classes for autoloading (above) before you can run them here.
	 *
	 * @todo Add your own plugin classes.
	 */
	protected function run() {
		Setup::instance();
		Asset::instance();
		Modal::instance();

		if ( is_admin() ) {
			Admin::instance();
			Download::instance();
			Repository::instance();
		}
	}

	/* =========================================================================
	 * Everything below this line is just plugin management and some very
	 * basic path and url handlers. You'll find the real action in the classes
	 * loaded above.
	 * ====================================================================== */

	/* -------------------------------------------------------------------------
	 * System environment checks
	 * ---------------------------------------------------------------------- */

	/**
	 * Checks if the system environment is supported.
	 *
	 * @return bool True if the system environment is supported, false otherwise.
	 * @todo Add/remove system environment checks for your plugin.
	 */
	protected static function check(): bool {
		$error = false;

		if ( defined( 'self::REQUIRE_PHP' ) && self::REQUIRE_PHP ) {
			if ( version_compare( PHP_VERSION, self::REQUIRE_PHP ) < 0 ) {
				$error = static::error( 'PHP', self::REQUIRE_PHP ) || $error;
			}
		}

		if ( defined( 'self::REQUIRE_WP' ) && self::REQUIRE_WP ) {
			if ( version_compare( get_bloginfo( 'version' ), self::REQUIRE_WP ) < 0 ) {
				$error = static::error( 'WordPress', self::REQUIRE_WP ) || $error;
			}
		}

		if ( defined( 'self::REQUIRE_ACF' ) && self::REQUIRE_ACF ) {
			if ( version_compare( get_option( 'acf_version', 0 ), self::REQUIRE_ACF ) < 0 ) {
				$error = static::error( 'WordPress', self::REQUIRE_WP ) || $error;
			}
		}

		if ( defined( 'self::REQUIRE_WOO' ) && self::REQUIRE_WOO ) {
			global $woocommerce;

			if ( empty( is_a( $woocommerce, 'WooCommerce' ) ) || version_compare( $woocommerce->version, self::REQUIRE_WOO ) < 0 ) {
				$error = static::error( 'WooCommerce', self::REQUIRE_WOO ) || $error;
			}
		}

		if ( defined( 'self::REQUIRE_WPML' ) && self::REQUIRE_WPML ) {
			if ( empty( defined( '\ICL_SITEPRESS_VERSION' ) ) || version_compare( \ICL_SITEPRESS_VERSION, self::REQUIRE_WPML ) < 0 ) {
				$error = static::error( 'WPML (WordPress Multilingual)', self::REQUIRE_WPML ) || $error;
			}
		}

		return empty( $error );
	}

	/**
	 * Logs and outputs missing system requirements.
	 *
	 * @param string $require The name of the required component.
	 * @param string $version The minimum version required.
	 *
	 * @return bool True, except when overridden by filter.
	 */
	protected static function error( string $require, string $version ): bool {
		if ( apply_filters( self::FILTER_SYSTEM_CHECK, true, $require, $version ) ) {
			if ( is_admin() ) {

				//	Error message
				$message = __( '%1$s requires %2$s version %3$s or higher, the plugin is NOT RUNNING.', '[plugin-text-domain]' );
				$message = sprintf( $message, self::NAME, $require, $version );

				//	Admin notice output
				$notice = function() use ( $message ) {
					vprintf( '<div class="notice notice-error"><p><strong>%s: </strong>%s</p></div>', [
						esc_html__( 'Error', '[plugin-text-domain]' ),
						esc_html( $message ),
					] );
				};

				//	Write error message to log and create admin notice.
				error_log( $message );
				add_action( 'admin_notices', $notice );
			}
			return true;
		}
		return false;
	}

	/* -------------------------------------------------------------------------
	 * Update, activate, deactivate and uninstall plugin.
	 * ---------------------------------------------------------------------- */

	/**
	 * Checks if the plugin was updated.
	 * Notifies plugin classes to update and flushes rewrite rules.
	 *
	 * @return bool True if the plugin was updated, false otherwise.
	 */
	protected function update(): bool {
		$version = get_option( self::OPTION_VERSION );

		if ( self::VERSION !== $version ) {
			do_action( self::ACTION_UPDATE, $this, self::VERSION, $version );
			update_option( self::OPTION_VERSION, self::VERSION );

			add_action( 'wp_loaded', 'flush_rewrite_rules' );
			add_action( 'admin_notices', function() {
				$notice = __( '%s has been updated to version %s', '[plugin-text-domain]' );
				$notice = sprintf( $notice, self::NAME, self::VERSION );
				printf( '<div class="notice notice-success is-dismissible"><p>%s.</p></div>', esc_html( $notice ) );
				error_log( $notice );
			} );
			return true;
		}
		return false;
	}

	/**
	 * Registers plugin activation, deactivation and uninstall hooks.
	 */
	public static function register() {
		if ( is_admin() ) {
			register_activation_hook( self::FILE, [ static::class, 'activate' ] );
			register_deactivation_hook( self::FILE, [ static::class, 'deactivate' ] );
			register_uninstall_hook( self::FILE, [ static::class, 'uninstall' ] );
		}
	}

	/**
	 * Runs when the plugin is activated.
	 * Notifies plugin classes to activate and flushes rewrite rules.
	 * This hook is called AFTER all other hooks (except 'shutdown').
	 * WP redirects the request immediately after this hook, so we can't register any hooks to be executed later.
	 */
	public static function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			do_action( self::ACTION_ACTIVATE, static::instance(), self::VERSION, get_option( self::OPTION_VERSION ) );
			update_option( self::OPTION_VERSION, self::VERSION );
			$message = __( '%s version %s has been activated', '[plugin-text-domain]' );
			error_log( sprintf( $message, self::NAME, self::VERSION ) );
			flush_rewrite_rules();
		}
	}

	/**
	 * Runs when the plugin is deactivated.
	 * Notifies plugin classes to deactivate and flushes rewrite rules.
	 */
	public static function deactivate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			do_action( self::ACTION_DEACTIVATE, static::instance(), self::VERSION, get_option( self::OPTION_VERSION ) );
			$message = __( '%s version %s has been deactivated', '[plugin-text-domain]' );
			error_log( sprintf( $message, self::NAME, self::VERSION ) );
			flush_rewrite_rules();
		}
	}

	/**
	 * Runs when the plugin is deleted.
	 * Notifies plugin classes to delete all plugin settings and flushes rewrite rules.
	 */
	public static function uninstall() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			do_action( self::ACTION_DELETE, static::instance(), self::VERSION, get_option( self::OPTION_VERSION ) );
			delete_option( self::OPTION_VERSION );
			$message = __( '%s version %s has been removed', '[plugin-text-domain]' );
			error_log( sprintf( $message, self::NAME, self::VERSION ) );
			flush_rewrite_rules();
		}
	}

	/* -------------------------------------------------------------------------
	 * Basic prefix, path and url utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Gets a prefixed identifier.
	 *
	 * @param string $name The identifier to prefix.
	 * @param string $sep The prefix separator.
	 *
	 * @return string The prefixed identifier.
	 */
	public static function prefix( string $name = '', string $sep = '_' ): string {
		$result = str_replace( '_', $sep, self::PREFIX . $sep . $name );
		return apply_filters( self::FILTER_PLUGIN_PREFIX, $result, $name, $sep );
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
		$full = plugin_dir_path( self::FILE ) . $path;
		return apply_filters( self::FILTER_PLUGIN_PATH, $full, $path );
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
		$url  = plugins_url( $path, self::FILE );
		return apply_filters( self::FILTER_PLUGIN_URL, $url, $path );
	}
}

//	Registers and runs the main plugin class
if ( defined( 'ABSPATH' ) ) {
	Plugin::register();
	add_action( 'after_setup_theme', [ Plugin::class, 'instance' ], 5 );
}
