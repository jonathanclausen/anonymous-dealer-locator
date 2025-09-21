<?php
/**
 * AJAX class for Anonymous Dealer Locator
 */

if (!defined('ABSPATH')) {
    exit;
}

class ADL_Ajax {
    
    public function __construct() {
        // Public AJAX endpoints (både logged in og ikke-logged in brugere)
        add_action('wp_ajax_adl_get_dealers', array($this, 'getDealers'));
        add_action('wp_ajax_nopriv_adl_get_dealers', array($this, 'getDealers'));
        
        add_action('wp_ajax_adl_search_dealers', array($this, 'searchDealers'));
        add_action('wp_ajax_nopriv_adl_search_dealers', array($this, 'searchDealers'));
        
        add_action('wp_ajax_adl_send_contact_form', array($this, 'sendContactForm'));
        add_action('wp_ajax_nopriv_adl_send_contact_form', array($this, 'sendContactForm'));
        
        add_action('wp_ajax_adl_search_nearby_dealers', array($this, 'searchNearbyDealers'));
        add_action('wp_ajax_nopriv_adl_search_nearby_dealers', array($this, 'searchNearbyDealers'));
    }
    
    /**
     * Get all active dealers
     */
    public function getDealers() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'adl_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $dealers = ADL_Database::getAllDealers('active');
        
        // Filtrér sensitive data - kun send koordinater og ID
        $filtered_dealers = array();
        foreach ($dealers as $dealer) {
            $filtered_dealers[] = array(
                'id' => $dealer->id,
                'latitude' => $dealer->latitude,
                'longitude' => $dealer->longitude,
                'city' => $dealer->city, // Valgfrit at vise by
                'postal_code' => $dealer->postal_code // Valgfrit at vise postnummer
            );
        }
        
