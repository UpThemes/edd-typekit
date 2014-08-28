<?php

/**
 * Returns the user's Typekit ID in a remote license key check call.
 */
function edd_tk_return_kit_id( $data, $args, $license_id ){

	$personal_kit_id  = get_post_meta( $license_id, '_edd_tk_kit_id', true );

	if( $personal_kit_id ){
		$data['typekit_id'] = $personal_kit_id;
	}

	return $data;

}

add_action( 'edd_remote_license_activation_response', 'edd_tk_return_kit_id', 3, 3 );

/**
 * Set up license manager class when plugins are loaded.
 */
add_action( 'plugins_loaded', array( 'EDD_Typekit_Kit_License_Manager_Admin', 'get_instance' ) );
