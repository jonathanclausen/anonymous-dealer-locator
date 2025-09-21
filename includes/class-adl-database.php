<?php
/**
 * Database klasse til Anonymous Dealer Locator
 */

if (!defined('ABSPATH')) {
    exit;
}

class ADL_Database {
    
    private static $dealers_table = 'adl_dealers';
    private static $inquiries_table = 'adl_inquiries';
    
    public function __construct() {
        // Database setup hooks
    }
    
    /**
     * Create necessary database tables
     */
    public static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create dealers table
        $dealers_table = $wpdb->prefix . self::$dealers_table;
        $sql_dealers = "CREATE TABLE $dealers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50),
            address text NOT NULL,
            latitude decimal(10, 8) NOT NULL,
            longitude decimal(11, 8) NOT NULL,
            city varchar(100),
            postal_code varchar(20),
            country varchar(100),
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY latitude (latitude),
            KEY longitude (longitude),
            KEY status (status)
        ) $charset_collate;";
        
        // Create inquiries table
        $inquiries_table = $wpdb->prefix . self::$inquiries_table;
        $sql_inquiries = "CREATE TABLE $inquiries_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            dealer_id mediumint(9) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50),
            customer_message text NOT NULL,
            email_sent enum('yes', 'no') DEFAULT 'no',
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY dealer_id (dealer_id),
            KEY customer_email (customer_email),
            KEY created_at (created_at),
            KEY email_sent (email_sent),
            FOREIGN KEY (dealer_id) REFERENCES $dealers_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_dealers);
        dbDelta($sql_inquiries);
    }
    
    /**
     * Tilføj ny forhandler
     */
    public static function addDealer($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        return $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'email' => sanitize_email($data['email']),
                'phone' => sanitize_text_field($data['phone']),
                'address' => sanitize_textarea_field($data['address']),
                'latitude' => floatval($data['latitude']),
                'longitude' => floatval($data['longitude']),
                'city' => sanitize_text_field($data['city']),
                'postal_code' => sanitize_text_field($data['postal_code']),
                'country' => sanitize_text_field($data['country']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Hent forhandler efter ID
     */
    public static function getDealer($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)
        );
    }
    
    /**
     * Hent alle aktive forhandlere
     */
    public static function getAllDealers($status = 'active') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE status = %s ORDER BY name", $status)
        );
    }
    
    /**
     * Get dealers within radius of coordinates (or all dealers if no radius)
     */
    public static function getDealersInRadius($latitude, $longitude, $radius = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        if ($radius === null || $radius <= 0) {
            // Return all dealers sorted by distance (closest first)
            $sql = $wpdb->prepare("
                SELECT *, 
                (6371 * acos(cos(radians(%f)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(latitude)))) AS distance
                FROM $table_name 
                WHERE status = 'active'
                ORDER BY distance ASC
            ", $latitude, $longitude, $latitude);
        } else {
            // Use Haversine formula to calculate distance with radius limit
            $sql = $wpdb->prepare("
                SELECT *, 
                (6371 * acos(cos(radians(%f)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(latitude)))) AS distance
                FROM $table_name 
                WHERE status = 'active'
                HAVING distance < %d 
                ORDER BY distance ASC
                LIMIT 50
            ", $latitude, $longitude, $latitude, $radius);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Opdater forhandler
     */
    public static function updateDealer($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        return $wpdb->update(
            $table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'email' => sanitize_email($data['email']),
                'phone' => sanitize_text_field($data['phone']),
                'address' => sanitize_textarea_field($data['address']),
                'latitude' => floatval($data['latitude']),
                'longitude' => floatval($data['longitude']),
                'city' => sanitize_text_field($data['city']),
                'postal_code' => sanitize_text_field($data['postal_code']),
                'country' => sanitize_text_field($data['country']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Slet forhandler
     */
    public static function deleteDealer($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Søg forhandlere efter postnummer eller by
     */
    public static function searchDealers($search_term) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$dealers_table;
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM $table_name 
                WHERE status = 'active' 
                AND (postal_code LIKE %s OR city LIKE %s OR address LIKE %s)
                ORDER BY name
                LIMIT 20
            ", $search_term, $search_term, $search_term)
        );
    }
    
    /**
     * Add new inquiry
     */
    public static function addInquiry($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$inquiries_table;
        
        return $wpdb->insert(
            $table_name,
            array(
                'dealer_id' => intval($data['dealer_id']),
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_email' => sanitize_email($data['customer_email']),
                'customer_phone' => sanitize_text_field($data['customer_phone']),
                'customer_message' => sanitize_textarea_field($data['customer_message']),
                'email_sent' => sanitize_text_field($data['email_sent']),
                'ip_address' => sanitize_text_field($data['ip_address']),
                'user_agent' => sanitize_text_field($data['user_agent'])
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get all inquiries with dealer information
     */
    public static function getAllInquiries($limit = 50, $offset = 0) {
        global $wpdb;
        
        $inquiries_table = $wpdb->prefix . self::$inquiries_table;
        $dealers_table = $wpdb->prefix . self::$dealers_table;
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT i.*, d.name as dealer_name, d.email as dealer_email, d.city as dealer_city 
                FROM $inquiries_table i 
                LEFT JOIN $dealers_table d ON i.dealer_id = d.id 
                ORDER BY i.created_at DESC 
                LIMIT %d OFFSET %d
            ", $limit, $offset)
        );
    }
    
    /**
     * Get inquiries count
     */
    public static function getInquiriesCount() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$inquiries_table;
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * Get inquiries for specific dealer
     */
    public static function getInquiriesForDealer($dealer_id, $limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$inquiries_table;
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM $table_name 
                WHERE dealer_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d
            ", $dealer_id, $limit)
        );
    }
    
    /**
     * Delete inquiry
     */
    public static function deleteInquiry($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$inquiries_table;
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }
}
