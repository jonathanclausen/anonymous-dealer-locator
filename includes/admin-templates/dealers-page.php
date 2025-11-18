<?php
/**
 * Admin template til at vise alle forhandlere
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Forhandlere', 'anonymous-dealer-locator'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=adl-add-dealer'); ?>" class="page-title-action"><?php _e('Tilføj Ny', 'anonymous-dealer-locator'); ?></a>
    <hr class="wp-header-end">

    <div class="notice notice-info" style="margin-top:10px;">
        <p>
            <?php 
            $missing_text = sprintf(
                /* translators: %d is number of dealers missing coordinates */
                __('Forhandlere uden koordinater: %d', 'anonymous-dealer-locator'),
                isset($missing_count) ? intval($missing_count) : 0
            );
            echo esc_html($missing_text);
            ?>
            <?php if (!empty($missing_count)): ?>
                <?php if (isset($_GET['adl_missing']) && $_GET['adl_missing'] === '1'): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=adl-dealers')); ?>" class="button button-secondary" style="margin-left:10px;"><?php _e('Vis alle', 'anonymous-dealer-locator'); ?></a>
                <?php else: ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=adl-dealers&adl_missing=1')); ?>" class="button button-secondary" style="margin-left:10px;"><?php _e('Vis kun uden koordinater', 'anonymous-dealer-locator'); ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>

    <?php if ($edit_dealer): ?>
        <div class="adl-edit-form">
            <h2><?php _e('Rediger Forhandler', 'anonymous-dealer-locator'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('adl_update_dealer', 'adl_nonce'); ?>
                <input type="hidden" name="dealer_id" value="<?php echo esc_attr($edit_dealer->id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="name"><?php _e('Navn', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="name" type="text" id="name" value="<?php echo esc_attr($edit_dealer->name); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email"><?php _e('Email', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="email" type="email" id="email" value="<?php echo esc_attr($edit_dealer->email); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone"><?php _e('Telefon', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="phone" type="text" id="phone" value="<?php echo esc_attr($edit_dealer->phone); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="address"><?php _e('Adresse', 'anonymous-dealer-locator'); ?></label></th>
                        <td>
                            <textarea name="address" id="address" rows="3" cols="50" class="large-text" required><?php echo esc_textarea($edit_dealer->address); ?></textarea>
                            <button type="button" id="geocode-btn" class="button"><?php _e('Hent Koordinater', 'anonymous-dealer-locator'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="city"><?php _e('By', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="city" type="text" id="city" value="<?php echo esc_attr($edit_dealer->city); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="postal_code"><?php _e('Postnummer', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="postal_code" type="text" id="postal_code" value="<?php echo esc_attr($edit_dealer->postal_code); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="country"><?php _e('Land', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="country" type="text" id="country" value="<?php echo esc_attr($edit_dealer->country); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="latitude"><?php _e('Breddegrad', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="latitude" type="number" step="any" id="latitude" value="<?php echo esc_attr($edit_dealer->latitude); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="longitude"><?php _e('Længdegrad', 'anonymous-dealer-locator'); ?></label></th>
                        <td><input name="longitude" type="number" step="any" id="longitude" value="<?php echo esc_attr($edit_dealer->longitude); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="status"><?php _e('Status', 'anonymous-dealer-locator'); ?></label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="active" <?php selected($edit_dealer->status, 'active'); ?>><?php _e('Aktiv', 'anonymous-dealer-locator'); ?></option>
                                <option value="inactive" <?php selected($edit_dealer->status, 'inactive'); ?>><?php _e('Inaktiv', 'anonymous-dealer-locator'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Opdater Forhandler', 'anonymous-dealer-locator'), 'primary', 'adl_update_dealer'); ?>
                <a href="<?php echo admin_url('admin.php?page=adl-dealers'); ?>" class="button"><?php _e('Annuller', 'anonymous-dealer-locator'); ?></a>
            </form>
        </div>
        <hr>
    <?php endif; ?>

    <?php if (empty($dealers)): ?>
        <div class="notice notice-info">
            <p><?php _e('Ingen forhandlere fundet. Tilføj din første forhandler for at komme i gang.', 'anonymous-dealer-locator'); ?></p>
        </div>
    <?php else: ?>
        <form method="post" action="" id="dealers-bulk-form">
            <?php wp_nonce_field('adl_bulk_action', 'adl_bulk_nonce'); ?>
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector" class="screen-reader-text"><?php _e('Select bulk action', 'anonymous-dealer-locator'); ?></label>
                    <select name="action" id="bulk-action-selector">
                        <option value="-1"><?php _e('Bulk Actions', 'anonymous-dealer-locator'); ?></option>
                        <option value="update_email"><?php _e('Update Email', 'anonymous-dealer-locator'); ?></option>
                        <option value="delete"><?php _e('Delete', 'anonymous-dealer-locator'); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'anonymous-dealer-locator'); ?>" />
                    <button type="button" id="delete-all-btn" class="button button-link-delete" style="margin-left: 10px;">
                        <?php _e('Delete All', 'anonymous-dealer-locator'); ?>
                    </button>
                </div>
            </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </td>
                    <th scope="col"><?php _e('Navn', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Email', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Telefon', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('By', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Postnummer', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Status', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Koordinater', 'anonymous-dealer-locator'); ?></th>
                    <th scope="col"><?php _e('Handlinger', 'anonymous-dealer-locator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dealers as $dealer): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="dealers[]" value="<?php echo intval($dealer->id); ?>" class="dealer-checkbox" />
                        </th>
                        <td><strong><?php echo esc_html($dealer->name); ?></strong></td>
                        <td><?php echo esc_html($dealer->email); ?></td>
                        <td><?php echo esc_html($dealer->phone); ?></td>
                        <td><?php echo esc_html($dealer->city); ?></td>
                        <td><?php echo esc_html($dealer->postal_code); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($dealer->status); ?>">
                                <?php echo $dealer->status === 'active' ? __('Aktiv', 'anonymous-dealer-locator') : __('Inaktiv', 'anonymous-dealer-locator'); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $lat = floatval($dealer->latitude);
                            $lng = floatval($dealer->longitude);
                            $missing = (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0 && $lng == 0));
                            if ($missing): ?>
                                <span class="adl-coord-badge adl-badge-missing"><?php _e('Mangler', 'anonymous-dealer-locator'); ?></span>
                                <a href="<?php echo admin_url('admin.php?page=adl-dealers&action=edit&id=' . $dealer->id); ?>" class="button-link" style="margin-left:8px;"><?php _e('Ret', 'anonymous-dealer-locator'); ?></a>
                            <?php else: ?>
                                <span class="adl-coord-badge adl-badge-ok"><?php echo esc_html(number_format($lat, 5)) . ', ' . esc_html(number_format($lng, 5)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=adl-dealers&action=edit&id=' . $dealer->id); ?>" class="button button-small"><?php _e('Rediger', 'anonymous-dealer-locator'); ?></a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=adl-dealers&action=delete&id=' . $dealer->id), 'adl_delete_dealer'); ?>" 
                               class="button button-small button-link-delete" 
                               onclick="return confirm('<?php _e('Er du sikker på at du vil slette denne forhandler?', 'anonymous-dealer-locator'); ?>')"><?php _e('Slet', 'anonymous-dealer-locator'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </form>
    <?php endif; ?>
</div>

<!-- Bulk Email Update Modal -->
<div id="adl-bulk-email-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 5px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <h2><?php _e('Bulk Update Email', 'anonymous-dealer-locator'); ?></h2>
        <p><?php _e('Enter the new email address to apply to all selected dealers:', 'anonymous-dealer-locator'); ?></p>
        <form id="adl-bulk-email-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="bulk_email"><?php _e('New Email', 'anonymous-dealer-locator'); ?> *</label></th>
                    <td>
                        <input type="email" id="bulk_email" name="bulk_email" class="regular-text" required />
                        <p class="description"><?php _e('This email will be applied to all selected dealers.', 'anonymous-dealer-locator'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Update Emails', 'anonymous-dealer-locator'); ?></button>
                <button type="button" class="button" id="cancel-bulk-email"><?php _e('Cancel', 'anonymous-dealer-locator'); ?></button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('.dealer-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Update select all when individual checkboxes change
    $('.dealer-checkbox').on('change', function() {
        var total = $('.dealer-checkbox').length;
        var checked = $('.dealer-checkbox:checked').length;
        $('#cb-select-all').prop('checked', total === checked);
    });
    
    // Delete All button
    $('#delete-all-btn').on('click', function(e) {
        e.preventDefault();
        var total = $('.dealer-checkbox').length;
        
        if (total === 0) {
            alert('<?php _e('No dealers to delete.', 'anonymous-dealer-locator'); ?>');
            return;
        }
        
        if (confirm('<?php _e('Are you sure you want to delete ALL', 'anonymous-dealer-locator'); ?> ' + total + ' <?php _e('dealers? This action cannot be undone!', 'anonymous-dealer-locator'); ?>')) {
            // Select all checkboxes
            $('.dealer-checkbox').prop('checked', true);
            $('#cb-select-all').prop('checked', true);
            // Set action to delete
            $('#bulk-action-selector').val('delete');
            // Submit form
            $('#dealers-bulk-form').submit();
        }
    });
    
    // Bulk form submission
    $('#dealers-bulk-form').on('submit', function(e) {
        var action = $('#bulk-action-selector').val();
        var checked = $('.dealer-checkbox:checked').length;
        
        if (action === '-1') {
            e.preventDefault();
            alert('<?php _e('Please select a bulk action.', 'anonymous-dealer-locator'); ?>');
            return false;
        }
        
        if (checked === 0) {
            e.preventDefault();
            alert('<?php _e('Please select at least one dealer.', 'anonymous-dealer-locator'); ?>');
            return false;
        }
        
        if (action === 'update_email') {
            e.preventDefault();
            // Show modal
            $('#adl-bulk-email-modal').fadeIn();
            return false;
        }
        
        if (action === 'delete') {
            var message = checked === 1 
                ? '<?php _e('Are you sure you want to delete this dealer?', 'anonymous-dealer-locator'); ?>'
                : '<?php _e('Are you sure you want to delete', 'anonymous-dealer-locator'); ?> ' + checked + ' <?php _e('dealers?', 'anonymous-dealer-locator'); ?>';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Bulk email update form
    $('#adl-bulk-email-form').on('submit', function(e) {
        e.preventDefault();
        
        var newEmail = $('#bulk_email').val();
        var selectedDealers = [];
        $('.dealer-checkbox:checked').each(function() {
            selectedDealers.push($(this).val());
        });
        
        if (selectedDealers.length === 0) {
            alert('<?php _e('Please select at least one dealer.', 'anonymous-dealer-locator'); ?>');
            return false;
        }
        
        if (!newEmail || !isValidEmail(newEmail)) {
            alert('<?php _e('Please enter a valid email address.', 'anonymous-dealer-locator'); ?>');
            return false;
        }
        
        // Submit via AJAX
        $.ajax({
            url: adl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'adl_bulk_update_email',
                dealers: selectedDealers,
                email: newEmail,
                nonce: adl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#adl-bulk-email-modal').fadeOut();
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Failed to update emails.', 'anonymous-dealer-locator'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred.', 'anonymous-dealer-locator'); ?>');
            }
        });
        
        return false;
    });
    
    // Close modal
    $('#cancel-bulk-email').on('click', function() {
        $('#adl-bulk-email-modal').fadeOut();
        $('#bulk_email').val('');
    });
    
    // Close modal when clicking outside
    $('#adl-bulk-email-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
            $('#bulk_email').val('');
        }
    });
    
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>

<style>
.status-active { color: #46b450; font-weight: bold; }
.status-inactive { color: #dc3232; font-weight: bold; }
.adl-edit-form { background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; }
.adl-coord-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
.adl-badge-ok { background: #e7f7ec; color: #1f8f3e; border: 1px solid #bfe8cb; }
.adl-badge-missing { background: #fdecea; color: #9f2a1e; border: 1px solid #f5c6cb; font-weight: 600; }
</style>
