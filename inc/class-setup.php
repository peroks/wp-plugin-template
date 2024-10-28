<?php
/**
 * Plugin setup.
 *
 * @author Per Egil Roksvaag
 */

declare( strict_types = 1 );
namespace Peroks\WP\Plugin\Name;

/**
 * Plugin setup.
 */
class Setup {
	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'load_translations' ] );

		if ( empty( is_admin() ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_styles' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
		}
	}

	/**
	 * Loads the translated strings (if any).
	 */
	public function load_translations(): void {
		$path = dirname( plugin_basename( Plugin::FILE ) ) . '/languages';
		load_plugin_textdomain( '[your-plugin-text-domain]', false, $path );
	}

	/**
	 * Enqueues frontend styles.
	 */
	public function wp_enqueue_styles() {}

	/**
	 * Enqueues frontend scripts.
	 */
	public function wp_enqueue_scripts() {}
}
