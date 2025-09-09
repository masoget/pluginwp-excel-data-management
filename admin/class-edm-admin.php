<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.0.0
 */
class EDM_Admin {

    // --- Properties bisa ditambahkan di sini ---
    private $plugin_name;
    private $version;

    /**
     * Constructor.
     *
     * @param string $plugin_name
     * @param string $version
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
    }

    /**
     * Register the settings for the plugin using the Settings API.
     *
     * @since 1.1.0
     */
    public function register_plugin_settings() {
        register_setting(
            'edm_settings_group', // Option group
            'edm_settings',       // Option name
            array( $this, 'sanitize_settings' ) // Sanitize callback
        );
    }

    /**
     * Sanitize each setting field as needed.
     *
     * @param array $input
     * @return array Sanitized input
     * @since 1.1.0
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = array();

        // Allow Frontend Upload
        $sanitized_input['allow_frontend_upload'] =
            ( isset( $input['allow_frontend_upload'] ) && $input['allow_frontend_upload'] === 'on' ) ? 'on' : 'off';

        // Table Style
        if ( isset( $input['table_style'] ) ) {
            $allowed_styles = array( 'dark', 'light', 'system' );
            $sanitized_input['table_style'] = in_array( $input['table_style'], $allowed_styles, true )
                ? $input['table_style']
                : 'dark';
        }

        // Show Search Bar
        $sanitized_input['show_search_bar'] =
            ( isset( $input['show_search_bar'] ) && $input['show_search_bar'] === 'on' ) ? 'on' : 'off';

        // Minimum Role to View
        if ( isset( $input['min_role_view'] ) ) {
            $allowed_roles = array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' );
            $sanitized_input['min_role_view'] = in_array( $input['min_role_view'], $allowed_roles, true )
                ? $input['min_role_view']
                : 'subscriber';
        }

        return $sanitized_input;
    }

    /**
     * Enqueue styles for admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            EDM_PLUGIN_URL . 'admin/css/edm-admin-styles.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue scripts for admin area.
     */
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_excel-data-manager' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            EDM_PLUGIN_URL . 'admin/js/edm-admin-scripts.js',
            array( 'jquery' ),
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_name,
            'edm_ajax_object',
            array(
                'ajax_url'       => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'edm_upload_nonce' ),
                'get_list_nonce' => wp_create_nonce( 'edm_get_list_nonce' ),
                'delete_nonce'   => wp_create_nonce( 'edm_delete_nonce' ),
                'view_nonce'     => wp_create_nonce( 'edm_view_nonce' ),
            )
        );
    }

    /**
     * Add admin menu and submenu.
     */
    public function add_admin_menu() {
        add_menu_page(
            'Excel Data Manager',
            'Excel Manager',
            'manage_excel_data',
            $this->plugin_name,
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-database-import',
            26
        );

        add_submenu_page(
            $this->plugin_name,
            'Kelola File',
            'Kelola File',
            'manage_excel_data',
            'edm-manage-files',
            array( $this, 'display_manage_files_page' )
        );
    }

    /**
     * Main admin page.
     */
    public function display_plugin_admin_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/edm-admin-display.php';
    }

    /**
     * Manage files page.
     */
    public function display_manage_files_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/edm-manage-files-display.php';
    }

    /**
     * Save spreadsheet data to a new DB table.
     */
    private function save_data_to_db( $data ) {
        global $wpdb;

        $headers            = array_shift( $data );
        $sanitized_headers  = array_map( fn( $h ) => sanitize_key( str_replace( ' ', '_', $h ) ), $headers );
        $table_name_base    = 'edm_data_' . time();
        $table_name_full    = $wpdb->prefix . $table_name_base;
        $charset_collate    = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name_full (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            " . implode( ",\n", array_map( fn( $h ) => "`$h` TEXT", $sanitized_headers ) ) . ",
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        foreach ( $data as $row ) {
            $insert_data = array_combine( $sanitized_headers, $row );
            $wpdb->insert( $table_name_full, $insert_data );
        }

        return $table_name_base;
    }

    /**
     * Record uploaded file info.
     */
    private function record_upload( $original_filename, $table_name ) {
        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        $wpdb->insert(
            $uploads_table,
            array(
                'original_filename' => sanitize_file_name( $original_filename ),
                'stored_filename'   => sanitize_file_name( $original_filename ),
                'table_name'        => $table_name,
                'uploaded_by'       => get_current_user_id(),
                'upload_date'       => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Handle AJAX: get file list.
     */
    public function handle_get_file_list() {
        check_ajax_referer( 'edm_get_list_nonce', 'security' );

        if ( ! current_user_can( 'manage_excel_data' ) ) {
            wp_send_json_error( 'You do not have permission to view this data.' );
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';
        $users_table   = $wpdb->users;

        $files = $wpdb->get_results(
            "SELECT 
                uploads.id, 
                uploads.original_filename, 
                uploads.table_name,
                uploads.upload_date, 
                users.display_name 
             FROM {$uploads_table} AS uploads
             LEFT JOIN {$users_table} AS users ON uploads.uploaded_by = users.ID
             ORDER BY uploads.upload_date DESC"
        );

        is_wp_error( $files )
            ? wp_send_json_error( 'Failed to retrieve file list.' )
            : wp_send_json_success( $files );
    }

    /**
     * Handle AJAX: delete file.
     */
    public function handle_delete_file() {
        check_ajax_referer( 'edm_delete_nonce', 'security' );

        if ( ! current_user_can( 'manage_excel_data' ) ) {
            wp_send_json_error( 'You do not have permission to delete files.' );
        }

        if ( ! isset( $_POST['file_id'] ) || ! is_numeric( $_POST['file_id'] ) ) {
            wp_send_json_error( 'Invalid file ID.' );
        }

        $file_id       = intval( $_POST['file_id'] );
        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        $table_name = $wpdb->get_var( $wpdb->prepare(
            "SELECT table_name FROM $uploads_table WHERE id = %d",
            $file_id
        ) );

        if ( ! $table_name ) {
            wp_send_json_error( 'Record not found.' );
        }

        $deleted = $wpdb->delete( $uploads_table, array( 'id' => $file_id ), array( '%d' ) );
        if ( false === $deleted ) {
            wp_send_json_error( 'Failed to delete record.' );
        }

        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table_name}" );

        wp_send_json_success( 'File and its data deleted successfully.' );
    }

    /**
     * Handle AJAX: get table data (with pagination + search).
     */
    public function handle_get_table_data() {
        check_ajax_referer( 'edm_view_nonce', 'security' );

        if ( ! current_user_can( 'manage_excel_data' ) ) {
            wp_send_json_error( 'You do not have permission to view data.' );
        }

        $file_id      = isset( $_POST['file_id'] ) ? intval( $_POST['file_id'] ) : 0;
        $page         = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $search_term  = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $items_per_page = 20;

        if ( $file_id <= 0 ) {
            wp_send_json_error( 'Invalid file ID.' );
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        $file_info = $wpdb->get_row(
            $wpdb->prepare( "SELECT table_name, original_filename FROM $uploads_table WHERE id = %d", $file_id )
        );

        if ( ! $file_info ) {
            wp_send_json_error( 'File not found.' );
        }

        $table_name = $wpdb->prefix . $file_info->table_name;
        $headers    = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );

        if ( empty( $headers ) ) {
            wp_send_json_error( 'Could not retrieve table structure.' );
        }

        $data_columns = array_slice( $headers, 1 );

        $base_query   = " FROM {$table_name}";
        $where_clause = '';
        $params       = array();

        if ( ! empty( $search_term ) && ! empty( $data_columns ) ) {
            $clauses = array();
            foreach ( $data_columns as $col ) {
                $clauses[] = "`{$col}` LIKE %s";
                $params[]  = '%' . $wpdb->esc_like( $search_term ) . '%';
            }
            $where_clause = " WHERE (" . implode( ' OR ', $clauses ) . ")";
        }

        $total_items = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) " . $base_query . $where_clause,
            $params
        ) );

        $offset = ( $page - 1 ) * $items_per_page;
        $params[] = $items_per_page;
        $params[] = $offset;

        $data = $wpdb->get_results( $wpdb->prepare(
            "SELECT * " . $base_query . $where_clause . " ORDER BY id ASC LIMIT %d OFFSET %d",
            $params
        ), ARRAY_A );

        wp_send_json_success( array(
            'headers'    => $data_columns,
            'data'       => $data,
            'filename'   => $file_info->original_filename,
            'pagination' => array(
                'total_items'  => (int) $total_items,
                'total_pages'  => ceil( $total_items / $items_per_page ),
                'current_page' => $page,
            ),
        ) );
    }

    /**
     * Handle AJAX: file upload.
     */
    public function handle_file_upload() {
        check_ajax_referer( 'edm_upload_nonce', 'security' );

        if ( ! current_user_can( 'manage_excel_data' ) ) {
            wp_send_json_error( 'You do not have permission to upload files.' );
        }

        if ( ! isset( $_FILES['excel_file'] ) ) {
            wp_send_json_error( 'No file uploaded.' );
        }

        $file = $_FILES['excel_file'];

        // Validate file type
        $allowed_types = array( 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            wp_send_json_error( 'Invalid file type. Please upload an Excel file (.xls or .xlsx).' );
        }

        // Validate file size (10MB limit)
        if ( $file['size'] > 10 * 1024 * 1024 ) {
            wp_send_json_error( 'File size exceeds 10MB limit.' );
        }

        // Process the Excel file
        try {
            $spreadsheet = IOFactory::load( $file['tmp_name'] );
            $worksheet = $spreadsheet->getActiveSheet();
            $data = array();

            // Get highest row and column
            $highest_row = $worksheet->getHighestRow();
            $highest_column = $worksheet->getHighestColumn();

            // Convert column to number
            $highest_column_index = Coordinate::columnIndexFromString($highest_column);

            // Read data
            for ($row = 1; $row <= $highest_row; $row++) {
                $row_data = array();
                for ($col = 1; $col <= $highest_column_index; $col++) {
                    $cell = $worksheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
                    $row_data[] = $cell->getValue();
                }
                $data[] = $row_data;
            }

            // Save data to database
            $table_name = $this->save_data_to_db( $data );

            // Record upload
            $this->record_upload( $file['name'], $table_name );

            wp_send_json_success( array( 'message' => 'File uploaded and processed successfully.' ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( 'Error processing file: ' . $e->getMessage() );
        }
    }
}
