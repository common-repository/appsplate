<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Appsplate
 * @subpackage Appsplate/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Appsplate
 * @subpackage Appsplate/includes
 * @author     Your Name <email@example.com>
 */
class Appsplate_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
    {
        global $wpdb;
        // include upgrade-functions for maybe_create_table;
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'appsplate_checkout';
        $sql = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            `code` tinytext NOT NULL,
            `order` text NOT NULL,
            PRIMARY KEY  (id)
        ) $charsetCollate;";
        $success = maybe_create_table($tableName, $sql);
	}

}
