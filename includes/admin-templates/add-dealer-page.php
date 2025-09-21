<?php
/**
 * Admin template for adding new dealer
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Add New Dealer', 'anonymous-dealer-locator'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('adl_add_dealer', 'adl_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name"><?php _e('Name', 'anonymous-dealer-locator'); ?> *</label></th>
                <td><input name="name" type="text" id="name" value="" class="regular-text" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="email"><?php _e('Email', 'anonymous-dealer-locator'); ?> *</label></th>
                <td><input name="email" type="email" id="email" value="" class="regular-text" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="phone"><?php _e('Phone', 'anonymous-dealer-locator'); ?></label></th>
                <td><input name="phone" type="text" id="phone" value="" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="address"><?php _e('Address', 'anonymous-dealer-locator'); ?> *</label></th>
                <td>
                    <textarea name="address" id="address" rows="3" cols="50" class="large-text" required placeholder="<?php _e('Enter the full address...', 'anonymous-dealer-locator'); ?>"></textarea>
                    <br>
                    <button type="button" id="geocode-btn" class="button" style="margin-top: 5px;"><?php _e('Get Coordinates Automatically', 'anonymous-dealer-locator'); ?></button>
                    <span id="geocode-status" style="margin-left: 10px;"></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="city"><?php _e('City', 'anonymous-dealer-locator'); ?></label></th>
                <td><input name="city" type="text" id="city" value="" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="postal_code"><?php _e('Postal Code', 'anonymous-dealer-locator'); ?></label></th>
                <td><input name="postal_code" type="text" id="postal_code" value="" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="country"><?php _e('Country', 'anonymous-dealer-locator'); ?></label></th>
                <td><input name="country" type="text" id="country" value="" class="regular-text" placeholder="<?php _e('e.g. Denmark, Germany, USA...', 'anonymous-dealer-locator'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="latitude"><?php _e('Latitude', 'anonymous-dealer-locator'); ?> *</label></th>
                <td>
                    <input name="latitude" type="number" step="any" id="latitude" value="" class="regular-text" required />
                    <p class="description"><?php _e('Will be filled automatically when you click "Get Coordinates"', 'anonymous-dealer-locator'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="longitude"><?php _e('Longitude', 'anonymous-dealer-locator'); ?> *</label></th>
                <td>
                    <input name="longitude" type="number" step="any" id="longitude" value="" class="regular-text" required />
                    <p class="description"><?php _e('Will be filled automatically when you click "Get Coordinates"', 'anonymous-dealer-locator'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="status"><?php _e('Status', 'anonymous-dealer-locator'); ?></label></th>
                <td>
                    <select name="status" id="status">
                        <option value="active"><?php _e('Active', 'anonymous-dealer-locator'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'anonymous-dealer-locator'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Add Dealer', 'anonymous-dealer-locator'), 'primary', 'adl_add_dealer'); ?>
    </form>
    
    <div class="adl-help-box" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 20px;">
        <h3><?php _e('Help', 'anonymous-dealer-locator'); ?></h3>
        <ul>
            <li><?php _e('Fill out all required fields marked with *', 'anonymous-dealer-locator'); ?></li>
            <li><?php _e('Use the "Get Coordinates" button to automatically find GPS coordinates based on the address', 'anonymous-dealer-locator'); ?></li>
            <li><?php _e('The coordinates are used to display the dealer on the map and calculate distances', 'anonymous-dealer-locator'); ?></li>
            <li><?php _e('Only active dealers are shown on the public map', 'anonymous-dealer-locator'); ?></li>
        </ul>
    </div>
</div>
