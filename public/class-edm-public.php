<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 */
class EDM_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            EDM_PLUGIN_URL . 'public/css/edm-public-styles.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            EDM_PLUGIN_URL . 'public/js/edm-public-scripts.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script for AJAX functionality
        wp_localize_script(
            $this->plugin_name,
            'edm_public_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('edm_public_nonce')
            )
        );
    }

    /**
     * Register shortcodes
     *
     * @since 1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('excel_display', array($this, 'display_excel_data'));
        add_shortcode('excel_search', array($this, 'search_excel_data'));
        add_shortcode('excel_form', array($this, 'form_excel_data'));
    }

    /**
     * Shortcode to display Excel data in a table
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function display_excel_data($atts) {
        // Check if user has permission to view data
        if (!$this->user_can_view_data()) {
            return '<p>You do not have permission to view this data.</p>';
        }

        $atts = shortcode_atts(array(
            'file_id' => '',
            'columns' => '',
            'limit' => ''
        ), $atts, 'excel_display');

        if (empty($atts['file_id'])) {
            return '<p>Error: file_id is required. Please specify a valid file ID, e.g., [excel_display file_id="1"].</p>';
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        // Get file info
        $file_info = $wpdb->get_row($wpdb->prepare(
            "SELECT table_name, original_filename FROM $uploads_table WHERE id = %d",
            $atts['file_id']
        ));

        if (!$file_info) {
            return '<p>Error: File not found.</p>';
        }

        $table_name = $wpdb->prefix . $file_info->table_name;

        // Get headers
        $headers = $wpdb->get_col("DESCRIBE {$table_name}", 0);
        if (empty($headers)) {
            return '<p>Error: Could not retrieve table structure.</p>';
        }

        // Remove 'id' column from headers
        $data_columns = array_slice($headers, 1);

        // Filter columns if specified
        if (!empty($atts['columns'])) {
            $requested_columns = explode(',', $atts['columns']);
            $filtered_columns = array();
            foreach ($requested_columns as $col) {
                $col = trim($col);
                if (in_array($col, $data_columns)) {
                    $filtered_columns[] = $col;
                }
            }
            if (!empty($filtered_columns)) {
                $data_columns = $filtered_columns;
            }
        }

        // Build query
        $query = "SELECT * FROM {$table_name}";
        $params = array();

        if (!empty($atts['limit']) && is_numeric($atts['limit'])) {
            $query .= " LIMIT %d";
            $params[] = intval($atts['limit']);
        }

        if (!empty($params)) {
            $data = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $data = $wpdb->get_results($query, ARRAY_A);
        }

        // Start building output
        ob_start();
        ?>
        <div class="edm-public-table-container">
            <table class="edm-public-table">
                <thead>
                    <tr>
                        <?php foreach ($data_columns as $column): ?>
                            <th><?php echo esc_html(str_replace('_', ' ', $column)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($data_columns as $column): ?>
                                    <td><?php echo esc_html($row[$column]); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo count($data_columns); ?>">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode to display Excel data with search functionality
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function search_excel_data($atts) {
        // Check if user has permission to view data
        if (!$this->user_can_view_data()) {
            return '<p>You do not have permission to view this data.</p>';
        }

        $atts = shortcode_atts(array(
            'file_id' => ''
        ), $atts, 'excel_search');

        if (empty($atts['file_id'])) {
            return '<p>Error: file_id is required. Please specify a valid file ID, e.g., [excel_search file_id="1"].</p>';
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        // Get file info
        $file_info = $wpdb->get_row($wpdb->prepare(
            "SELECT table_name, original_filename FROM $uploads_table WHERE id = %d",
            $atts['file_id']
        ));

        if (!$file_info) {
            return '<p>Error: File not found.</p>';
        }

        // Generate a unique ID for this instance
        $instance_id = uniqid('edm_search_');

        // Create the search form and table container
        ob_start();
        ?>
        <div class="edm-search-container" id="<?php echo esc_attr($instance_id); ?>">
            <div class="edm-search-box">
                <input type="text" class="edm-search-input" placeholder="Search..." data-file-id="<?php echo esc_attr($atts['file_id']); ?>">
                <button class="edm-search-btn">Search</button>
            </div>
            <div class="edm-search-results">
                <div class="edm-loader" style="display: none;">Loading...</div>
                <div class="edm-search-table-container">
                    <!-- Table will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode to display a form for adding data to Excel file
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function form_excel_data($atts) {
        // Check if user has permission to view data
        if (!$this->user_can_view_data()) {
            return '<p>You do not have permission to view this data.</p>';
        }

        $atts = shortcode_atts(array(
            'file_id' => ''
        ), $atts, 'excel_form');

        if (empty($atts['file_id'])) {
            return '<p>Error: file_id is required. Please specify a valid file ID, e.g., [excel_form file_id="1"].</p>';
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        // Get file info
        $file_info = $wpdb->get_row($wpdb->prepare(
            "SELECT table_name, original_filename FROM $uploads_table WHERE id = %d",
            $atts['file_id']
        ));

        if (!$file_info) {
            return '<p>Error: File not found.</p>';
        }

        $table_name = $wpdb->prefix . $file_info->table_name;

        // Get headers
        $headers = $wpdb->get_col("DESCRIBE {$table_name}", 0);
        if (empty($headers)) {
            return '<p>Error: Could not retrieve table structure.</p>';
        }

        // Remove 'id' column from headers
        $data_columns = array_slice($headers, 1);

        // Generate a unique ID for this instance
        $instance_id = uniqid('edm_form_');

        // Create the form
        ob_start();
        ?>
        <div class="edm-form-container" id="<?php echo esc_attr($instance_id); ?>">
            <form class="edm-data-form" data-file-id="<?php echo esc_attr($atts['file_id']); ?>">
                <?php foreach ($data_columns as $column): ?>
                    <div class="edm-form-group">
                        <label for="<?php echo esc_attr($instance_id . '_' . $column); ?>">
                            <?php echo esc_html(str_replace('_', ' ', $column)); ?>
                        </label>
                        <input type="text" 
                               id="<?php echo esc_attr($instance_id . '_' . $column); ?>" 
                               name="<?php echo esc_attr($column); ?>" 
                               class="edm-form-input"
                               required>
                    </div>
                <?php endforeach; ?>
                <div class="edm-form-group">
                    <button type="submit" class="edm-form-submit">Submit</button>
                </div>
                <div class="edm-form-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX request for search functionality
     *
     * @since 1.0.0
     */
    public function handle_search_request() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edm_public_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if ($file_id <= 0) {
            wp_send_json_error('Invalid file ID');
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        // Get file info
        $file_info = $wpdb->get_row($wpdb->prepare(
            "SELECT table_name, original_filename FROM $uploads_table WHERE id = %d",
            $file_id
        ));

        if (!$file_info) {
            wp_send_json_error('File not found');
        }

        $table_name = $wpdb->prefix . $file_info->table_name;

        // Get headers
        $headers = $wpdb->get_col("DESCRIBE {$table_name}", 0);
        if (empty($headers)) {
            wp_send_json_error('Could not retrieve table structure');
        }

        // Remove 'id' column from headers
        $data_columns = array_slice($headers, 1);

        // Build query
        $base_query = " FROM {$table_name}";
        $where_clause = '';
        $params = array();

        if (!empty($search_term) && !empty($data_columns)) {
            $clauses = array();
            foreach ($data_columns as $col) {
                $clauses[] = "`{$col}` LIKE %s";
                $params[] = '%' . $wpdb->esc_like($search_term) . '%';
            }
            $where_clause = " WHERE (" . implode(' OR ', $clauses) . ")";
        }

        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT * " . $base_query . $where_clause . " ORDER BY id ASC LIMIT 50",
            $params
        ), ARRAY_A);

        // Prepare response
        $response = array(
            'headers' => $data_columns,
            'data' => $data
        );

        wp_send_json_success($response);
    }

    /**
     * Handle AJAX request for form submission
     *
     * @since 1.0.0
     */
    public function handle_form_submission() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'edm_public_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();

        if ($file_id <= 0) {
            wp_send_json_error('Invalid file ID');
        }

        if (empty($form_data) || !is_array($form_data)) {
            wp_send_json_error('Invalid form data');
        }

        global $wpdb;
        $uploads_table = $wpdb->prefix . 'edm_uploads';

        // Get file info
        $file_info = $wpdb->get_row($wpdb->prepare(
            "SELECT table_name, original_filename FROM $uploads_table WHERE id = %d",
            $file_id
        ));

        if (!$file_info) {
            wp_send_json_error('File not found');
        }

        $table_name = $wpdb->prefix . $file_info->table_name;

        // Insert data
        $result = $wpdb->insert($table_name, $form_data);

        if ($result === false) {
            wp_send_json_error('Failed to insert data');
        }

        wp_send_json_success('Data successfully added');
    }

    /**
     * Check if the current user can view data based on plugin settings
     *
     * @since 1.0.0
     * @return bool Whether the user can view data
     */
    private function user_can_view_data() {
        // Get plugin settings
        $settings = get_option('edm_settings', array());
        
        // If no settings exist, allow access by default
        if (empty($settings)) {
            return true;
        }
        
        // If user is not logged in, check if minimum role is subscriber (which means public access)
        if (!is_user_logged_in()) {
            return (isset($settings['min_role_view']) && $settings['min_role_view'] === 'subscriber');
        }
        
        // Get current user
        $current_user = wp_get_current_user();
        
        // Define role hierarchy
        $role_hierarchy = array(
            'subscriber' => 1,
            'contributor' => 2,
            'author' => 3,
            'editor' => 4,
            'administrator' => 5
        );
        
        // Get user's highest role level
        $user_level = 0;
        foreach ($current_user->roles as $role) {
            if (isset($role_hierarchy[$role]) && $role_hierarchy[$role] > $user_level) {
                $user_level = $role_hierarchy[$role];
            }
        }
        
        // Get minimum required role level
        $min_role = isset($settings['min_role_view']) ? $settings['min_role_view'] : 'subscriber';
        $min_level = isset($role_hierarchy[$min_role]) ? $role_hierarchy[$min_role] : 1;
        
        // Check if user meets minimum role requirement
        return $user_level >= $min_level;
    }
}