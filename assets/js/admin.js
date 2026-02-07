    // Live search/filter for unused widgets (robust)
    $('#eeo-unused-widget-search').on('input', function() {
        var q = $(this).val().toLowerCase();
        $('.unused-widgets-by-source .eeo-widget-label').each(function() {
            var label = $(this);
            var name = label.data('widget-name') || '';
            var id = label.data('widget-id') || '';
            if (q === '' || name.indexOf(q) !== -1 || id.indexOf(q) !== -1) {
                label.show();
            } else {
                label.hide();
            }
        });
        // Hide group if all children hidden
        $('.widget-source-group').each(function() {
            var group = $(this);
            var visible = group.find('.eeo-widget-label:visible').length;
            if (visible === 0) {
                group.hide();
            } else {
                group.show();
            }
        });
    });
    // Full Reset (settings + analytics)
    $('#eeo-full-reset').on('click', function(e) {
        e.preventDefault();
        if (!confirm('This will reset ALL plugin settings and analytics. Are you sure?')) return;
        var button = $(this);
        button.prop('disabled', true).text('Resetting...');
        $.ajax({
            url: eeo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'eeo_full_reset',
                nonce: eeo_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred during full reset: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).text('Full Reset (All Settings & Analytics)');
            }
        });
    });
/**
 * Elementor Editor Optimizer - Admin JavaScript
 * Enhanced with Widget Usage Analytics for Backend Performance Optimization
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Elementor Editor Optimizer: Admin scripts loading...');
    
    // Check if eeo_data exists
    if (typeof eeo_data === 'undefined') {
        console.error('eeo_data is not defined. Admin scripts may not work properly.');
        return;
    }
    
    console.log('eeo_data loaded:', eeo_data);
    
    // Debug: Check if elements exist
    console.log('Scan button found:', $('#scan-widget-usage').length);
    console.log('Select all button found:', $('#select-all-unused').length);
    console.log('Select none button found:', $('#select-none-unused').length);
    console.log('Disable selected button found:', $('#disable-selected-unused').length);
    console.log('Unused widget checkboxes found:', $('.unused-widget-checkbox').length);
    
    // Scan widget usage
    $('#scan-widget-usage').on('click', function(e) {
        e.preventDefault();
        console.log('Scan button clicked');
        
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Scanning...');
        
        $.ajax({
            url: eeo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'eeo_perform_widget_scan',
                nonce: eeo_data.nonce
            },
            success: function(response) {
                console.log('Scan response:', response);
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Scan error:', error);
                alert('An error occurred during the scan: ' + error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Auto-disable unused widgets
    $('#auto-disable-unused').on('click', function(e) {
        e.preventDefault();
        console.log('Auto-disable button clicked');
        
        if (!confirm('This will automatically disable all unused widgets. Continue?')) {
            return;
        }
        
        $('.unused-widget-checkbox').prop('checked', true);
        $('#disable-selected-unused').trigger('click');
    });
    
    // Reset usage data
    $('#reset-usage-data').on('click', function(e) {
        e.preventDefault();
        console.log('Reset button clicked');
        
        if (!confirm('This will reset all widget usage data. Continue?')) {
            return;
        }
        
        $.ajax({
            url: eeo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'eeo_reset_widget_usage_data',
                nonce: eeo_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('An error occurred.');
            }
        });
    });
    
    // Select all unused widgets
    $('#select-all-unused').on('click', function(e) {
        e.preventDefault();
        console.log('Select all clicked');
        var checkboxes = $('.unused-widget-checkbox');
        console.log('Found checkboxes:', checkboxes.length);
        checkboxes.prop('checked', true);
        updateSelectedCount();
    });

    // Select none unused widgets
    $('#select-none-unused').on('click', function(e) {
        e.preventDefault();
        console.log('Select none clicked');
        $('.unused-widget-checkbox').prop('checked', false);
        updateSelectedCount();
    });

    // Reset unused selection (uncheck all unused-widget-checkboxes)
    $('#reset-unused-selection').on('click', function(e) {
        e.preventDefault();
        console.log('Reset selection clicked');
        $('.unused-widget-checkbox').prop('checked', false);
        updateSelectedCount();
    });
    
    // Update selected count function
    function updateSelectedCount() {
        var count = $('.unused-widget-checkbox:checked').length;
        var total = $('.unused-widget-checkbox').length;
        var button = $('#disable-selected-unused');
        
        if (count > 0) {
            button.text('Disable Selected Widgets (' + count + ')').removeClass('button-secondary').addClass('button-primary');
        } else {
            button.text('Disable Selected Widgets').removeClass('button-primary').addClass('button-secondary');
        }
        
        console.log('Selected count updated:', count, 'of', total);
    }
    
    // Bind change event to checkboxes
    $(document).on('change', '.unused-widget-checkbox', function() {
        updateSelectedCount();
    });
    
    // Disable selected unused widgets
    $('#disable-selected-unused').on('click', function(e) {
        e.preventDefault();
        console.log('Disable selected clicked');
        
        var selectedWidgets = $('.unused-widget-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        console.log('Selected widgets:', selectedWidgets);
        
        if (selectedWidgets.length === 0) {
            alert('Please select widgets to disable.');
            return;
        }
        
        // Check the corresponding checkboxes in the main form
        var checkedCount = 0;
        selectedWidgets.forEach(function(widgetId) {
            var mainCheckbox = $('input[name="elementor_editor_optimizer_settings[disable_widgets][]"][value="' + widgetId + '"]');
            if (mainCheckbox.length > 0) {
                mainCheckbox.prop('checked', true);
                checkedCount++;
            }
        });
        
        alert('Selected ' + checkedCount + ' widgets for disabling. Don\'t forget to save your settings!');
        
        // Scroll to save button with highlighting
        var saveButton = $('#submit');
        if (saveButton.length > 0) {
            $('html, body').animate({
                scrollTop: saveButton.offset().top - 100
            }, 500);
            
            // Add highlighting effect
            saveButton.css({
                'background-color': '#ffeb3b',
                'border-color': '#ffc107',
                'box-shadow': '0 0 10px rgba(255, 193, 7, 0.5)'
            });
            
            // Remove highlighting after 3 seconds
            setTimeout(function() {
                saveButton.css({
                    'background-color': '',
                    'border-color': '',
                    'box-shadow': ''
                });
            }, 3000);
        }
    });
    
    // Initialize count on page load
    updateSelectedCount();
    
    // Add loading states for form submission
    $('form').on('submit', function() {
        $(this).find('input[type="submit"]').prop('disabled', true).val('Saving...');
    });
    
    console.log('Elementor Editor Optimizer: Admin scripts loaded successfully');
});
