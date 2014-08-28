<?php
/**
 * Easy Digital Downloads - Typekit Kit Manager
 *
 * A plugin that allows EDD licenses to generate font kits from Typekit.
 *
 * @package   EDD_Typekit_Kit_Manager
 * @author    Chris Wallace <chris@liftux.com>
 * @license   GPL-2.0+
 * @link      http://upthemes.com
 * @copyright 2014 Chris Wallace
 *
 * @wordpress-plugin
 * Plugin Name:       Easy Digital Downloads - Typekit Kit Manager
 * Plugin URI:        http://upthemes.com
 * Description:       A plugin that allows EDD licenses to generate font kits from Typekit.
 * Version:           0.0.1
 * Author:            Chris Wallace
 * Author URI:        http://chriswallace.net
 * Text Domain:       edd_typekit
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: git@github.com:UpThemes/edd-typekit.git
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( !defined( 'EDDTK_PLUGIN_DIR' ) ) {
	define( 'EDDTK_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( !defined( 'EDDTK_PLUGIN_URL' ) ) {
	define( 'EDDTK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

define( 'EDD_TYPEKIT_VERSION', '0.0.1' );

if( class_exists( 'EDD_License' ) && is_admin() ) {
	$edd_typekit_license = new EDD_License( __FILE__, 'Typekit Font Manager', EDD_TYPEKIT_VERSION, 'Chris Wallace', 'typekit_api_key' );
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/remote_activation.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-edd_typekit_license_manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/php-typekit/typekit-client.php' );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-edd_typekit-admin.php' );
	add_action( 'plugins_loaded', array( 'EDD_Typekit_Kit_Manager_Admin', 'get_instance' ) );

}
