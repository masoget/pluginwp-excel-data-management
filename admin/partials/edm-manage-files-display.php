<?php
/**
 * Provides the admin area view for the "Kelola File" (Manage Files) settings page.
 *
 * @since      1.1.0
 */

// Get the saved options from the database
$options = get_option( 'edm_settings' );

// Set default values for each option if they don't exist yet
$allow_frontend_upload = isset( $options['allow_frontend_upload'] ) ? $options['allow_frontend_upload'] : 'on';
$table_style = isset( $options['table_style'] ) ? $options['table_style'] : 'dark';
$show_search_bar = isset( $options['show_search_bar'] ) ? $options['show_search_bar'] : 'on';
$min_role_view = isset( $options['min_role_view'] ) ? $options['min_role_view'] : 'subscriber';

?>

<div class="wrap edm-wrap">

    <div class="edm-header">
        <h1><span class="dashicons dashicons-admin-settings"></span> Kelola File</h1>
        <p>Configure the settings for the Excel Data Manager plugin.</p>
    </div>

    <?php settings_errors(); // Display settings update messages ?>

    <form method="post" action="options.php">
        <?php
            // This function handles the security nonces and other hidden fields
            settings_fields( 'edm_settings_group' );
        ?>
        <div class="edm-settings-content">
        
            <!-- General Settings Card -->
            <div class="edm-card">
                <h2><span class="dashicons dashicons-admin-generic"></span> General Settings</h2>
                
                <div class="edm-setting-row">
                    <div class="edm-setting-label">
                        <label for="allow-frontend-upload">Allow Frontend Upload</label>
                        <p class="description">Enable or disable the ability for users to upload files from the frontend using a shortcode.</p>
                    </div>
                    <div class="edm-setting-field">
                        <label class="edm-switch">
                            <input type="checkbox" id="allow-frontend-upload" name="edm_settings[allow_frontend_upload]" <?php checked( $allow_frontend_upload, 'on' ); ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Shortcode Appearance Card -->
            <div class="edm-card">
                <h2><span class="dashicons dashicons-desktop"></span> Shortcode Appearance</h2>
                
                <div class="edm-setting-row">
                    <div class="edm-setting-label">
                        <label for="table-style">Table Style</label>
                        <p class="description">Choose the visual theme for the data tables displayed by the shortcode.</p>
                    </div>
                    <div class="edm-setting-field">
                        <select id="table-style" name="edm_settings[table_style]">
                            <option value="dark" <?php selected( $table_style, 'dark' ); ?>>Dark Mode</option>
                            <option value="light" <?php selected( $table_style, 'light' ); ?>>Light Mode</option>
                            <option value="system" <?php selected( $table_style, 'system' ); ?>>System Default</option>
                        </select>
                    </div>
                </div>

                <div class="edm-setting-row">
                    <div class="edm-setting-label">
                        <label for="show-search-bar">Show Search Bar</label>
                        <p class="description">Display a search bar above the data table on the frontend.</p>
                    </div>
                    <div class="edm-setting-field">
                         <label class="edm-switch">
                            <input type="checkbox" id="show-search-bar" name="edm_settings[show_search_bar]" <?php checked( $show_search_bar, 'on' ); ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Permissions Card -->
            <div class="edm-card">
                <h2><span class="dashicons dashicons-admin-users"></span> Permissions</h2>
                
                <div class="edm-setting-row">
                    <div class="edm-setting-label">
                        <label for="minimum-role-view">Minimum Role to View</label>
                        <p class="description">Select the minimum user role required to view the data tables.</p>
                    </div>
                    <div class="edm-setting-field">
                        <select id="minimum-role-view" name="edm_settings[min_role_view]">
                            <option value="subscriber" <?php selected( $min_role_view, 'subscriber' ); ?>>Subscriber</option>
                            <option value="contributor" <?php selected( $min_role_view, 'contributor' ); ?>>Contributor</option>
                            <option value="author" <?php selected( $min_role_view, 'author' ); ?>>Author</option>
                            <option value="editor" <?php selected( $min_role_view, 'editor' ); ?>>Editor</option>
                            <option value="administrator" <?php selected( $min_role_view, 'administrator' ); ?>>Administrator</option>
                        </select>
                    </div>
                </div>

            </div>

            <p class="submit">
                <?php submit_button( 'Save Changes', 'edm-button-primary' ); ?>
            </p>

        </div>
    </form>
</div>

