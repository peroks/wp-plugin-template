<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Plugin asset handler.
 * Enqueues styles and scripts, enables stylesheet inlining and JavaScript defer / async.
 *
 * @author Per Egil Roksvaag
 */
class Asset {
	use Singleton;

	/**
	 * @var string The base directories for css and js assets.
	 */
	const DIR_STYLES  = 'assets/css';
	const DIR_SCRIPTS = 'assets/js';

	/**
	 * @var string The class filter hooks.
	 */
	const FILTER_GET_BASE       = Plugin::PREFIX . '_get_base';
	const FILTER_GET_HANDLE     = Plugin::PREFIX . '_get_handle';
	const FILTER_ENQUEUE_STYLE  = Plugin::PREFIX . '_enqueue_style';
	const FILTER_ENQUEUE_SCRIPT = Plugin::PREFIX . '_enqueue_script';

	/**
	 * @var string Admin settings
	 */
	const SECTION_ASSET             = Plugin::PREFIX . '_asset';
	const OPTION_ASSET_STYLE_INLINE = self::SECTION_ASSET . '_style_inline';
	const OPTION_ASSET_SCRIPT_DEFER = self::SECTION_ASSET . '_script_defer';

	/**
	 * @var array Styles to inline
	 */
	protected array $inline = [];

	/**
	 * @var array Scripts to defer
	 */
	protected array $defer = [];

	/**
	 * @var array Scripts to async and defer
	 */
	protected array $async = [];

