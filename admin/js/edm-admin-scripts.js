(function($) {
    'use strict';

    $(document).ready(function() {
        
        // --- Element Variables ---
        const feedbackMessage = $('#edm-feedback-message');
        
        // Upload related
        const uploadArea = $('#edm-upload-area');
        const fileInput = $('#edm-file-input');
        const uploadButton = $('#edm-upload-button');
        const progressBarContainer = $('#edm-progress-bar-container');
        const progressBar = $('#edm-progress-bar');
        
        // File list related
        const fileListContainer = $('#edm-file-list-container');
        
        // Delete Modal related
        const deleteModal = $('#edm-delete-modal');
        const deleteModalClose = $('#edm-delete-modal .edm-modal-close');
        const deleteModalCancel = $('#edm-modal-cancel');
        const deleteModalConfirm = $('#edm-modal-confirm');
        let fileToDeleteId = null;
        let fileToDeleteName = null;
        let rowToDelete = null;

        // View Modal related
        const viewModal = $('#edm-view-modal');
        const viewModalClose = $('#edm-view-modal .edm-modal-close');
        const viewModalTitle = $('#edm-view-title');
        const viewModalDataContainer = $('#edm-view-data-container');
        const viewModalPagination = $('#edm-view-pagination');
        const viewModalSearchInput = $('#edm-view-search-input');
        let currentViewFileId = null;
        let viewSearchTimer = null;


        // --- Initial Load ---
        refreshFileList();

        // --- Upload Functionality ---

        // Trigger file input click when the button is clicked
        uploadButton.on('click', function() {
            fileInput.click();
        });
        
        // Handle drag and drop events
        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        uploadArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        });
        
        // Handle file selection via button
        fileInput.on('change', function() {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0]);
            }
        });

        /**
         * Handles the client-side validation and AJAX submission of the file.
         * @param {File} file The file to upload.
         */
        function handleFileUpload(file) {
            // Validate file type on the client side for better UX
            const allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!allowedTypes.includes(file.type)) {
                feedbackMessage.css('color', 'red').text('Error: Invalid file type. Please select an .xls or .xlsx file.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'edm_upload_file');
            formData.append('security', edm_ajax_object.nonce);
            formData.append('excel_file', file);

            $.ajax({
                url: edm_ajax_object.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    feedbackMessage.css('color', 'var(--edm-text-primary)').text('Uploading "' + file.name + '"...');
                    progressBarContainer.show();
                    progressBar.width('0%');
                },
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = evt.loaded / evt.total;
                            progressBar.width(Math.round(percentComplete * 100) + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    progressBar.width('100%');
                    if (response.success) {
                        feedbackMessage.css('color', 'green').text(response.data.message);
                        refreshFileList(); 
                    } else {
                        feedbackMessage.css('color', 'red').text('Error: ' + response.data);
                    }
                },
                error: function() {
                    feedbackMessage.css('color', 'red').text('An unknown error occurred during upload.');
                },
                complete: function() {
                   setTimeout(() => progressBarContainer.hide(), 2000);
                }
            });
        }

        // --- File List Functionality ---

        /**
         * Fetches the list of uploaded files from the server and renders the table.
         */
        function refreshFileList() {
            fileListContainer.html('<div id="edm-loader" class="edm-loader-container"><div class="edm-loader"></div></div>');
            const data = {
                'action': 'edm_get_file_list',
                'security': edm_ajax_object.get_list_nonce
            };
            
            $.post(edm_ajax_object.ajax_url, data, function(response) {
                fileListContainer.empty();
                if (response.success && Array.isArray(response.data)) {
                    if (response.data.length > 0) {
                        const table = $('<table class="wp-list-table widefat striped fixed"><thead><tr><th style="width:40%">Filename</th><th>Uploaded By</th><th>Upload Date</th><th style="width:120px">Actions</th></tr></thead><tbody></tbody></table>');
                        const tbody = table.find('tbody');
                        
                        $.each(response.data, function(index, file) {
                            const row = $('<tr>').attr('data-row-id', file.id);
                            row.append($('<td>').text(file.original_filename));
                            row.append($('<td>').text(file.display_name || 'N/A'));
                            row.append($('<td>').text(file.upload_date));
                            const actions = `<button class="button btn-view" data-id="${file.id}" title="View Data"><span class="dashicons dashicons-visibility"></span></button> <button class="button btn-delete" data-id="${file.id}" data-filename="${file.original_filename}" title="Delete File"><span class="dashicons dashicons-trash"></span></button>`;
                            row.append($('<td>').html(actions));
                            tbody.append(row);
                        });
                        
                        fileListContainer.append(table);
                    } else {
                        fileListContainer.html('<p>No files uploaded yet.</p>');
                    }
                } else {
                    fileListContainer.html('<p style="color:red;">Could not retrieve file list.</p>');
                }
            }).fail(function() {
                fileListContainer.html('<p style="color:red;">An error occurred while fetching the file list.</p>');
            });
        }

        // --- Delete Functionality ---

        // Open confirmation modal when delete is clicked
        fileListContainer.on('click', '.btn-delete', function() {
            fileToDeleteId = $(this).data('id');
            fileToDeleteName = $(this).data('filename');
            rowToDelete = $(this).closest('tr');
            $('#edm-modal-filename').text(fileToDeleteName);
            deleteModal.show();
        });
        
        function closeDeleteModal() {
            deleteModal.hide();
        }
        deleteModalClose.on('click', closeDeleteModal);
        deleteModalCancel.on('click', closeDeleteModal);
        
        // Handle the confirmation of file deletion
        deleteModalConfirm.on('click', function() {
            if (!fileToDeleteId) return;

            const data = {
                'action': 'edm_delete_file',
                'security': edm_ajax_object.delete_nonce,
                'file_id': fileToDeleteId
            };
            
            const originalButtonText = $(this).text();
            $(this).text('Deleting...').prop('disabled', true);

            $.post(edm_ajax_object.ajax_url, data, function(response) {
                if (response.success) {
                    rowToDelete.fadeOut(400, function() {
                        $(this).remove();
                         if (fileListContainer.find('tbody tr').length === 0) {
                            refreshFileList();
                        }
                    });
                    feedbackMessage.css('color', 'green').text(response.data);
                } else {
                    feedbackMessage.css('color', 'red').text('Error: ' + response.data);
                }
            }).fail(function() {
                feedbackMessage.css('color', 'red').text('An error occurred during deletion.');
            }).always(function() {
                closeDeleteModal();
                deleteModalConfirm.text(originalButtonText).prop('disabled', false);
                fileToDeleteId = null;
                fileToDeleteName = null;
                rowToDelete = null;
            });
        });

        // --- View Functionality ---
        
        // Open view modal when view is clicked
        fileListContainer.on('click', '.btn-view', function() {
            currentViewFileId = $(this).data('id');
            viewModalSearchInput.val(''); // Reset search
            fetchAndDisplayTableData(currentViewFileId, 1, '');
        });

        // Handle search input in view modal (with debounce to prevent rapid firing)
        viewModalSearchInput.on('keyup', function() {
            clearTimeout(viewSearchTimer);
            const searchTerm = $(this).val();
            viewSearchTimer = setTimeout(function() {
                fetchAndDisplayTableData(currentViewFileId, 1, searchTerm);
            }, 500); // Wait 500ms after user stops typing
        });

        // Handle pagination clicks within the view modal
        viewModalPagination.on('click', 'button.page-button', function() {
            if ($(this).is(':disabled')) return;
            const page = $(this).data('page');
            const searchTerm = viewModalSearchInput.val();
            fetchAndDisplayTableData(currentViewFileId, page, searchTerm);
        });

        /**
         * Fetches paginated/searched data for a specific file and displays it in the view modal.
         * @param {number} fileId The ID of the file to view.
         * @param {number} page The page number to fetch.
         * @param {string} searchTerm The term to search for.
         */
        function fetchAndDisplayTableData(fileId, page, searchTerm) {
            viewModalDataContainer.html('<div id="edm-loader" class="edm-loader-container"><div class="edm-loader"></div></div>');
            viewModal.show();
            
            const data = {
                action: 'edm_get_table_data',
                security: edm_ajax_object.view_nonce,
                file_id: fileId,
                page: page,
                search: searchTerm
            };

            $.post(edm_ajax_object.ajax_url, data, function(response) {
                if(response.success) {
                    const res = response.data;
                    viewModalTitle.text('Viewing Data: ' + res.filename);
                    
                    // Build Table
                    if (res.data && res.data.length > 0) {
                        const table = $('<table class="wp-list-table widefat striped fixed"></table>');
                        const thead = $('<thead><tr></tr></thead>');
                        $.each(res.headers, function(i, header) {
                            thead.find('tr').append($('<th>').text(header.replace(/_/g, ' ')));
                        });
                        
                        const tbody = $('<tbody></tbody>');
                        $.each(res.data, function(i, row) {
                            const tr = $('<tr>');
                            $.each(res.headers, function(j, header) {
                                tr.append($('<td>').text(row[header]));
                            });
                            tbody.append(tr);
                        });

                        table.append(thead).append(tbody);
                        viewModalDataContainer.html(table);
                    } else {
                         viewModalDataContainer.html('<p>No matching data found.</p>');
                    }
                    
                    // Build Pagination
                    buildPagination(res.pagination);

                } else {
                    viewModalDataContainer.html('<p style="color:red">Error: ' + response.data + '</p>');
                }
            }).fail(function() {
                viewModalDataContainer.html('<p style="color:red">A network error occurred. Please try again.</p>');
            });
        }
        
        /**
         * Renders the pagination controls in the view modal.
         * @param {object} p Pagination data from the server.
         */
        function buildPagination(p) {
            viewModalPagination.empty();
            if (p.total_pages <= 1) return;

            const info = `<span class="pagination-info">Page ${p.current_page} of ${p.total_pages} (${p.total_items} items)</span>`;
            
            const controls = $('<div class="edm-pagination-controls"></div>');
            const prevButton = $(`<button class="button page-button" data-page="${p.current_page - 1}">&laquo; Prev</button>`);
            const nextButton = $(`<button class="button page-button" data-page="${p.current_page + 1}">Next &raquo;</button>`);

            if (p.current_page <= 1) prevButton.prop('disabled', true);
            if (p.current_page >= p.total_pages) nextButton.prop('disabled', true);

            controls.append(prevButton).append(nextButton);
            viewModalPagination.append(info).append(controls);
        }

        // --- General Modal Closing Logic ---
        function closeViewModal() {
            viewModal.hide();
            currentViewFileId = null; // Clear state
        }
        viewModalClose.on('click', closeViewModal);
        
        $(window).on('click', function(event) {
            if ($(event.target).is(deleteModal)) {
                closeDeleteModal();
            }
            if ($(event.target).is(viewModal)) {
                closeViewModal();
            }
        });

    });

})(jQuery);

