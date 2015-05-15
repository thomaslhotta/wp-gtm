<?php
/**
 * @package   WP-GTM
 * @author	Thomas Lhotta
 * @license   GPL-2.0+
 * @link	  http://example.com
 * @copyright 2013 Thomas Lhotta
 *
 * @wordpress-plugin
 * Plugin Name:	   Wordpress Google Tag Manager
 * Plugin URI:		https://github.com/thomaslhotta/wp-gtm
 * Description:	   Allows adding of a Google Tag Manager Container via Wordpress config file. 
 * Version:		   1.0.0
 * Author:			Thomas Lhotta
 * Author URI:		https://github.com/thomaslhotta
 * Text Domain:	   cie
 * License:		   GPL-2.0+
 * Domain Path:	   /languages
 * GitHub Plugin URI: https://github.com/thomaslhotta/wp-gtm
 */


if ( ! defined( 'ABSPATH' ) || ! defined( 'GOOGLE_TAG_MANAGER_CONTAINER' ) || '' == GOOGLE_TAG_MANAGER_CONTAINER ) {
	return;
}

/**
 * A simple plugin that allows adding of a Google Tag Manager Container via Wordpress config file. 
 *
 * @author Thomas Lhotta
 *
 */
class Google_Tag_Manager
{
	/**
	 * @var Google_Tag_Manager
	 */
	protected static $instance;

	/**
	 * Used to check if the fallback action has to be used.
	 *
	 * @var boolean
	 */
	protected $echoed = false;

	/**
	 * Returns a singleton instance.
	 *
	 * @return Google_Tag_Manager
	 */
	public static function get_instance()
	{
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct()
	{
		if ( ! defined( 'GOOGLE_TAG_MANAGER_CONTAINER' ) || '' == GOOGLE_TAG_MANAGER_CONTAINER ) {
			return;
		}

		// Add data layer
		add_action( 'wp_head', array( $this, 'gtm_data_layer' ) );
		add_action( 'login_head', array( $this, 'gtm_data_layer' ) );

		// Add GTM snippet
		add_action( 'after_body_open', array( $this, 'gtm_tag' ) );

		// Fallback to footer if no header action exists
		add_action( 'wp_footer', array( $this, 'gtm_tag' ) );

		// On the login screen we must add it to the footer as there is no hook after the body tag
		add_action( 'login_footer', array( $this, 'gtm_tag' ) );

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
	public function get_data_layer( )
	{
		$data_layer = array();

		// Log if the user is logged in
		$data_layer['loggedIn'] = is_user_logged_in();

		// Log current post type
		$post_type = get_post_type();
		if ( ! empty( $post_type ) ) {
			$data_layer['postType'] = get_post_type();
		}


		$data_layer = apply_filters( 'google_tag_manager_data_layer', $data_layer );

		return json_encode( $data_layer );
	}

	/**
	 * Prints the data layer tag
	 */
	public function gtm_data_layer()
	{
		$data_layer = $this->get_data_layer();
		echo '<script> dataLayer = [' . $data_layer .'  ];</script>';
	}

	/**
	 * Prints the GTM snippet
	 */
	public function gtm_tag()
	{
		// Return if tag has already been echoed
		if ( $this->echoed ) {
			return;
		}

		$this->echoed = true;

		?>
		<!-- Google Tag Manager -->
		<noscript><iframe src="//www.googletagmanager.com/ns.html?id=<?php echo GOOGLE_TAG_MANAGER_CONTAINER?>"
		height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','<?php echo GOOGLE_TAG_MANAGER_CONTAINER?>');</script>
		<!-- End Google Tag Manager -->
		<?php
	}
}

add_action( 'init', array( 'Google_Tag_Manager', 'get_instance' ) );