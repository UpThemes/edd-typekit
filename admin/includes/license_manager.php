<?php

/**
 * Creates a Typekit kit when license key is activated.
 *
 * @since 1.0
 */
function edd_tk_sites_update( $meta_id, $object_id, $meta_key, $meta_value ){

	if( $meta_key !== '_edd_sl_sites' )
		return;

	$edd_settings    = get_option( 'edd_settings' );
	$typekit_token   = $edd_settings[ 'edd_tk_api' ];

	if( ! isset( $typekit_token ) )
		return

	$sites = $meta_value;

	$license_key     = get_post_meta( $object_id, '_edd_sl_key', true );
	$license_status  = get_post_meta( $object_id, '_edd_sl_status', true );

	if ( 'active' !== $license_status ){
		do_action( 'edd_tk_delete_kit', $meta_id, $object_id, $meta_key );
		return;
	}

	do_action( 'edd_tk_update_kit', $object_id );

}

//add_action( 'edd_sl_activate_license', 'edd_tk_activate_fontkit' );
add_action( 'updated_postmeta', 'edd_tk_sites_update', 10, 5 );


/**
 * Removes a Typekit kit when license key is deactivated.
 *
 * @since 1.0
 */

function edd_tk_update_kit_domains( $object_id ){

	$edd_settings     = get_option( 'edd_settings' );
	$typekit_token    = $edd_settings[ 'edd_tk_api' ];

	$license_key      = get_post_meta( $object_id, '_edd_sl_key', true );

	$download_id      = get_post_meta( $object_id, '_edd_sl_download_id', true );
	$default_kit_id   = get_post_meta( $download_id, '_edd_tk_kit_id', true );

	$sites            = get_post_meta( $object_id, '_edd_sl_sites' );

	$typekit          = new Typekit();

	$kit_info         = $typekit->get( $default_kit_id, $typekit_token );

	$previous_domains = $kit_info[ 'kit' ][ 'domains' ];
	$new_domains      = $sites[0];

	$merged_domains   = array_merge( $previous_domains, $new_domains );

	unset( $kit_info[ 'domains' ] );
	$kit_info[ 'domains' ] = $merged_domains;

	$updated          = $typekit->update( $default_kit_id, $kit_info, $typekit_token );

	echo "<pre>";
	print_r( $updated );
	echo "</pre>";
	exit;

}

add_action( 'edd_tk_update_kit', 'edd_tk_update_kit_domains' );

//add_action( 'edd_sl_deactivate_license', 'edd_tk_deactivate_fontkit' );

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