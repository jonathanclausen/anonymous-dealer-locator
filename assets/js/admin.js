jQuery(document).ready(function($) {
    
    // Geocode button functionality
    $('#geocode-btn').on('click', function(e) {
        e.preventDefault();
        
        var address = $('#address').val();
        if (!address) {
            alert('Please enter an address first.');
            return;
        }
        
        var $btn = $(this);
        var $status = $('#geocode-status');
        
        // Show loading state
        $btn.prop('disabled', true).text('Getting coordinates...');
        $status.html('<span style="color: #0073aa;">Geocoding...</span>');
        
        // Send AJAX request
        $.ajax({
            url: adl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'adl_geocode_address',
                address: address,
                nonce: adl_ajax.nonce
            },
            success: function(response) {
                if (response.error) {
                    $status.html('<span style="color: #dc3232;">Error: ' + response.error + '</span>');
                } else {
                    $('#latitude').val(response.latitude);
                    $('#longitude').val(response.longitude);
                    
                    // Auto-udfyld by, postnummer og land hvis ikke allerede udfyldt
                    if (response.city && !$('#city').val()) {
                        $('#city').val(response.city);
                    }
                    if (response.postcode && !$('#postal_code').val()) {
                        $('#postal_code').val(response.postcode);
                    }
                    if (response.country && !$('#country').val()) {
                        $('#country').val(response.country);
                    }
                    
                    var statusMsg = 'Coordinates retrieved successfully!';
                    if (response.country) {
                        statusMsg += ' (Country: ' + response.country + ')';
                    }
                    $status.html('<span style="color: #46b450;">' + statusMsg + '</span>');
                }
            },
            error: function() {
                $status.html('<span style="color: #dc3232;">Error retrieving coordinates</span>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Get Coordinates Automatically');
                
                // Hide status after 3 seconds
                setTimeout(function() {
                    $status.fadeOut();
                }, 3000);
            }
        });
    });
    
    // Auto-fill city and postal code based on address
    $('#address').on('blur', function() {
        var address = $(this).val();
        if (address) {
            // Try to parse Danish address format
            var parts = address.split(',');
            if (parts.length >= 2) {
                var lastPart = parts[parts.length - 1].trim();
                var match = lastPart.match(/(\d{4})\s+(.+)/);
                if (match) {
                    if (!$('#postal_code').val()) {
                        $('#postal_code').val(match[1]);
                    }
                    if (!$('#city').val()) {
                        $('#city').val(match[2]);
                    }
                }
            }
        }
    });
    
    // Validate coordinates
    function validateCoordinates() {
        var lat = parseFloat($('#latitude').val());
        var lng = parseFloat($('#longitude').val());
        
        if (isNaN(lat) || isNaN(lng)) {
            return false;
        }
        
        // Global coordinate bounds
        // Latitude: -90 to 90
        // Longitude: -180 to 180
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            alert('The coordinates are not valid. Latitude must be between -90 and 90, longitude between -180 and 180.');
            return false;
        }
        
        return true;
    }
    
    // Validate form before submit
    $('form').on('submit', function(e) {
        if (!validateCoordinates()) {
            e.preventDefault();
            alert('Please check the coordinates or use the "Get Coordinates" button.');
        }
    });
    
    // Real-time coordinate validation
    $('#latitude, #longitude').on('input', function() {
        var lat = parseFloat($('#latitude').val());
        var lng = parseFloat($('#longitude').val());
        
        if (!isNaN(lat) && !isNaN(lng)) {
            $(this).css('border-color', '#46b450');
        } else {
            $(this).css('border-color', '#dc3232');
        }
    });
});
