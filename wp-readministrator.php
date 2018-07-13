<?php
/**
 * @author            Dhanendran (https://dhanendranrajagopal.me/)
 * @link              https://dhanendranrajagopal.me/
 * @since             1.0.0
 * @package           wp-readministrator
 *
 * @wordpress-plugin
 * Plugin Name: Readministrator (Read Only Administrator)
 * Plugin URI:  https://github.com/dhanendran/wp-readministrator
 * Description: Allowing users to see the admin settings page. Just Seeing, No edit allowed :) These users will have all the privilege of editors along with that they will have the ability to see the admin settings.
 * Version:     0.0.1
 * Author:      Dhanendran
 * Author URI:  https://dhanendranrajagopal.me/
 * License:     GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
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
 * Defining a constant to determine wp-readministrator.
 */
function wpreadmin_define_constant() {
	$user = wp_get_current_user();
	if ( in_array( 'readministrator', (array) $user->roles ) && ! defined( 'WP_READMIN' ) ) {
		define( 'WP_READMIN', true );
	}
}
add_action( 'init', 'wpreadmin_define_constant', 1 );

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
	if ( defined( 'WP_READMIN' ) && WP_READMIN ) {
		$classes .= ' readministrator ';
	}

	return $classes;
}
add_filter( 'admin_body_class', 'wpreadmin_add_class', 10, 1 );

/**
 * Check for secured pages.
 *
 * @return bool
 */
function wpreadmin_is_secured_page() {
	global $pagenow;

	$settings_page = array(
		'options.php'            => 1,
		'options-general.php'    => 1,
		'options-writing.php'    => 1,
		'options-reading.php'    => 1,
		'options-discussion.php' => 1,
		'options-media.php'      => 1,
		'options-permalink.php'  => 1,
		'privacy.php'            => 1,
		'tools.php'              => 1,
	);

	if ( isset( $settings_page[ $pagenow ] ) ) {
		return true;
	}

	return false;
}
/**
 * Show notice on the admin settings page.
 */
function wpreadmin_show_notice() {
	if ( wpreadmin_is_secured_page() ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			'You can only able to see the settings and can\'t make any changes.'
		);
	}
}
add_action( 'admin_notices', 'wpreadmin_show_notice' );

/**
 * Prevent hacking.
 *
 * @param $value
 * @param $option
 * @param $old_value
 *
 * @return mixed
 */
function wpreadmin_preserve_update( $value, $option, $old_value ) {
	if ( wpreadmin_is_secured_page() ) {
		$errors = get_settings_errors();
		foreach ( $errors as $error ) {
			if ( 'wpreamin_save_error' === $error['code'] ) {
				return $old_value;
			}
		}

		add_settings_error('general', 'wpreamin_save_error', __('Cheatin&#8217; uh? You are not allowed to do that.'), 'error');
		return $old_value;
	}

	return $value;
}
add_filter( 'pre_update_option', 'wpreadmin_preserve_update', 1, 3 );

