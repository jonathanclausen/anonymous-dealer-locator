<?php
/**
 * Admin class for Anonymous Dealer Locator
 */

if (!defined('ABSPATH')) {
    exit;
}

class ADL_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'handleFormSubmissions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_ajax_adl_geocode_address', array($this, 'geocodeAddress'));
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Dealer Locator', 'anonymous-dealer-locator'),
            __('Dealer Locator', 'anonymous-dealer-locator'),
            'manage_options',
            'adl-dealers',
            array($this, 'dealersPage'),
            'dashicons-location-alt',
            30
        );
        
        add_submenu_page(
            'adl-dealers',
            __('All Dealers', 'anonymous-dealer-locator'),
            __('All Dealers', 'anonymous-dealer-locator'),
            'manage_options',
            'adl-dealers',
            array($this, 'dealersPage')
        );
        
        add_submenu_page(
            'adl-dealers',
            __('Add Dealer', 'anonymous-dealer-locator'),
            __('Add Dealer', 'anonymous-dealer-locator'),
            'manage_options',
            'adl-add-dealer',
            array($this, 'addDealerPage')
        );
        
        add_submenu_page(
            'adl-dealers',
            __('Customer Inquiries', 'anonymous-dealer-locator'),
            __('Customer Inquiries', 'anonymous-dealer-locator'),
            'manage_options',
            'adl-inquiries',
            array($this, 'inquiriesPage')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'adl-') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('adl-admin-js', ADL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ADL_VERSION, true);
            wp_localize_script('adl-admin-js', 'adl_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('adl_admin_nonce')
            ));
            
            // Add admin styles for inquiries page
            if ($hook === 'dealer-locator_page_adl-inquiries') {
                wp_enqueue_style('adl-admin-css', ADL_PLUGIN_URL . 'assets/css/admin.css', array(), ADL_VERSION);
            }
        }
    }
    
    /**
     * Handle form submissions
     */
    public function handleFormSubmissions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Add new dealer
        if (isset($_POST['adl_add_dealer']) && wp_verify_nonce($_POST['adl_nonce'], 'adl_add_dealer')) {
            $this->processAddDealer();
        }
        
        // Update dealer
        if (isset($_POST['adl_update_dealer']) && wp_verify_nonce($_POST['adl_nonce'], 'adl_update_dealer')) {
            $this->processUpdateDealer();
        }
        
        // Delete dealer
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'adl_delete_dealer')) {
            $this->processDeleteDealer();
        }
        
        // Delete inquiry
        if (isset($_GET['action']) && $_GET['action'] === 'delete_inquiry' && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'adl_delete_inquiry')) {
            $this->processDeleteInquiry();
        }
    }
    
    /**
     * Process add dealer form
     */
    private function processAddDealer() {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'country' => sanitize_text_field($_POST['country']),
            'latitude' => floatval($_POST['latitude']),
            'longitude' => floatval($_POST['longitude']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        if (ADL_Database::addDealer($data)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Dealer added successfully!', 'anonymous-dealer-locator') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error adding dealer.', 'anonymous-dealer-locator') . '</p></div>';
            });
        }
    }
    
    /**
     * Process update dealer form
     */
    private function processUpdateDealer() {
        $id = intval($_POST['dealer_id']);
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'country' => sanitize_text_field($_POST['country']),
            'latitude' => floatval($_POST['latitude']),
            'longitude' => floatval($_POST['longitude']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        if (ADL_Database::updateDealer($id, $data)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Dealer updated successfully!', 'anonymous-dealer-locator') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating dealer.', 'anonymous-dealer-locator') . '</p></div>';
            });
        }
    }
    
    /**
     * Process delete dealer
     */
    private function processDeleteDealer() {
        $id = intval($_GET['id']);
        
        if (ADL_Database::deleteDealer($id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Dealer deleted successfully!', 'anonymous-dealer-locator') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error deleting dealer.', 'anonymous-dealer-locator') . '</p></div>';
            });
        }
    }
    
    /**
     * Dealers overview page
     */
    public function dealersPage() {
        $dealers = ADL_Database::getAllDealers();
        
        // Handle edit mode
        $edit_dealer = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $edit_dealer = ADL_Database::getDealer(intval($_GET['id']));
        }
        
        include ADL_PLUGIN_PATH . 'includes/admin-templates/dealers-page.php';
    }
    
    /**
     * Add dealer page
     */
    public function addDealerPage() {
        include ADL_PLUGIN_PATH . 'includes/admin-templates/add-dealer-page.php';
    }
    
    /**
     * Inquiries page
     */
    public function inquiriesPage() {
        // Handle pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Get inquiries
        $inquiries = ADL_Database::getAllInquiries($per_page, $offset);
        $total_inquiries = ADL_Database::getInquiriesCount();
        $total_pages = ceil($total_inquiries / $per_page);
        
        include ADL_PLUGIN_PATH . 'includes/admin-templates/inquiries-page.php';
    }
    
    /**
     * Process delete inquiry
     */
    private function processDeleteInquiry() {
        $id = intval($_GET['id']);
        
        if (ADL_Database::deleteInquiry($id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Inquiry deleted successfully!', 'anonymous-dealer-locator') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error deleting inquiry.', 'anonymous-dealer-locator') . '</p></div>';
            });
        }
    }
    
    /**
     * AJAX geocode address
     */
    public function geocodeAddress() {
        check_ajax_referer('adl_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $address = sanitize_text_field($_POST['address']);
        
        // Brug Google Geocoding API eller OpenStreetMap Nominatim
        $coordinates = $this->getCoordinatesFromAddress($address);
        
        wp_send_json($coordinates);
    }
    
    /**
     * Get coordinates from address using Nominatim - Nu med international support
     */
    private function getCoordinatesFromAddress($address) {
        $address = urlencode($address);
        // Tilføj addressdetails for bedre information og fjern landebegrænsninger
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
            'country' => isset($data[0]['address']['country']) ? $data[0]['address']['country'] : '',
            'city' => isset($data[0]['address']['city']) ? $data[0]['address']['city'] : 
                     (isset($data[0]['address']['town']) ? $data[0]['address']['town'] : 
                     (isset($data[0]['address']['village']) ? $data[0]['address']['village'] : '')),
            'postcode' => isset($data[0]['address']['postcode']) ? $data[0]['address']['postcode'] : ''
        );
    }
}
