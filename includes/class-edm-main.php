<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 */
class EDM_Main {

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = EDM_VERSION;
        $this->plugin_name = 'excel-data-manager';
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Class for admin-facing functionality
        require_once EDM_PLUGIN_DIR . 'admin/class-edm-admin.php';
        // Class for public-facing functionality
        require_once EDM_PLUGIN_DIR . 'public/class-edm-public.php';
    }


    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $plugin_admin = new EDM_Admin( $this->get_plugin_name(), $this->get_version() );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $plugin_admin, 'add_admin_menu' ) );

        // Register the AJAX action hook for file upload
        add_action( 'wp_ajax_edm_upload_file', array( $plugin_admin, 'handle_file_upload' ) );

        // Register the AJAX action hook for fetching the file list
        add_action( 'wp_ajax_edm_get_file_list', array( $plugin_admin, 'handle_get_file_list' ) );
        
        // Register the AJAX action hook for deleting a file
        add_action( 'wp_ajax_edm_delete_file', array( $plugin_admin, 'handle_delete_file' ) );

        // Register the AJAX action hook for getting table data for viewing
        add_action( 'wp_ajax_edm_get_table_data', array( $plugin_admin, 'handle_get_table_data' ) );
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new EDM_Public( $this->get_plugin_name(), $this->get_version() );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_action( 'init', array( $plugin_public, 'register_shortcodes' ) );
        
        // Register AJAX actions for public functionality
        add_action( 'wp_ajax_edm_search_data', array( $plugin_public, 'handle_search_request' ) );
        add_action( 'wp_ajax_nopriv_edm_search_data', array( $plugin_public, 'handle_search_request' ) );
        
        add_action( 'wp_ajax_edm_submit_form', array( $plugin_public, 'handle_form_submission' ) );
        add_action( 'wp_ajax_nopriv_edm_submit_form', array( $plugin_public, 'handle_form_submission' ) );
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}