	/**
	 * Constructor.
	 */
	protected function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'admin_init' ] );
			add_action( Plugin::ACTION_ACTIVATE, [ $this, 'activate' ] );
			add_action( Plugin::ACTION_DELETE, [ $this, 'delete' ] );
		} else {
			add_action( 'init', [ $this, 'init' ] );
		}
	}

	/**
	 * Inline style and defer scripts.
	 */
	public function init() {

		//	Inline styles
		if ( get_option( self::OPTION_ASSET_STYLE_INLINE ) ) {
			add_filter( self::FILTER_ENQUEUE_STYLE, [ $this, 'inline_styles' ], 10, 5 );
			add_action( 'wp_print_styles', [ $this, 'wp_print_styles' ], 50 );
		}

		//	Defer or async scripts
		if ( get_option( self::OPTION_ASSET_SCRIPT_DEFER ) ) {
			add_filter( self::FILTER_ENQUEUE_SCRIPT, [ $this, 'defer_scripts' ], 10, 5 );
			add_filter( 'script_loader_tag', [ $this, 'script_loader_tag' ], 5, 3 );
		}
	}

	/* -------------------------------------------------------------------------
	 * Inline styles
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers styles for inlining.
	 *
	 * @param string $handle A stylesheet handle.
	 * @param string $path The stylesheet file system path.
	 * @param string $source The stylesheet URL.
	 * @param array $deps An array of registered stylesheet handles this stylesheet depends on.
	 * @param array $args Optional additional arguments: media, inline, etc.
	 *
	 * @return string The stylesheet handle
	 * @see Asset::enqueue_style()
	 */
	public function inline_styles( string $handle, string $path, string $source, array $deps, array $args ): string {
		if ( $args['inline'] ?? false ) {
			$this->inline[ $handle ] = $path;
		}
		return $handle;
	}

	/**
	 * Inlines styles in html head.
	 */
	public function wp_print_styles() {
		foreach ( $this->inline as $handle => $path ) {
			if ( wp_style_is( $handle ) && file_exists( $path ) ) {
				if ( $css = file_get_contents( $path ) ) {
					wp_styles()->registered[ $handle ]->src = false;
					wp_add_inline_style( $handle, $css );
				}
			}
		}
	}

	/* -------------------------------------------------------------------------
	 * Defer or async scripts
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers styles for defer or async.
	 *
	 * @param string $handle A JavaScript handle.
	 * @param string $path The JavaScript file system path.
	 * @param string $source The JavaScript URL.
	 * @param array $deps An array of registered JavaScript handles this JavaScript depends on.
	 * @param array $args Optional additional arguments: footer, defer, async, etc.
	 *
	 * @return string The JavaScript handle
	 * @see Asset::enqueue_script()
	 */
	public function defer_scripts( string $handle, string $path, string $source, array $deps, array $args ): string {
		if ( $args['async'] ?? false ) {
			$this->async[ $handle ] = $path;
		} elseif ( $args['defer'] ?? false ) {
			$this->defer[ $handle ] = $path;
		}
		return $handle;
	}

	/**
	 * Renders deferred and async scripts.
	 *
	 * @param string $tag The script tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 * @param string $src The script's source URL.
	 *
	 * @return string The modified tag.
	 */
	public function script_loader_tag( string $tag, string $handle, string $src ): string {
		if ( array_key_exists( $handle, $this->async ) && is_bool( strpos( ' async', $tag ) ) ) {
			return str_replace( ' src=', ' async defer src=', $tag );
		}
		if ( array_key_exists( $handle, $this->defer ) && is_bool( strpos( ' defer', $tag ) ) ) {
			return str_replace( ' src=', ' defer src=', $tag );
		}
		return $tag;
	}

	/* -------------------------------------------------------------------------
	 * Asset handlers
	 * ---------------------------------------------------------------------- */

	/**
	 * Gets the base directory for the give asset.
	 *
	 * @param string $path The local path to the asset relative to this plugin's root directory.
	 *
	 * @return string The asset base directory.
	 */
	public function get_base( string $path ): string {
		switch ( pathinfo( $path, PATHINFO_EXTENSION ) ) {
			case 'css':
				$base = self::DIR_STYLES;
				break;
			case 'js':
				$base = self::DIR_SCRIPTS;
				break;
			default:
				$base = '';
		}

		$base = trim( $base, '/' );
		return apply_filters( self::FILTER_GET_BASE, $base, $path );
	}

	/**
	 * Enqueues a script.
	 *
	 * @param string $path The local path to the asset relative to this plugin's root directory.
	 *
	 * @return string The generated asset handle.
	 */
	public function get_handle( string $path ): string {
		$path   = trim( trim( $path ), '/' );
		$base   = $this->get_base( $path );
		$debug  = SCRIPT_DEBUG || current_user_can( 'administrator' );
		$source = $debug ? preg_replace( '/[.]min[.](js|css)$/', '.$1', $path ) : $path;
		$handle = preg_replace( "!^{$base}/(.+?)([.]min)?[.](js|css)$!", '$1', $source );
		$handle = preg_replace( '![/._]!', '-', Plugin::PREFIX . '-' . $handle );

		return apply_filters( self::FILTER_GET_HANDLE, $handle, Plugin::path( $source ), $source, $base );
	}

	/**
	 * Enqueues a stylesheet.
	 *
	 * @param string $path The local path to the asset relative to this plugin's root directory.
	 * @param array $deps An array of registered stylesheet handles this stylesheet depends on.
	 * @param array $args Optional additional arguments: media, inline, etc.
	 *
	 * @return string The generated asset handle..
	 */
	public function enqueue_style( string $path, array $deps = [], array $args = [] ): string {
		$path   = trim( trim( $path ), '/' );
		$base   = $this->get_base( $path );
		$debug  = SCRIPT_DEBUG || current_user_can( 'administrator' );
		$source = $debug ? preg_replace( '/[.]min[.](js|css)$/', '.$1', $path ) : $path;
		$handle = preg_replace( "!^{$base}/(.+?)([.]min)?[.](js|css)$!", '$1', $source );
		$handle = preg_replace( '![/._]!', '-', Plugin::PREFIX . '-' . $handle );

		wp_enqueue_style( $handle, Plugin::url( $source ), $deps, Plugin::VERSION, $args['media'] ?? 'all' );
		return apply_filters( self::FILTER_ENQUEUE_STYLE, $handle, Plugin::path( $source ), $source, $deps, $args );
	}

	/**
	 * Enqueues a script.
	 *
	 * @param string $path The local path to the asset relative to this plugin's root directory.
	 * @param array $deps An array of registered script handles this script depends on.
	 * @param array $args Optional additional arguments: footer, defer, async, etc.
	 *
	 * @return string The generated asset handle..
	 */
	public function enqueue_script( string $path, array $deps = [], array $args = [] ): string {
		$path   = trim( trim( $path ), '/' );
		$base   = $this->get_base( $path );
		$debug  = SCRIPT_DEBUG || current_user_can( 'administrator' );
		$source = $debug ? preg_replace( '/[.]min[.](js|css)$/', '.$1', $path ) : $path;
		$handle = preg_replace( "!^{$base}/(.+?)([.]min)?[.](js|css)$!", '$1', $source );
		$handle = preg_replace( '![/._]!', '-', Plugin::PREFIX . '-' . $handle );

		wp_enqueue_script( $handle, Plugin::url( $source ), $deps, Plugin::VERSION, $args['footer'] ?? true );
		return apply_filters( self::FILTER_ENQUEUE_SCRIPT, $handle, Plugin::path( $source ), $source, $deps, $args );
	}

	/* -------------------------------------------------------------------------
	 * Admin settings
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers settings, sections and fields.
	 */
	public function admin_init() {

		// Assets section
		Admin::instance()->add_section( [
			'section'     => self::SECTION_ASSET,
			'page'        => Admin::PAGE,
			'label'       => __( 'Asset settings', '[plugin-text-domain]' ),
			'description' => vsprintf( '<p>%s</p>', [
				esc_html__( 'Check the below checkboxes to improve asset performance.', '[plugin-text-domain]' ),
			] ),
		] );

		//	Inline stylesheets
		Admin::instance()->add_checkbox( [
			'option'      => self::OPTION_ASSET_STYLE_INLINE,
			'section'     => self::SECTION_ASSET,
			'page'        => Admin::PAGE,
			'label'       => __( 'Inline stylesheets', '[plugin-text-domain]' ),
			'description' => __( 'Check to enable stylesheet inlining.', '[plugin-text-domain]' ),
		] );

		//	Defer JavaScript
		Admin::instance()->add_checkbox( [
			'option'      => self::OPTION_ASSET_SCRIPT_DEFER,
			'section'     => self::SECTION_ASSET,
			'page'        => Admin::PAGE,
			'label'       => __( 'Defer JavaScript', '[plugin-text-domain]' ),
			'description' => __( 'Check to enable deferred or async JavasScript.', '[plugin-text-domain]' ),
		] );
	}

	/**
	 * Sets plugin default settings on activation.
	 */
	public function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if ( is_null( get_option( self::OPTION_ASSET_STYLE_INLINE, null ) ) ) {
				add_option( self::OPTION_ASSET_STYLE_INLINE, 1 );
			}
			if ( is_null( get_option( self::OPTION_ASSET_SCRIPT_DEFER, null ) ) ) {
				add_option( self::OPTION_ASSET_SCRIPT_DEFER, 1 );
			}
		}
	}

	/**
	 * Removes settings on plugin deletion.
	 */
	public function delete() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			if ( get_option( Admin::OPTION_DELETE_SETTINGS ) ) {
				delete_option( self::OPTION_ASSET_STYLE_INLINE );
				delete_option( self::OPTION_ASSET_SCRIPT_DEFER );
			}
		}
	}
}