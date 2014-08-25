<?php

/**
 * This class manages font kits with Typekit.
 *
 * @package EDD_Typekit_Kit_License_Manager_Admin
 * @author  Chris Wallace <chris@liftux.com>
 */
class EDD_Typekit_Kit_License_Manager_Admin  {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of the Typekit class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $typekit = null;

	/**
	 * Instance of the EDD settings.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $edd_settings = null;

	/**
	 * Instance of the typekit token.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $typekit_token = null;

	/**
	 * Instance of the user's license key.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $license_key = null;

	/**
	 * Instance of the personal kit ID.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $personal_kit_id = null;

	/**
	 * Instance of download ID.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $download_id = null;

	/**
	 * Instance of the default kit ID for user's purchased product.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $default_kit_id = null;

	/**
	 * Instance of the user's active sites.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	static $sites = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'edd_sl_activate_license', array( $this, 'edd_tk_sites_update' ), 10, 5 );
		add_action( 'updated_postmeta', array( $this, 'edd_tk_sites_update' ), 10, 5 );

		add_action( 'edd_tk_update_kit',array( $this, 'edd_tk_update_kit_domains' ), 1, 1 );

		//add_action( 'edd_sl_deactivate_license', 'edd_tk_deactivate_fontkit' );

		$this->typekit = new Typekit();

		$this->edd_settings     = get_option( 'edd_settings' );
		$this->typekit_token    = $edd_settings[ 'edd_tk_api' ];

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Creates a Typekit kit when license key is activated.
	 *
	 * @since 1.0
	 */
	function edd_tk_sites_update( $meta_id, $object_id, $meta_key, $meta_value ){

		if( $meta_key !== '_edd_sl_sites' )
			return;

		$this->license_key      = get_post_meta( $object_id, '_edd_sl_key', true );
		$this->personal_kit_id  = get_post_meta( $object_id, '_edd_tk_kit_id', true );

		$this->download_id      = get_post_meta( $object_id, '_edd_sl_download_id', true );
		$this->default_kit_id   = get_post_meta( $this->download_id, '_edd_tk_kit_id', true );

		$this->edd_settings    = get_option( 'edd_settings' );
		$this->typekit_token   = $this->edd_settings[ 'edd_tk_api' ];

		if( ! isset( $this->typekit_token ) )
			return;

		do_action( 'edd_tk_update_kit', $object_id );

	}

	/**
	 * Removes a Typekit kit when license key is deactivated.
	 *
	 * @since 1.0
	 */
	function edd_tk_update_kit_domains( $object_id ){

		$this->sites = get_post_meta( $object_id, '_edd_sl_sites', true );

		$this->default_kit_info = $this->typekit->get( $this->default_kit_id, $this->typekit_token );

		if( ! $this->personal_kit_id ):
			$this->edd_tk_create_kit( $object_id );
		elseif( $this->personal_kit_id ):
			$this->edd_tk_update_kit( $this->sites );
		else:
			$this->edd_tk_update_kit( array() );
		endif;

	}

	/**
	 * Creates the Typekit kit
	 */
	function edd_tk_create_kit( $license_id ){

		$new_kit_info             = array();
		$new_kit_info['name']     = $this->download_id . " - " . $this->default_kit_id;
		$new_kit_info['domains']  = $this->sites;
		$new_kit_info['families'] = $this->default_kit_info['kit']['families'];

		$new_kit_id = $this->typekit->create( $new_kit_info, $this->typekit_token );

		if( $new_kit_id ){
			$post_meta = add_post_meta( $license_id, $new_kit_id, true );
			$this->typekit->publish( $new_kit_id, $this->typekit_token );
		}

		print_r($post_meta);

	}

	/**
	 * Creates the Typekit kit
	 */
	function edd_tk_update_kit( $sites ){

		$new_kit_info             = array();
		$new_kit_info['id']       = $this->personal_kit_id;
		$new_kit_info['domains']  = $sites;
		$new_kit_info['families'] = $this->default_kit_info['kit']['families'];

		$new_kit_id = $typekit->update( $new_kit_info, $this->typekit_token );

		$this->typekit->publish( $this->personal_kit_id, $this->typekit_token );

	}

	/**
	 * Removes a Typekit kit when license key is deactivated.
	 *
	 * @since 1.0
	 */

	/*function edd_tk_deactivate_fontkit( $license_id, $download_id ){

		echo "<pre>";
		print_r($license_id);
		echo "</pre>";

		echo "<pre>";
		print_r($license_id);
		echo "</pre>";

		exit;

	}

	add_action( 'edd_sl_deactivate_license', 'edd_tk_deactivate_fontkit' );*/

}