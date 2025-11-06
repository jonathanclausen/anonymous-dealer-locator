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
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
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
    <?php endif; ?>
</div>

<style>
.status-active { color: #46b450; font-weight: bold; }
.status-inactive { color: #dc3232; font-weight: bold; }
.adl-edit-form { background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; }
.adl-coord-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
.adl-badge-ok { background: #e7f7ec; color: #1f8f3e; border: 1px solid #bfe8cb; }
.adl-badge-missing { background: #fdecea; color: #9f2a1e; border: 1px solid #f5c6cb; font-weight: 600; }
</style>
