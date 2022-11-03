<?php
/**
 * @package   WP-GTM
 * @author    Thomas Lhotta
 * @license   GPL-2.0+
 * @link      https://github.com/thomaslhotta/wp-gtm
 * @copyright 2022 Thomas Lhotta
 *
 * @wordpress-plugin
 * Plugin Name:	      Wordpress Google Tag Manager
 * Plugin URI:	      https://github.com/thomaslhotta/wp-gtm
 * Description:	      Allows adding of a Google Tag Manager Container via WordPress config file or network options.
 * Version:	      	  1.3.1
 * Author:	          Thomas Lhotta
 * Author URI:	      https://github.com/thomaslhotta
 * License:	          GPL-2.0+
 * GitHub Plugin URI: https://github.com/thomaslhotta/wp-gtm
 */


if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * A simple plugin that allows adding of a Google Tag Manager Container via WordPress config file.
 *
 * @author Thomas Lhotta
 *
 */
class Google_Tag_Manager {

	/**
	 * @var Google_Tag_Manager
	 */
	protected static $instance;

	/**
	 * Used to check if a fallback iframe has already been rendered.
	 *
	 * @var array
	 */
	protected $rendered_iframes = [];

	/**
	 * Returns a singleton instance.
	 *
	 * @return Google_Tag_Manager
	 */
	public static function get_instance() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Google_Tag_Manager constructor.
	 */
	protected function __construct() {
		// Add data layer
		add_action( 'wp_head', array( $this, 'gtm_data_layer' ) );
		add_action( 'wp_head', array( $this, 'render_script_tags' ) );
		add_action( 'login_head', array( $this, 'gtm_data_layer' ) );
		add_action( 'login_head', array( $this, 'render_script_tags' ) );

		// Add GTM snippet
		add_action( 'after_body_open', array( $this, 'render_fallback_iframes' ) );

		// Fallback to footer if no header action exists
		add_action( 'wp_footer', array( $this, 'render_fallback_iframes' ) );
		add_action( 'login_footer', array( $this, 'render_fallback_iframes' ) );

		// Activate in admin if constant ist set
		if ( defined( 'GOOGLE_TAG_MANAGER_IN_ADMIN' ) && GOOGLE_TAG_MANAGER_IN_ADMIN ) {
			add_action( 'in_admin_header', array( $this, 'gtm_tag' ) );
		}
	}

	/**
	 * Returns data layer string
	 *
	 * @return string
	 */
	public function get_data_layer() {
		$data_layer = array();

		// Log if the user is logged in
		$data_layer['loggedIn'] = is_user_logged_in() ? '1' : '0';

		// Log current post type
		$post_type = get_post_type();
		if ( ! is_admin() && ! empty( $post_type ) ) {
			$data_layer['postType'] = get_post_type();
		}

		// Log site names and ids on multi site installs
		if ( is_multisite() ) {
			$data_layer['siteId']   = (string) get_current_blog_id();
			$data_layer['siteName'] = get_bloginfo( 'name' );
		}

		$data_layer = apply_filters( 'google_tag_manager_data_layer', $data_layer );

		return wp_json_encode( $data_layer );
	}

	/**
	 * Prints the data layer tag
	 */
	public function gtm_data_layer() {
		printf(
			'<script>
				window.dataLayer = window.dataLayer || [];
				dataLayer.push(%s);
			</script>',
			$this->get_data_layer()
		);
	}

	/**
	 * Renders JS for all defined containers.
	 */
	public function render_script_tags() {
		$container_ids = $this->get_gtm_ids();

		foreach ( $container_ids as $single_container_id ) {
			echo $this->get_script_tag( $single_container_id ) . PHP_EOL;
		}
	}

	/**
	 * Renders the fallback iframe for all defined containers.
	 */
	public function render_fallback_iframes() {
		$container_ids = $this->get_gtm_ids();

		foreach ( $container_ids as $single_container_id ) {
			if ( in_array( $single_container_id, $this->rendered_iframes, true ) ) {
				continue;
			}
			echo $this->get_fallback_iframe( $single_container_id ) . PHP_EOL;
			$this->rendered_iframes[] = $single_container_id;
		}
	}

	/**
	 * Returns the contains JS template.
	 *
	 * @param string $container_id
	 *
	 * @return string
	 */
	public function get_script_tag( $container_id ) {
		return sprintf(
			"<!-- Google Tag Manager -->
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','%s');</script>
			<!-- End Google Tag Manager -->",
			esc_js( $container_id )
		);
	}

	/**
	 * Returns the container iframe template.
	 *
	 * @param string $container_id
	 *
	 * @return string
	 */
	public function get_fallback_iframe( $container_id ) {
		return sprintf(
			'<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->',
			esc_attr( $container_id )
		);
	}

    /**
     * Returns the container ids
     *
     * @return array
     */
    public function get_gtm_ids() {
        $container_ids = '';

        if ( defined( 'GOOGLE_TAG_MANAGER_CONTAINER' ) ) {
            $container_ids = GOOGLE_TAG_MANAGER_CONTAINER;
        } else {
            $container_ids = get_network_option( null, 'google_tag_manager_container' );
        }

        $container_ids = explode( ',', $container_ids );
        return array_filter( $container_ids );
    }
}

if ( apply_filters( 'wp_gtm_disable', (bool) get_option( 'wp_gtm_disable', false ) ) ) {
	return;
}

add_action( 'init', array( 'Google_Tag_Manager', 'get_instance' ) );
