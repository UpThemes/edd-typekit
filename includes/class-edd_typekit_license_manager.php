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
	protected $typekit = null;

	/**
	 * Instance of the typekit token.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $typekit_token = null;

	/**
	 * Instance of the user's license key.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $license_key = null;

	/**
	 * Instance of the user's license key.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $license_id = null;

	/**
	 * Status of the user's license key.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $license_status = null;

	/**
	 * Instance of the personal kit ID.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $personal_kit_id = null;

	/**
	 * Instance of download ID.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $download_id = null;

	/**
	 * Instance of the default kit ID for user's purchased product.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $default_kit_id = null;

	/**
	 * Instance of the user's active sites.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected $sites = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'edd_sl_post_set_status', array( $this, 'edd_tk_check_status' ), 2, 2 );

		add_action( 'updated_postmeta', array( $this, 'edd_tk_sites_update' ), 10, 5 );

		add_action( 'edd_sl_pre_activate_license', array( $this, 'load_variables' ), 2, 2 );
		add_action( 'edd_sl_pre_deactivate_license', array( $this, 'load_variables' ), 2, 2 );
		add_action( 'edd_sl_check_license', array( $this, 'load_variables' ), 2, 2 );

		add_action( 'init', array( $this, 'edd_tk_check_valid_kit' ), 1, 1 );

		add_action( 'edd_tk_update_kit', array( $this, 'edd_tk_update_kit_domains' ), 1, 1 );

		add_action( 'edd_remote_license_activation_response', array( $this, 'edd_tk_return_kit_id' ), 3, 3 );

		$this->typekit = new Typekit();

		$edd_settings           = get_option( 'edd_settings' );
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

	public function load_variables( $license_id, $download_id = false ){

		if( ! $license_id )
			return;

		$this->license_id = $license_id;

		if( $download_id )
			$this->download_id = $download_id;


		if( $this->license_id ){
			$this->license_key      = get_post_meta( $this->license_id, '_edd_sl_key', true );
			$this->download_id      = get_post_meta( $this->license_id, '_edd_sl_download_id', true );
		}

		if( $this->download_id ){
			$this->default_kit_id   = get_post_meta( $this->download_id, '_edd_tk_kit_id', true );
		}

	}

	/**
	 * Creates a Typekit kit when license key is activated.
	 *
	 * @since 1.0
	 */
	public function edd_tk_sites_update( $meta_id, $license_id, $meta_key, $meta_value ){

		if( ! isset( $this->typekit_token ) || ( $meta_key !== '_edd_sl_status' && $meta_key !== '_edd_sl_sites' ) )
			return;

		$this->license_id       = $license_id;
		$this->license_key      = get_post_meta( $this->license_id, '_edd_sl_key', true );
		$this->download_id      = get_post_meta( $this->license_id, '_edd_sl_download_id', true );
		$this->default_kit_id   = get_post_meta( $this->download_id, '_edd_tk_kit_id', true );

		if( $meta_key === '_edd_sl_status' ){
			$this->license_status = $meta_value;
		} else {
			$this->license_status = get_post_meta( $this->license_id, '_edd_sl_status', true );
		}

		if( $meta_key === '_edd_sl_sites' ){
			$this->edd_tk_check_status();
		}

	}

	/**
	 * Creates a Typekit kit when license key is activated.
	 *
	 * @since 1.0
	 */
	public function edd_tk_check_status(){

		if( ! did_action( 'edd_tk_update_kit' ) ){
			do_action( 'edd_tk_update_kit' );
		}

	}

	/**
	 * Removes a Typekit kit when license key is deactivated.
	 *
	 * @since 1.0
	 */
	public function edd_tk_update_kit_domains(){

		$this->personal_kit_id  = get_post_meta( $this->license_id, '_edd_tk_kit_id', true );

		$this->sites = get_post_meta( $this->license_id, '_edd_sl_sites', true );
		$this->default_kit_info = $this->typekit->get( $this->default_kit_id, $this->typekit_token );

		if( $this->personal_kit_id ):

			if( isset( $this->license_status ) && ( $this->license_status === 'revoked' || $this->license_status === 'expired' || $this->license_status === 'inactive' ) ){
				$this->sites = 'upthemes.com';
			}

			$this->edd_tk_update_kit();

		else:

			$this->edd_tk_create_kit();

		endif;

	}

	/**
	 * Returns the user's Typekit ID in a remote license key check call.
	 */
	public function edd_tk_return_kit_id( $data, $args, $license_id ){

		$this->load_variables( $license_id );

		$kit_is_valid = $this->edd_tk_check_valid_kit();

		if( $this->personal_kit_id && $kit_is_valid ){
			$data['typekit_id'] = $this->personal_kit_id;
		}

		return $data;

	}

	/**
	 * Check for valid personal kit
	 */
	public function edd_tk_check_valid_kit(){

		if( ! $this->license_id )
			return;

		if( ! $this->typekit_token )
			return;

		if ( ! $this->personal_kit_id ){
			$this->personal_kit_id = get_post_meta( $this->license_id, '_edd_tk_kit_id', true );
		}

		$kit_id = $this->typekit->get( $this->personal_kit_id, $this->typekit_token );

		if( ! $kit_id ){
			delete_post_meta( $this->license_id, '_edd_tk_kit_id' );
			return;
		}

		return true;

	}

	/**
	 * Creates the Typekit kit
	 */
	public function edd_tk_update_kit(){

		if( ! $this->license_id )
			return;

		$this->personal_kit_id  = get_post_meta( $this->license_id, '_edd_tk_kit_id', true );

		$kit_info             = array();
		$kit_info['id']       = $this->personal_kit_id;
		$kit_info['domains']  = $this->sites;
		$kit_info['families'] = $this->default_kit_info['kit']['families'];

		$kit_is_valid = $this->edd_tk_check_valid_kit();

 		if( $kit_is_valid ){

			$kit_id = $this->typekit->update( $this->personal_kit_id, $kit_info, $this->typekit_token );

			if( $kit_id ){
				$this->typekit->publish( $this->personal_kit_id, $this->typekit_token );
			}

		}

	}


	/**
	 * Creates the Typekit kit
	 */
	public function edd_tk_create_kit(){

		if( ! $this->download_id || ! $this->default_kit_id )
			return;

		$new_kit_info             = array();
		$new_kit_info['name']     = $this->download_id . " - " . $this->default_kit_id;
		$new_kit_info['domains']  = $this->sites;
		$new_kit_info['families'] = $this->default_kit_info['kit']['families'];

		$new_kit = $this->typekit->create( $new_kit_info, $this->typekit_token );

		if( $new_kit && is_array( $new_kit ) && $new_kit['kit']['id'] ){
			$post_meta = add_post_meta( $this->license_id, '_edd_tk_kit_id', $new_kit['kit']['id'], true );
			$this->typekit->publish( $new_kit['kit']['id'], $this->typekit_token );
		}

	}
}