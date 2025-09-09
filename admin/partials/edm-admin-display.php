<?php
/**
 * Provides the admin area view for the plugin.
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 */
?>

<div class="wrap edm-wrap">
    
    <div class="edm-header">
        <h1><span class="dashicons dashicons-database-import"></span> Excel Data Manager</h1>
        <p>Manage your uploaded Excel files with ease. Upload new data or view existing records.</p>
    </div>

    <div class="edm-main-content">

        <!-- Upload Card -->
        <div class="edm-card" id="edm-upload-card">
            <h2><span class="dashicons dashicons-upload"></span> Upload New Data</h2>
            <div class="upload-area" id="edm-upload-area">
                <div class="upload-area-icon">
                    <span class="dashicons dashicons-cloud-upload"></span>
                </div>
                <p class="upload-area-text">Drag & Drop your .xls or .xlsx file here</p>
                <p class="upload-area-or">or</p>
                <input type="file" id="edm-file-input" accept=".xls,.xlsx" hidden>
                <button id="edm-upload-button" class="button edm-button-primary">Select a File</button>
            </div>
            <div id="edm-progress-bar-container" style="display: none;">
                <div id="edm-progress-bar"></div>
            </div>
            <p id="edm-feedback-message"></p>
        </div>

        <!-- File List Card -->
        <div class="edm-card" id="edm-files-card">
             <div class="edm-card-header">
                <h2><span class="dashicons dashicons-list-view"></span> Uploaded Files</h2>
                <div class="edm-search-container">
                    <input type="text" id="edm-search-input" placeholder="Search files...">
                    <span class="dashicons dashicons-search"></span>
                </div>
            </div>
            <div class="edm-file-list-container" id="edm-file-list-container">
                 <!-- The file list table will be loaded here by AJAX -->
                 <div id="edm-loader" class="edm-loader-container"><div class="edm-loader"></div></div>
            </div>
        </div>

    </div>

</div>

<!-- Confirmation Modal for Deletion -->
<div id="edm-delete-modal" class="edm-modal" style="display:none;">
    <div class="edm-modal-content">
        <span class="edm-modal-close">&times;</span>
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this file and all its associated data? This action cannot be undone.</p>
        <p><strong>Filename:</strong> <span id="edm-modal-filename"></span></p>
        <div class="edm-modal-actions">
            <button id="edm-modal-cancel" class="button">Cancel</button>
            <button id="edm-modal-confirm" class="button button-danger">Yes, Delete It</button>
        </div>
    </div>
</div>

<!-- Data View Modal -->
<div id="edm-view-modal" class="edm-modal" style="display:none;">
    <div class="edm-modal-content edm-view-modal-content">
        <span class="edm-modal-close">&times;</span>
        <div class="edm-view-header">
            <h3 id="edm-view-title">Viewing Data</h3>
             <div class="edm-search-container">
                <input type="text" id="edm-view-search-input" placeholder="Search in this table...">
                <span class="dashicons dashicons-search"></span>
            </div>
        </div>
        <div id="edm-view-data-container" class="edm-view-data-container">
            <!-- Data table will be loaded here -->
        </div>
        <div id="edm-view-pagination" class="edm-pagination-container">
            <!-- Pagination controls will be loaded here -->
        </div>
    </div>
</div>

