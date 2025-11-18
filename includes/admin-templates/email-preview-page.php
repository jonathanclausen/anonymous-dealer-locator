<?php
/**
 * Email preview page for development
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the AJAX class to access email building methods
require_once ADL_PLUGIN_PATH . 'includes/class-adl-ajax.php';
$ajax_instance = new ADL_Ajax();

// Use reflection to access private methods
$reflection = new ReflectionClass($ajax_instance);

// Get the email building methods
$customer_method = $reflection->getMethod('buildCustomerConfirmationMessage');
$customer_method->setAccessible(true);

$dealer_method = $reflection->getMethod('buildEmailMessage');
$dealer_method->setAccessible(true);

// Build email previews
$customer_email_html = $customer_method->invoke($ajax_instance, $sample_customer_data, $sample_dealer);
$dealer_email_html = $dealer_method->invoke($ajax_instance, $sample_dealer, $sample_customer_data);
?>

<div class="wrap">
    <h1><?php _e('Email Template Preview', 'anonymous-dealer-locator'); ?></h1>
    <p><?php _e('Preview how emails will look to customers and dealers. No emails are actually sent from this page.', 'anonymous-dealer-locator'); ?></p>
    
    <div style="background: #f0f0f1; padding: 15px; margin: 20px 0; border-left: 4px solid #2271b1;">
        <strong><?php _e('Note:', 'anonymous-dealer-locator'); ?></strong> 
        <?php _e('This is a development preview using sample data. The actual emails will use real customer and dealer information.', 'anonymous-dealer-locator'); ?>
    </div>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=adl-email-preview&type=customer" class="nav-tab <?php echo $preview_type === 'customer' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Customer Confirmation Email', 'anonymous-dealer-locator'); ?>
        </a>
        <a href="?page=adl-email-preview&type=dealer" class="nav-tab <?php echo $preview_type === 'dealer' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Dealer Notification Email', 'anonymous-dealer-locator'); ?>
        </a>
    </h2>
    
    <div style="margin-top: 20px;">
        <?php if ($preview_type === 'customer'): ?>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccc;">
                <h3><?php _e('Customer Confirmation Email Preview', 'anonymous-dealer-locator'); ?></h3>
                <p><strong><?php _e('To:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($sample_customer_data['customer_email']); ?></p>
                <p><strong><?php _e('Subject:', 'anonymous-dealer-locator'); ?></strong> <?php echo sprintf(esc_html__('Thank you for contacting AM-Robots, we will get back to you as soon as possible - %s', 'anonymous-dealer-locator'), get_bloginfo('name')); ?></p>
                <hr>
                <div style="border: 2px solid #ddd; padding: 20px; background: #f9f9f9;">
                    <?php echo $customer_email_html; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccc;">
                <h3><?php _e('Dealer Notification Email Preview', 'anonymous-dealer-locator'); ?></h3>
                <p><strong><?php _e('To:', 'anonymous-dealer-locator'); ?></strong> storm@am-robots.com</p>
                <p><strong><?php _e('Subject:', 'anonymous-dealer-locator'); ?></strong> <?php echo sprintf(esc_html__('End user inquiry via STORM Robot – %s', 'anonymous-dealer-locator'), $sample_dealer->name); ?></p>
                <hr>
                <div style="border: 2px solid #ddd; padding: 20px; background: #f9f9f9;">
                    <?php echo $dealer_email_html; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <h3><?php _e('Sample Data Used:', 'anonymous-dealer-locator'); ?></h3>
        <ul>
            <li><strong><?php _e('Customer Name:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($sample_customer_data['customer_name']); ?></li>
            <li><strong><?php _e('Customer Email:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($sample_customer_data['customer_email']); ?></li>
            <li><strong><?php _e('Customer Phone:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($sample_customer_data['customer_phone']); ?></li>
            <li><strong><?php _e('Dealer City:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($sample_dealer->city); ?></li>
            <li><strong><?php _e('Message:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($sample_customer_data['customer_message']); ?></li>
        </ul>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 0 !important;
}
</style>

