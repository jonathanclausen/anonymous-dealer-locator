<?php
/**
 * Admin template for displaying customer inquiries
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Customer Inquiries', 'anonymous-dealer-locator'); ?></h1>
    
    <?php if (empty($inquiries)): ?>
        <div class="notice notice-info">
            <p><?php _e('No customer inquiries found yet.', 'anonymous-dealer-locator'); ?></p>
        </div>
    <?php else: ?>
        <div class="adl-inquiries-stats" style="background: #f9f9f9; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <strong><?php printf(__('Total inquiries: %d', 'anonymous-dealer-locator'), $total_inquiries); ?></strong>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php _e('Date', 'anonymous-dealer-locator'); ?></th>
                    <th style="width: 20%;"><?php _e('Customer', 'anonymous-dealer-locator'); ?></th>
                    <th style="width: 20%;"><?php _e('Dealer', 'anonymous-dealer-locator'); ?></th>
                    <th style="width: 30%;"><?php _e('Message', 'anonymous-dealer-locator'); ?></th>
                    <th style="width: 10%;"><?php _e('Email Sent', 'anonymous-dealer-locator'); ?></th>
                    <th style="width: 5%;"><?php _e('Actions', 'anonymous-dealer-locator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html(date('M j, Y', strtotime($inquiry->created_at))); ?></strong><br>
                            <small><?php echo esc_html(date('H:i', strtotime($inquiry->created_at))); ?></small>
                        </td>
                        <td>
                            <strong><?php echo esc_html($inquiry->customer_name); ?></strong><br>
                            <a href="mailto:<?php echo esc_attr($inquiry->customer_email); ?>"><?php echo esc_html($inquiry->customer_email); ?></a>
                            <?php if (!empty($inquiry->customer_phone)): ?>
                                <br><small><?php echo esc_html($inquiry->customer_phone); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($inquiry->ip_address) && $inquiry->ip_address !== 'unknown'): ?>
                                <br><small style="color: #666;">IP: <?php echo esc_html($inquiry->ip_address); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($inquiry->dealer_name): ?>
                                <strong><?php echo esc_html($inquiry->dealer_name); ?></strong><br>
                                <a href="mailto:<?php echo esc_attr($inquiry->dealer_email); ?>"><?php echo esc_html($inquiry->dealer_email); ?></a>
                                <?php if (!empty($inquiry->dealer_city)): ?>
                                    <br><small><?php echo esc_html($inquiry->dealer_city); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #dc3232;"><?php _e('Dealer not found', 'anonymous-dealer-locator'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="max-height: 100px; overflow-y: auto; padding: 5px; background: #f9f9f9; border-radius: 3px;">
                                <?php echo nl2br(esc_html($inquiry->customer_message)); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($inquiry->email_sent === 'yes'): ?>
                                <span style="color: #46b450;">✓ <?php _e('Yes', 'anonymous-dealer-locator'); ?></span>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ <?php _e('No', 'anonymous-dealer-locator'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin.php?page=adl-inquiries&action=delete_inquiry&id=' . $inquiry->id),
                                'adl_delete_inquiry'
                            ); ?>" 
                               class="button button-small"
                               onclick="return confirm('<?php _e('Are you sure you want to delete this inquiry?', 'anonymous-dealer-locator'); ?>')"
                               style="color: #dc3232;">
                                <?php _e('Delete', 'anonymous-dealer-locator'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(__('%d items', 'anonymous-dealer-locator'), $total_inquiries); ?>
                    </span>
                    
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous', 'anonymous-dealer-locator'),
                        'next_text' => __('Next &raquo;', 'anonymous-dealer-locator'),
                        'total' => $total_pages,
                        'current' => $page
                    ));
                    
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="adl-help-box" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 20px;">
        <h3><?php _e('About Customer Inquiries', 'anonymous-dealer-locator'); ?></h3>
        <ul>
            <li><?php _e('All customer inquiries submitted through the dealer locator are automatically saved here', 'anonymous-dealer-locator'); ?></li>
            <li><?php _e('The "Email Sent" column shows whether the email was successfully delivered to the dealer', 'anonymous-dealer-locator'); ?></li>
            <li><?php _e('You can delete old inquiries to keep the list manageable', 'anonymous-dealer-locator'); ?></li>
            <li><?php _e('Customer IP addresses are logged for spam prevention purposes', 'anonymous-dealer-locator'); ?></li>
        </ul>
    </div>
</div>

<style>
.adl-inquiries-stats {
    display: flex;
    align-items: center;
    gap: 20px;
}

.wp-list-table td {
    vertical-align: top;
}

.wp-list-table td small {
    color: #666;
}

.tablenav-pages {
    float: right;
}

.tablenav-pages .displaying-num {
    margin-right: 10px;
    color: #666;
}
</style>
