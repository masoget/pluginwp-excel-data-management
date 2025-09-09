<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://example.com
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Remove custom roles and capabilities
remove_role( 'excel_data_viewer' );
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
    $admin_role->remove_cap( 'view_excel_data' );
    $admin_role->remove_cap( 'manage_excel_data' );
}
$editor_role = get_role( 'editor' );
if ( $editor_role ) {
    $editor_role->remove_cap( 'view_excel_data' );
}


// 2. Drop all dynamically created data tables
$uploads_table = $wpdb->prefix . 'edm_uploads';
$upload_infos = $wpdb->get_results( "SELECT table_name FROM $uploads_table" );

if ( ! empty( $upload_infos ) ) {
    foreach ( $upload_infos as $info ) {
        $data_table_name = $wpdb->prefix . $info->table_name;
        $wpdb->query( "DROP TABLE IF EXISTS {$data_table_name}" );
    }
}


// 3. Drop the main management table
$wpdb->query( "DROP TABLE IF EXISTS {$uploads_table}" );
