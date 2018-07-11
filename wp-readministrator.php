<?php
/**
* @package wp-readministrator
*/

/**
 * Plugin Name: Readministrator (Read Only Administrator)
 * Plugin URI: https://github.com/dhanendran/wp-readministrator
 * Description: Allowing users to see the admin settings page. Just Seeing, No edit allowed :) These users will have all the privilege of editors along with that they will have the ability to see the admin settings.
 * Version: 0.0.1
 * Author: Dhanendran Rajagopal
 * Author URI: https://dhanendranrajagopal.me/
 * License: GPLv3 or later
 * Text Domain: wpreadmin
 */

/**
 * Adding new Role with Editor capabilities on plugin activation.
 */
function wpreadmin_add_role() {
	$editor       = get_role( 'editor' );
	$capabilities = $editor->capabilities;
	$capabilities = array_merge( $capabilities, array( 'manage_options' => 1 ) );

	add_role( 'readministrator', 'Read Only Administrator', $capabilities );
}
register_activation_hook( __FILE__, 'wpreadmin_add_role' );

/**
 * Remove role `readministrator` on plugin uninstall.
 */
function wpreadmin_remove_role() {
	remove_role( 'readministrator' );
}
register_deactivation_hook( __FILE__, 'wpreadmin_remove_role' );

/**
 * Adding style sheet.
 */
function wpreadmin_add_style() {
	wp_enqueue_style( 'wpreadmin-style',  plugin_dir_url( __FILE__ ) . '/style.css' );
}
add_action( 'init', 'wpreadmin_add_style' );

/**
 * Add CSS class `wp-readministrator` to body tag if user is Read Only Admin.
 *
 * @param String $classes Admin CSS classes.
 *
 * @return String
 */
function wpreadmin_add_class( $classes ) {
	$user = wp_get_current_user();
	if ( in_array( 'readministrator', (array) $user->roles ) ) {
		$classes .= ' readministrator ';
	}

	return $classes;
}
add_filter( 'admin_body_class', 'wpreadmin_add_class', 10, 1 );

/**
 * @param $a
 */
function wpreadmin_disable_editing( $a ) {
	global $pagenow;

	$settings_page = array(
		'options-general.php'   => 1,
		'options-writing.php'   => 1,
		'options-reading.php'   => 1,
		'options-media.php'     => 1,
		'options-permalink.php' => 1,
		'privacy.php'           => 1,
	);

//	echo "<pre>1"; print_r($pagenow);die;
}
add_action( 'admin_menu', 'wpreadmin_disable_editing' );

add_action( 'init', function() {
	global $user;
//	echo "<pre>"; print_r(get_role('readministrator'));die;
} );
