<?php
/**
 * Add Typekit key meta box
 *
 * @since 1.0
 */
function edd_tk_render_typekit_meta_box() {

	global $post;

	add_meta_box( 'edd_tk_box', __( 'Typekit Font License Settings', 'edd_tk' ), 'edd_tk_render_fontkit_meta_box', 'download', 'normal', 'core' );

}
add_action( 'add_meta_boxes', 'edd_tk_render_typekit_meta_box', 100 );

/**
 * Render the download information meta box
 *
 * @since 1.0
 */
function edd_tk_render_fontkit_meta_box()	{

	global $post;

	$edd_settings    = get_option( 'edd_settings' );
	$typekit_token   = $edd_settings[ 'edd_tk_api' ];

	if( $typekit_token ){

		// Use nonce for verification
		echo '<input type="hidden" name="edd_tk_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

		echo '<table class="form-table">';

			$enabled         = get_post_meta( $post->ID, '_edd_tk_enabled', true ) ? true : false;
			$display         = $enabled ? '' : ' style="display:none;"';

			echo '<script type="text/javascript">jQuery( document ).ready( function($) {$( "#edd_tk_enabled" ).on( "click",function() {$( ".edd_tk_toggled_row" ).toggle();} )} );</script>';

			echo '<tr>';
				echo '<td class="edd_field_type_text" colspan="2">';
					echo '<input type="checkbox" name="edd_tk_enabled" id="edd_tk_enabled" value="1" ' . checked( true, $enabled, false ) . '/>&nbsp;';
					echo '<label for="edd_tk_enabled">' . __( 'Check to enable Typekit font licensing for this product', 'edd_tk' ) . '</label>';
				echo '<td>';
			echo '</tr>';

			echo '<tr' . $display . ' class="edd_tk_toggled_row">';
				echo '<td class="edd_field_type_textarea" colspan="2">';
					echo '<label for="edd_tk_kit_id">' . __( 'Typekit Kit ID', 'edd_tk' ) . '</label><br/>';
					echo '<select name="edd_tk_kit_id" id="edd_tk_kit_id">';

					echo '<option>' . __( 'Select a Kit', 'edd_tk' ) . '</option>';
					edd_tk_get_typekit_kit_options( $edd_settings );

					echo '</select>';

					echo '<p class="description">' . __( 'Enter the Typekit kit ID to clone for each customer who holds a valid license. Every time a customer activates their license key, this kit will be cloned and their domain will be added to it. Your kits are listed below for reference:', 'edd_tk' ) . '</p>';

				echo '</td>';
			echo '</tr>';

		echo '</table>';

	} else {
		_e( 'Please enter your Typekit API key in the Easy Digital Downloads Settings tab to utilize this feature.' , 'edd_tk' );
	}

}

/**
 * Render the typekit ID select options
 *
 * @since 1.0
 */
function edd_tk_get_typekit_kit_options( $edd_settings ){

	global $post;

	if( ! isset( $edd_settings ) ){
		$edd_settings    = get_option( 'edd_settings' );
	}

	$typekit_kits    = edd_tk_get_typekit_kits( $edd_settings );
	$typekit_kit_id  = get_post_meta( $post->ID, '_edd_tk_kit_id', true );
	$typekit_token   = $edd_settings[ 'edd_tk_api' ];
	$typekit         = new Typekit();

	foreach( $typekit_kits as $kit_array ):
		foreach( $kit_array as $kit ):
			$kit_info = $typekit->get( $kit[ 'id' ], $typekit_token );
			echo '<option value="' . $kit_info[ 'kit' ][ 'id' ] . '" ' . selected( $kit_info[ 'kit' ][ 'id' ], $typekit_kit_id ) . '>';
			echo $kit_info[ 'kit' ][ 'name' ] . ' (' . $kit_info[ 'kit' ][ 'id' ] . ')';
			echo '</option>';
		endforeach;
	endforeach;

}

/**
 * Get all typekit kits
 *
 * @since 1.0
 */
function edd_tk_get_typekit_kits( $edd_settings ){

	global $post;

	if( ! isset( $edd_settings ) ){
		$edd_settings = get_option( 'edd_settings' );
	}

	$typekit_token   = $edd_settings[ 'edd_tk_api' ];

	$typekit         = new Typekit();
	$typekit_kits    = $typekit->get( false, $typekit_token );

	return $typekit_kits;

}

/**
 * Santize our kit ID option on save
 *
 * @since 1.0
 */
function edd_tk_sanitize_typekit_kit( $input ){

	global $post;

	if( ! isset( $edd_settings ) ){
		$edd_settings = get_option( 'edd_settings' );
	}

	$typekit_kits = edd_tk_get_typekit_kits( $edd_settings );

	$typekit_kit_ids = array();

	foreach( $typekit_kits as $kit_array ):
		foreach( $kit_array as $kit ):
			$typekit_kit_ids[] = $kit[ 'id' ];
		endforeach;
	endforeach;

	if( ! in_array( $input, $typekit_kit_ids ) ){
		return '';
	}

	return $input;

}

/**
 * Save data from meta box
 *
 * @since 1.0
 */
function edd_tk_fontkit_meta_box_save( $post_id ) {

	global $post;

	// verify nonce
	if ( ! isset( $_POST['edd_tk_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['edd_tk_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// Check for auto save / bulk edit
	if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return $post_id;
	}

	if ( isset( $_POST['post_type'] ) && 'download' != $_POST['post_type'] ) {
		return $post_id;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	if ( isset( $_POST['edd_tk_enabled'] ) ) {
		update_post_meta( $post_id, '_edd_tk_enabled', true );
	} else {
		delete_post_meta( $post_id, '_edd_tk_enabled' );
	}

	if ( isset( $_POST['edd_tk_enabled'] ) && isset( $_POST['edd_tk_kit_id'] ) ) {
		update_post_meta( $post_id, '_edd_tk_kit_id', edd_tk_sanitize_typekit_kit( $_POST['edd_tk_kit_id'] ) );
	} else {
		delete_post_meta( $post_id, '_edd_tk_kit_id' );
	}

}
add_action( 'save_post', 'edd_tk_fontkit_meta_box_save' );