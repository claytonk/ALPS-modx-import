<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/claytonk
 * @since             1.0.0
 * @package           Modx_Import
 *
 * @wordpress-plugin
 * Plugin Name:       MODX Import
 * Plugin URI:        https://github.com/claytonk/ALPS-MODX-Import-to-WP
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Clayton Kinney
 * Author URI:        https://github.com/claytonk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       modx-import
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
define( 'MODX_IMPORT_VERSION', '1.0.0' );
define( 'MODX_IMPORT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-modx-import-activator.php
 */
function activate_modx_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-modx-import-activator.php';
	Modx_Import_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-modx-import-deactivator.php
 */
function deactivate_modx_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-modx-import-deactivator.php';
	Modx_Import_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_modx_import' );
register_deactivation_hook( __FILE__, 'deactivate_modx_import' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-modx-import.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_modx_import() {

	$plugin = new Modx_Import();
	$plugin->run();

}
run_modx_import();
