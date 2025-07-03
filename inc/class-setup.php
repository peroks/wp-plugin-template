<?php
/**
 * Plugin setup.
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
		// Load plugin translations.
		add_action( 'init', [ $this, 'load_translations' ] );

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Enqueue block assets for both editor and frontend.
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );

		// Enqueue block assets for the editor.
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Loads the translated strings (if any).
	 */
	public function load_translations(): void {
		$path = dirname( plugin_basename( Plugin::FILE ) ) . '/languages';
		load_plugin_textdomain( '[your-plugin-text-domain]', false, $path );
	}

	/**
	 * Enqueues frontend assets.
	 */
	public function enqueue_frontend_assets(): void {}

	/**
	 * Enqueues admin assets.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {}

	/**
	 * Enqueues block assets for both editor and frontend.
	 */
	public function enqueue_block_assets(): void {}

	/**
	 * Enqueues block assets for the editor.
	 */
	public function enqueue_editor_assets(): void {}
}