        wp_send_json_success($filtered_dealers);
    }
    
    /**
     * Søg dealers baseret på adresse/postnummer
     */
    public function searchDealers() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'adl_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $query = sanitize_text_field($_POST['query']);
        
        if (empty($query)) {
            wp_send_json_error('Empty search query');
            return;
        }
        
        // Forsøg først at geocode søge-adressen
        $search_coordinates = $this->geocodeAddress($query);
        
        $dealers = array();
        
        if ($search_coordinates && !isset($search_coordinates['error'])) {
            // Search by coordinates (more precise) - return all dealers sorted by distance
            $dealers = ADL_Database::getDealersInRadius(
                $search_coordinates['latitude'],
                $search_coordinates['longitude'],
                null // No radius limit - return all dealers sorted by distance
            );
        } else {
            // Fallback: søg i database efter postnummer/by
            $dealers = ADL_Database::searchDealers($query);
        }
        
        // Filtrér sensitive data
        $filtered_dealers = array();
        foreach ($dealers as $dealer) {
            $filtered_dealers[] = array(
                'id' => $dealer->id,
                'latitude' => $dealer->latitude,
                'longitude' => $dealer->longitude,
                'city' => $dealer->city,
                'postal_code' => $dealer->postal_code,
                'distance' => isset($dealer->distance) ? round($dealer->distance, 1) : null
            );
        }
        
        $response_data = array(
            'dealers' => $filtered_dealers,
            'search_coordinates' => $search_coordinates,
            'query' => $query
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Søg dealers i nærheden af brugerens koordinater (geolocation)
     */
    public function searchNearbyDealers() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'adl_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $radius = null; // No radius limit - always return all dealers sorted by distance
        
        // Valider koordinater
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            wp_send_json_error('Invalid coordinates');
            return;
        }
        
        // Søg dealers i nærheden
        $dealers = ADL_Database::getDealersInRadius($latitude, $longitude, $radius);
        
        // Filtrér sensitive data
        $filtered_dealers = array();
        foreach ($dealers as $dealer) {
            $filtered_dealers[] = array(
                'id' => $dealer->id,
                'latitude' => $dealer->latitude,
                'longitude' => $dealer->longitude,
                'city' => $dealer->city,
                'postal_code' => $dealer->postal_code,
                'distance' => isset($dealer->distance) ? round($dealer->distance, 1) : null
            );
        }
        
        $response_data = array(
            'dealers' => $filtered_dealers,
            'user_coordinates' => array(
                'latitude' => $latitude,
                'longitude' => $longitude
            ),
            'radius' => 'unlimited' // No radius limit
        );
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Send kontakt formular til forhandler
     */
    public function sendContactForm() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'adl_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Sanitize input
        $dealer_id = intval($_POST['dealer_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        $customer_message = sanitize_textarea_field($_POST['customer_message']);
        
        // Valider input
        if (empty($dealer_id) || empty($customer_name) || empty($customer_email) || empty($customer_message)) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        if (!is_email($customer_email)) {
            wp_send_json_error('Invalid email address');
            return;
        }
        
        // Hent forhandler info
        $dealer = ADL_Database::getDealer($dealer_id);
        
        if (!$dealer || $dealer->status !== 'active') {
            wp_send_json_error('Dealer not found or inactive');
            return;
        }
        
        // Anti-spam check (simple honeypot og rate limiting kunne tilføjes her)
        
        // Send email til forhandler
        $email_sent = $this->sendDealerEmail($dealer, array(
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_message' => $customer_message
        ));
        
        // Save inquiry to database
        $inquiry_data = array(
            'dealer_id' => $dealer_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_message' => $customer_message,
            'email_sent' => $email_sent ? 'yes' : 'no',
            'ip_address' => $this->getUserIP(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
        
        $inquiry_saved = ADL_Database::addInquiry($inquiry_data);
        
        if ($email_sent) {
            wp_send_json_success('Message sent successfully');
        } else {
            wp_send_json_error('Failed to send message');
        }
    }
    
    /**
     * Send email til forhandler
     */
    private function sendDealerEmail($dealer, $customer_data) {
        $subject = sprintf(
            __('New customer inquiry via %s', 'anonymous-dealer-locator'),
            get_bloginfo('name')
        );
        
        $message = $this->buildEmailMessage($dealer, $customer_data);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $customer_data['customer_name'] . ' <' . $customer_data['customer_email'] . '>',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($dealer->email, $subject, $message, $headers);
    }
    
    /**
     * Byg email besked
     */
    private function buildEmailMessage($dealer, $customer_data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('New Customer Inquiry', 'anonymous-dealer-locator'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
                .customer-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .message-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007cba; margin: 15px 0; }
                .footer { margin-top: 20px; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><?php _e('New Customer Inquiry', 'anonymous-dealer-locator'); ?></h2>
                    <p><?php _e('You have received a new inquiry via your dealer page on', 'anonymous-dealer-locator'); ?> <?php echo get_bloginfo('name'); ?>.</p>
                </div>
                
                <div class="content">
                    <h3><?php _e('Customer Information', 'anonymous-dealer-locator'); ?></h3>
                    <div class="customer-info">
                        <p><strong><?php _e('Name:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html($customer_data['customer_name']); ?></p>
                        <p><strong><?php _e('Email:', 'anonymous-dealer-locator'); ?></strong> <a href="mailto:<?php echo esc_attr($customer_data['customer_email']); ?>"><?php echo esc_html($customer_data['customer_email']); ?></a></p>
                        <?php if (!empty($customer_data['customer_phone'])): ?>
                            <p><strong><?php _e('Phone:', 'anonymous-dealer-locator'); ?></strong> <a href="tel:<?php echo esc_attr($customer_data['customer_phone']); ?>"><?php echo esc_html($customer_data['customer_phone']); ?></a></p>
                        <?php endif; ?>
                    </div>
                    
                    <h3><?php _e('Message', 'anonymous-dealer-locator'); ?></h3>
                    <div class="message-box">
                        <?php echo nl2br(esc_html($customer_data['customer_message'])); ?>
                    </div>
                    
                    <p><strong><?php _e('What should you do now?', 'anonymous-dealer-locator'); ?></strong></p>
                    <ul>
                        <li><?php _e('Reply to the customer directly at the provided email address', 'anonymous-dealer-locator'); ?></li>
                        <li><?php _e('Call the customer if a phone number is provided', 'anonymous-dealer-locator'); ?></li>
                        <li><?php _e('Respond to the inquiry as quickly as possible for the best customer service', 'anonymous-dealer-locator'); ?></li>
                    </ul>
                </div>
                
                <div class="footer">
                    <p><?php _e('This email was sent automatically from', 'anonymous-dealer-locator'); ?> <a href="<?php echo home_url(); ?>"><?php echo get_bloginfo('name'); ?></a></p>
                    <p><?php _e('Received:', 'anonymous-dealer-locator'); ?> <?php echo current_time('d/m/Y H:i'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Log kontakt (for statistik - valgfrit)
     */
    private function logContact($dealer_id, $customer_email) {
        // Kunne implementere logging til database for statistik
        // For nu logges det bare i WordPress log
        error_log("ADL Contact: Dealer ID {$dealer_id} contacted by {$customer_email}");
    }
    
    /**
     * Get user's IP address
     */
    private function getUserIP() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Geocode address (used for search) - Now supports international addresses
     */
    private function geocodeAddress($address) {
        $address = urlencode($address);
        // Fjern countrycodes parameter for at understøtte hele verden
        $url = "https://nominatim.openstreetmap.org/search?format=json&q={$address}&limit=1&addressdetails=1";
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'WordPress Anonymous Dealer Locator Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return array('error' => 'Failed to geocode address');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return array('error' => 'Address not found');
        }
        
        return array(
            'latitude' => floatval($data[0]['lat']),
            'longitude' => floatval($data[0]['lon']),
            'display_name' => $data[0]['display_name'],
            'country' => isset($data[0]['address']['country']) ? $data[0]['address']['country'] : ''
        );
    }
}
