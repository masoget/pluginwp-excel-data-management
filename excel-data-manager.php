<?php
/**
 * Plugin Name:       Excel Data Manager New Design
 * Plugin URI:        https://example.com/
 * Description:       A plugin to import and manage data from Excel files with role-based access control.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       excel-data-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'EDM_VERSION', '1.0.0' );
define( 'EDM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require Composer's autoloader
if ( file_exists( EDM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once EDM_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-edm-activator.php
 */
function activate_excel_data_manager() {
    require_once EDM_PLUGIN_DIR . 'includes/class-edm-activator.php';
    EDM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-edm-deactivator.php
 */
function deactivate_excel_data_manager() {
    require_once EDM_PLUGIN_DIR . 'includes/class-edm-deactivator.php';
    EDM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_excel_data_manager' );
register_deactivation_hook( __FILE__, 'deactivate_excel_data_manager' );

/**
 * Begins execution of the plugin.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-edm-main.php';

function run_excel_data_manager() {
    $plugin = new EDM_Main();
    $plugin->run();
}

run_excel_data_manager();
