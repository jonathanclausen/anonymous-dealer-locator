<?php
/**
 * Plugin Name: Anonymous Dealer Locator
 * Plugin URI: https://yourwebsite.com
 * Description: A WordPress plugin that displays dealers on a map without revealing their names, with contact form functionality.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: anonymous-dealer-locator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ADL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ADL_VERSION', '1.0.0');

// Include required files
require_once ADL_PLUGIN_PATH . 'includes/class-adl-database.php';
require_once ADL_PLUGIN_PATH . 'includes/class-adl-admin.php';
require_once ADL_PLUGIN_PATH . 'includes/class-adl-frontend.php';
require_once ADL_PLUGIN_PATH . 'includes/class-adl-ajax.php';

/**
 * Main class for Anonymous Dealer Locator plugin
 */
class AnonymousDealerLocator {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize database
        new ADL_Database();
        
        // Initialize admin
        if (is_admin()) {
            new ADL_Admin();
        }
        
        // Initialize frontend
        new ADL_Frontend();
        
        // Initialize AJAX
        new ADL_Ajax();
        
        // Load text domain for translations
        load_plugin_textdomain('anonymous-dealer-locator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function activate() {
        // Create database tables
        ADL_Database::createTables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
AnonymousDealerLocator::getInstance();
