<?php
/**
 * Review coordinates page for dealers with missing coordinates
 */

if (!defined('ABSPATH')) {
    exit;
}

$transient_key = isset($_GET['transient']) ? sanitize_text_field($_GET['transient']) : '';
?>

<div class="wrap">
    <h1><?php _e('Review & Add Coordinates', 'anonymous-dealer-locator'); ?></h1>
    <p><?php _e('The following dealers are missing valid coordinates. Please add coordinates for each dealer and save when done.', 'anonymous-dealer-locator'); ?></p>
    
    <form method="post" action="" id="coordinates-review-form">
        <?php wp_nonce_field('adl_save_coordinates', 'adl_nonce'); ?>
        <input type="hidden" name="transient_key" value="<?php echo esc_attr($transient_key); ?>">
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" style="width: 200px;"><?php _e('Dealer Name', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Address', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col" style="width: 150px;"><?php _e('Latitude', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col" style="width: 150px;"><?php _e('Longitude', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col" style="width: 200px;"><?php _e('Actions', 'anonymous-dealer-locator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dealers as $dealer): ?>
                    <tr>
                        <td><strong><?php echo esc_html($dealer['name']); ?></strong></td>
                        <td>
                            <?php 
                            $full_address = array_filter(array(
                                $dealer['address'],
                                $dealer['postal_code'],
                                $dealer['city'],
                                $dealer['country']
                            ));
                            echo esc_html(implode(', ', $full_address)); 
                            ?>
                        </td>
                        <td>
                            <input type="number" 
                                   step="any" 
                                   name="dealers[<?php echo intval($dealer['id']); ?>][latitude]" 
                                   value="<?php echo esc_attr($dealer['latitude']); ?>" 
                                   class="regular-text latitude-input" 
                                   data-dealer-id="<?php echo intval($dealer['id']); ?>"
                                   data-address="<?php echo esc_attr(implode(', ', $full_address)); ?>"
                                   required />
                        </td>
                        <td>
                            <input type="number" 
                                   step="any" 
                                   name="dealers[<?php echo intval($dealer['id']); ?>][longitude]" 
                                   value="<?php echo esc_attr($dealer['longitude']); ?>" 
                                   class="regular-text longitude-input" 
                                   data-dealer-id="<?php echo intval($dealer['id']); ?>"
                                   required />
                        </td>
                        <td>
                            <button type="button" 
                                    class="button button-small geocode-btn" 
                                    data-dealer-id="<?php echo intval($dealer['id']); ?>"
                                    data-address="<?php echo esc_attr(implode(', ', $full_address)); ?>">
                                <?php _e('Get Coordinates', 'anonymous-dealer-locator'); ?>
                            </button>
                            <span class="geocode-status" id="status-<?php echo intval($dealer['id']); ?>" style="margin-left: 5px;"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" name="adl_save_coordinates" class="button button-primary button-large" value="<?php _e('Save All Coordinates', 'anonymous-dealer-locator'); ?>" />
            <a href="<?php echo admin_url('admin.php?page=adl-dealers'); ?>" class="button button-large"><?php _e('Cancel', 'anonymous-dealer-locator'); ?></a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Geocode button functionality
    $('.geocode-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var dealerId = $btn.data('dealer-id');
        var address = $btn.data('address');
        var $status = $('#status-' + dealerId);
        var $row = $btn.closest('tr');
        var $latInput = $row.find('.latitude-input');
        var $lngInput = $row.find('.longitude-input');
        
        if (!address) {
            alert('<?php _e('No address available for geocoding.', 'anonymous-dealer-locator'); ?>');
            return;
        }
        
        // Show loading state
        $btn.prop('disabled', true).text('<?php _e('Getting coordinates...', 'anonymous-dealer-locator'); ?>');
        $status.html('<span style="color: #0073aa;"><?php _e('Geocoding...', 'anonymous-dealer-locator'); ?></span>');
        
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
                    $status.html('<span style="color: #dc3232;"><?php _e('Error:', 'anonymous-dealer-locator'); ?> ' + response.error + '</span>');
                } else {
                    $latInput.val(response.latitude);
                    $lngInput.val(response.longitude);
                    $status.html('<span style="color: #46b450;"><?php _e('Coordinates retrieved!', 'anonymous-dealer-locator'); ?></span>');
                    
                    // Hide status after 3 seconds
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 3000);
                }
            },
            error: function() {
                $status.html('<span style="color: #dc3232;"><?php _e('Error retrieving coordinates', 'anonymous-dealer-locator'); ?></span>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Get Coordinates', 'anonymous-dealer-locator'); ?>');
            }
        });
    });
    
    // Validate coordinates before submit
    $('#coordinates-review-form').on('submit', function(e) {
        var hasErrors = false;
        
        $('.latitude-input, .longitude-input').each(function() {
            var lat = parseFloat($(this).closest('tr').find('.latitude-input').val());
            var lng = parseFloat($(this).closest('tr').find('.longitude-input').val());
            
            if (isNaN(lat) || isNaN(lng)) {
                hasErrors = true;
                return false;
            }
            
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                hasErrors = true;
                return false;
            }
            
            if (lat == 0 && lng == 0) {
                hasErrors = true;
                return false;
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('<?php _e('Please check all coordinates. They must be valid numbers within valid ranges (Latitude: -90 to 90, Longitude: -180 to 180) and cannot both be 0.', 'anonymous-dealer-locator'); ?>');
            return false;
        }
    });
});
</script>

<style>
.geocode-status {
    font-size: 12px;
}
.latitude-input, .longitude-input {
    width: 100%;
}
</style>

