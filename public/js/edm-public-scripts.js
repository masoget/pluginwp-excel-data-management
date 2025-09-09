(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle search functionality
        $('.edm-search-container').each(function() {
            const container = $(this);
            const searchInput = container.find('.edm-search-input');
            const searchBtn = container.find('.edm-search-btn');
            const fileID = searchInput.data('file-id');
            const resultsContainer = container.find('.edm-search-table-container');
            const loader = container.find('.edm-loader');
            
            // Perform search on button click
            searchBtn.on('click', function(e) {
                e.preventDefault();
                performSearch();
            });
            
            // Perform search on Enter key
            searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    performSearch();
                }
            });
            
            // Initial load of all data
            performSearch();
            
            function performSearch() {
                const searchTerm = searchInput.val();
                
                loader.show();
                resultsContainer.hide();
                
                $.ajax({
                    url: edm_public_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'edm_search_data',
                        file_id: fileID,
                        search: searchTerm,
                        nonce: edm_public_ajax.nonce
                    },
                    success: function(response) {
                        loader.hide();
                        
                        if (response.success) {
                            const data = response.data;
                            displaySearchResults(data, resultsContainer);
                            resultsContainer.show();
                        } else {
                            resultsContainer.html('<p>Error: ' + response.data + '</p>');
                            resultsContainer.show();
                        }
                    },
                    error: function() {
                        loader.hide();
                        resultsContainer.html('<p>An error occurred while searching.</p>');
                        resultsContainer.show();
                    }
                });
            }
        });
        
        // Display search results in a table
        function displaySearchResults(data, container) {
            if (!data.headers || !data.data) {
                container.html('<p>No data structure received.</p>');
                return;
            }
            
            if (data.data.length === 0) {
                container.html('<p>No matching data found.</p>');
                return;
            }
            
            let tableHTML = '<table class="edm-public-table"><thead><tr>';
            
            // Create headers
            $.each(data.headers, function(index, header) {
                tableHTML += '<th>' + header.replace(/_/g, ' ') + '</th>';
            });
            
            tableHTML += '</tr></thead><tbody>';
            
            // Create rows
            $.each(data.data, function(rowIndex, row) {
                tableHTML += '<tr>';
                $.each(data.headers, function(colIndex, header) {
                    tableHTML += '<td>' + (row[header] || '') + '</td>';
                });
                tableHTML += '</tr>';
            });
            
            tableHTML += '</tbody></table>';
            
            container.html(tableHTML);
        }
        
        // Handle form submissions
        $('.edm-data-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const fileID = form.data('file-id');
            const messageDiv = form.find('.edm-form-message');
            const submitBtn = form.find('.edm-form-submit');
            
            // Collect form data
            const formData = {};
            form.find('.edm-form-input').each(function() {
                const input = $(this);
                formData[input.attr('name')] = input.val();
            });
            
            // Disable submit button during submission
            submitBtn.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: edm_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edm_submit_form',
                    file_id: fileID,
                    form_data: formData,
                    nonce: edm_public_ajax.nonce
                },
                success: function(response) {
                    submitBtn.prop('disabled', false).text('Submit');
                    
                    if (response.success) {
                        messageDiv.removeClass('error').addClass('success').text(response.data);
                        messageDiv.show();
                        
                        // Reset form
                        form[0].reset();
                        
                        // Hide message after 5 seconds
                        setTimeout(function() {
                            messageDiv.fadeOut();
                        }, 5000);
                    } else {
                        messageDiv.removeClass('success').addClass('error').text('Error: ' + response.data);
                        messageDiv.show();
                    }
                },
                error: function() {
                    submitBtn.prop('disabled', false).text('Submit');
                    messageDiv.removeClass('success').addClass('error').text('An error occurred during submission.');
                    messageDiv.show();
                }
            });
        });
    });
    
})(jQuery);