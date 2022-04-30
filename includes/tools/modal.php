<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Creates modal dialogs.
 *
 * @author Per Egil Roksvaag
 */
class Modal {
	use Singleton;

	/**
	 * @var string The class filter hooks.
	 */
	const FILTER_MODAL_ID        = Plugin::PREFIX . '_modal_id';
	const FILTER_MODAL_TRIGGER   = Plugin::PREFIX . '_modal_trigger';
	const FILTER_MODAL_CONTAINER = Plugin::PREFIX . '_modal_container';
	const FILTER_MODAL_TEMPLATES = Plugin::PREFIX . '_modal_templates';
	const FILTER_MODAL_CONTENT   = Plugin::PREFIX . '_modal_content';
	const FILTER_MODAL_HEADER    = Plugin::PREFIX . '_modal_header';
	const FILTER_MODAL_BODY      = Plugin::PREFIX . '_modal_body';
	const FILTER_MODAL_FOOTER    = Plugin::PREFIX . '_modal_footer';

	/**
	 * @var int The modal counter
	 */
	protected $index = 0;

	/**
	 * @var array An array of registred modal templates
	 */
	protected $templates = [];

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/* -------------------------------------------------------------------------
	 * WordPress callbacks
	 * ---------------------------------------------------------------------- */

	/**
	 * Adds shortcodes and enqueues class styles and scripts.
	 */
	public function init() {

		//	Add shortcodes
		add_shortcode( Plugin::PREFIX . '_modal', [ $this, 'shortcode' ] );
		add_shortcode( Plugin::PREFIX . '_modal_trigger', [ $this, 'trigger' ] );
		add_shortcode( Plugin::PREFIX . '_modal_container', [ $this, 'container' ] );

		//	Enqueue frontend styles and scripts
		if ( empty( is_admin() ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_styles' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
		}
	}

	/**
	 * Enqueues styles.
	 */
	public function wp_enqueue_styles() {
		$args = [ 'inline' => true ];
		Asset::instance()->enqueue_style( 'assets/css/tools/pure-modal.min.css', [], $args );
	}

	/**
	 * Enqueues scripts.
	 */
	public function wp_enqueue_scripts() {
		$args = [ 'defer' => true ];
		Asset::instance()->enqueue_script( 'assets/js/tools/pure-modal.min.js', [], $args );
	}

	/* -------------------------------------------------------------------------
	 * Shortcodes
	 * ---------------------------------------------------------------------- */

	/**
	 * Creates a modal dialog container and a trigger for opening the modal dialog.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $content The modal container content.
	 * @param string $shortcode The shortcode name.
	 *
	 * @return string The modal trigger html
	 */
	public function shortcode( $args = [], $content = '', $shortcode = '' ) {
		foreach ( array_change_key_case( $args ) as $key => $value ) {
			if ( strpos( $key, '_' ) ) {
				[ $prefix, $name ] = explode( '_', $key );
				$var[ $prefix ][ $name ] = $value;
			}
		}

		$trigger   = $var['trigger'] ?? [];
		$container = $var['container'] ?? [];

		$trigger['container'] = $this->container( $container, $content, $shortcode );
		return $this->trigger( $trigger );
	}

	/**
	 * Creates a trigger for opening a modal dialog.
	 *
	 * @param array $args An array of shortcode attributes.
	 *
	 * @return string The modal trigger html
	 */
	public function trigger( $args = [] ) {
		$args = array_change_key_case( $args );
		$args = wp_parse_args( $args, [
			'container' => '',
			'type'      => 'button',
			'class'     => [],
			'icon'      => '',
			'text'      => __( 'Open', '[plugin-text-domain]' ),
		] );

		$template = $args['type'];

		$args['class']   = Utils::instance()->parse_class( $args['class'] );
		$args['class'][] = 'pure-modal-trigger';
		$args['class'][] = $template . '-type';
		$args['class'][] = $args['container'];

		$callback = $this->get_template( $template, 'trigger' );
		$trigger  = call_user_func( $callback, $args, $template );
		return apply_filters( self::FILTER_MODAL_TRIGGER, $trigger, $args, $template );
	}

	/**
	 * Creates a modal dialog container.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $content The modal container content.
	 * @param string $shortcode The shortcode name.
	 *
	 * @return string The modal container ID.
	 */
	public function container( $args = [], $content = '', $shortcode = '' ) {
		$this->index++;
		$id = sprintf( Plugin::prefix( 'modal-container-%d', '-' ), $this->index );
		$id = apply_filters( self::FILTER_MODAL_ID, $id, $this->index );

		$args = array_change_key_case( $args );
		$args = wp_parse_args( $args, [
			'id'    => $id,
			'class' => [],
			'type'  => 'simple',
			'defer' => false,
			'load'  => '',
		] );

		$template = $args['type'];
		$content  = $this->get_content( $args, $content );

		$args['class']   = Utils::instance()->parse_class( $args['class'] );
		$args['class'][] = 'pure-modal-container';
		$args['class'][] = $template . '-type';

		add_action( 'wp_footer', function() use ( $args, $content, $template ) {
			$callback  = $this->get_template( $template, 'container' );
			$container = call_user_func( $callback, $args, $content, $template );
			echo apply_filters( self::FILTER_MODAL_CONTAINER, $container, $args, $content, $template );
		}, 50 );

		return $args['id'];
	}

	/* -------------------------------------------------------------------------
	 * Utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Modifies the modal content depending on the given arguments.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $content The modal container content.
	 *
	 * @return string The modified modal container content.
	 */
	public function get_content( $args, $content ) {
		$content = apply_shortcodes( $content );

		if ( $args['defer'] ) {
			$base64  = base64_encode( $content );
			$content = sprintf( '<data class="pure-modal-defer" value="%s"></data>', $base64 );
		} elseif ( $load = $args['load'] ) {
			$content = sprintf( '<data class="pure-modal-load" value="%s"></data>', $load );
		}

		return apply_filters( self::FILTER_MODAL_CONTENT, $content, $args );
	}

	/**
	 * Gets a modal template callback function.
	 *
	 * @param string $template A registred template name: link, button, simple, section, form.
	 * @param string $type The template type: trigger or container.
	 *
	 * @return callable A callback to a template function for rendering a modal container.
	 */
	public function get_template( $template, $type ) {
		if ( empty( $this->templates ) ) {
			$this->templates = apply_filters( self::FILTER_MODAL_TEMPLATES, [
				'trigger'   => [
					'link'   => [ $this, 'trigger_link' ],
					'button' => [ $this, 'trigger_button' ],
				],
				'container' => [
					'simple'  => [ $this, 'container_simple' ],
					'section' => [ $this, 'container_section' ],
					'form'    => [ $this, 'container_form' ],
				],
			] );
		}
		return $this->templates[ $type ][ $template ] ?? '__return_empty_string';
	}

	/* -------------------------------------------------------------------------
	 * Modal templates
	 * ---------------------------------------------------------------------- */

	/**
	 * A template for rendering a link modal trigger.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $template The template name.
	 *
	 * @return string The modal trigger html.
	 */
	public function trigger_link( $args, $template = '' ) {
		return vsprintf( '<a class="%s" href="javascript:void(0);">%s%s</a>', [
			esc_attr( join( ' ', $args['class'] ) ),
			wp_kses_post( $args['icon'] ),
			wp_kses_post( $args['text'] ),
		] );
	}

	/**
	 * A template for rendering a button modal trigger.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $template The template name.
	 *
	 * @return string The modal trigger html.
	 */
	public function trigger_button( $args, $template = '' ) {
		return vsprintf( '<button class="%s">%s%s</button>', [
			esc_attr( join( ' ', $args['class'] ) ),
			wp_kses_post( $args['icon'] ),
			wp_kses_post( $args['text'] ),
		] );
	}

	/**
	 * A template for rendering a simple modal dialog container.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $content The modal container content.
	 * @param string $template The template name.
	 *
	 * @return string The modal container html.
	 */
	public function container_simple( $args, $content, $template ) {
		return vsprintf( '<div id="%s" class="%s">%s</div>', [
			esc_attr( $args['id'] ),
			esc_attr( join( ' ', $args['class'] ) ),
			$content,
		] );
	}

	/**
	 * A template for rendering a modal dialog container with header, body and footer.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $content The modal container content.
	 * @param string $template The template name.
	 *
	 * @return string The modal container html.
	 */
	public function container_section( $args, $content, $template ) {
		$args = wp_parse_args( $args, [
			'header' => true,
			'body'   => true,
			'footer' => true,
			'title'  => __( 'Title', '[plugin-text-domain]' ),
		] );

		if ( $args['header'] ) {
			$title    = wp_kses_post( $args['title'] );
			$header   = sprintf( '<div class="pure-modal-header"><h3>%s<h3></div>', $title );
			$output[] = apply_filters( self::FILTER_MODAL_HEADER, $header, $args, $content, $template );
		}

		if ( $args['body'] ) {
			$body     = sprintf( '<div class="pure-modal-body">%s</div>', $content );
			$output[] = apply_filters( self::FILTER_MODAL_BODY, $body, $args, $content, $template );
		}

		if ( $args['footer'] ) {
			$text     = esc_html__( 'OK', '[plugin-text-domain]' );
			$default  = sprintf( '<button type="button" class="pure-modal-button">%s</button>', $text );
			$buttons  = wp_kses_post( $args['buttons'] ?? $default );
			$footer   = sprintf( '<div class="pure-modal-footer">%s</div>', $buttons );
			$output[] = apply_filters( self::FILTER_MODAL_FOOTER, $footer, $args, $content, $template );
		}

		if ( isset( $output ) ) {
			return vsprintf( '<section id="%s" class="%s">%s</section>', [
				esc_attr( $args['id'] ),
				esc_attr( join( ' ', $args['class'] ) ),
				join( '', array_filter( $output ) ),
			] );
		}
	}

	/**
	 * A template for rendering a modal dialog form with header, body and footer.
	 *
	 * @param array $args An array of shortcode attributes.
	 * @param string $content The modal container content.
	 * @param string $template The template name.
	 *
	 * @return string The modal container html.
	 */
	public function container_form( $args, $content, $template ) {
		global $wp;

		$args = wp_parse_args( $args, [
			'header'  => true,
			'body'    => true,
			'footer'  => true,
			'title'   => __( 'Title', '[plugin-text-domain]' ),
			'method'  => 'POST',
			'action'  => home_url( $wp->request ),
			'enctype' => 'multipart/form-data',
		] );

		if ( $args['header'] ) {
			$title    = wp_kses_post( $args['title'] );
			$header   = sprintf( '<div class="pure-modal-header"><h3>%s<h3></div>', $title );
			$output[] = apply_filters( self::FILTER_MODAL_HEADER, $header, $args, $content, $template );
		}

		if ( $args['body'] ) {
			$body     = sprintf( '<div class="pure-modal-body">%s</div>', $content );
			$output[] = apply_filters( self::FILTER_MODAL_BODY, $body, $args, $content, $template );
		}

		if ( $args['footer'] ) {
			$text     = esc_html__( 'Submit', '[plugin-text-domain]' );
			$default  = sprintf( '<button type="submit" class="pure-modal-button">%s</button>', $text );
			$buttons  = wp_kses_post( $args['buttons'] ?? $default );
			$footer   = sprintf( '<div class="pure-modal-footer">%s</div>', $buttons );
			$output[] = apply_filters( self::FILTER_MODAL_FOOTER, $footer, $args, $content, $template );
		}

		if ( isset( $output ) ) {
			return vsprintf( '<form id="%s" class="%s" method="%s" action="%s" enctype="%s">%s</form>', [
				esc_attr( $args['id'] ),
				esc_attr( join( ' ', $args['class'] ) ),
				esc_attr( $args['method'] ),
				esc_attr( $args['action'] ),
				esc_attr( $args['enctype'] ),
				join( '', array_filter( $output ) ),
			] );
		}
	}
}