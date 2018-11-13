<?php
/**
 * Plugin Name:       ACF Multi-steps Form
 * Plugin URI:        https://saika.li/multi-steps-form-acf/
 * Description:       A proof-of-concept on how to create a multi-steps ACF form with follow-up link.
 * Version:           1.0.0
 * Author:            Pierre Saïkali
 * Author URI:        https://saika.li
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       amf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * Define global constants.
 *
 * @since 1.0.0
 */
// Plugin version.
if ( ! defined( 'AMF_VERSION' ) ) {
	define( 'AMF_VERSION', '1.0.0' );
}

if ( ! defined( 'AMF_NAME' ) ) {
	define( 'AMF_NAME', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
}

if ( ! defined( 'AMF_DIR' ) ) {
	define( 'AMF_DIR', WP_PLUGIN_DIR . '/' . AMF_NAME );
}

if ( ! defined( 'AMF_URL' ) ) {
	define( 'AMF_URL', WP_PLUGIN_URL . '/' . AMF_NAME );
}

/**
 * Include obligatory files.
 */
require_once AMF_DIR . '/src/class-utils.php';
require_once AMF_DIR . '/src/class-shortcode.php';

/**
 * Fire the darn thing!
 */
new ACF_Multiforms_Example\Shortcode();
