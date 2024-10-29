<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://appsplate.com
 * @since             2.1.3
 * @package           Appsplate
 *
 * @wordpress-plugin
 * Plugin Name:       Appsplate
 * Plugin URI:        https://appsplate.com/?ref=plugininfo
 * Description:       Turn you WordPress website to Android & IOS Native App easily with Appsplate.
 * Version:           2.1.3
 * Requires at least: 5.8
 * Tested up to: 6.3.2
 * Stable tag: 2.1.3
 * Author:            Appsplate
 * Author URI:        https://appsplate.com/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       appsplate
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'APPSPLATE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-appsplate-activator.php
 */
function activate_appsplate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-appsplate-activator.php';
	Appsplate_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-appsplate-deactivator.php
 */
function deactivate_appsplate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-appsplate-deactivator.php';
    Appsplate_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_appsplate' );
register_deactivation_hook( __FILE__, 'deactivate_appsplate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-appsplate.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_appsplate() {

	$plugin = new Appsplate();
	$plugin->run();

}
run_appsplate();
