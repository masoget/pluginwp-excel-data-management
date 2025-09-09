<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class EDM_Activator {

    /**
     * Main activation method.
     *
     * Creates custom database tables and sets up user roles.
     */
    public static function activate() {
        self::create_tables();
        self::setup_roles();
    }

    /**
     * Create custom database tables.
     *
     * A management table `edm_uploads` is created to track uploaded files
     * and the name of the dynamic table that holds its data.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'edm_uploads';

        // SQL to create the main management table
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_filename varchar(255) NOT NULL,
            stored_filename varchar(255) NOT NULL,
            table_name varchar(255) NOT NULL,
            uploaded_by bigint(20) unsigned NOT NULL,
            upload_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Setup custom user roles and capabilities.
     * This logic is based on the provided `role.file`.
     */
    private static function setup_roles() {
        // Remove role first to ensure a clean slate on reactivation
        remove_role( 'excel_data_viewer' );

        // Add the custom role
        add_role(
            'excel_data_viewer',
            __( 'Excel Data Viewer' ),
            array(
                'read' => true, // Basic read capability
                'view_excel_data' => true // Custom capability
            )
        );

        // Add the custom capability to Administrator and Editor roles as well
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'view_excel_data', true );
            $admin_role->add_cap( 'manage_excel_data', true ); // for uploading/managing
        }

        $editor_role = get_role( 'editor' );
        if ( $editor_role ) {
            $editor_role->add_cap( 'view_excel_data', true );
        }
    }
}
