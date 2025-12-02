<?php
/**
 * Frontend class for Anonymous Dealer Locator
 */

if (!defined('ABSPATH')) {
    exit;
}

class ADL_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_shortcode('dealer_locator', array($this, 'dealerLocatorShortcode'));
        add_action('wp_head', array($this, 'addMapboxCSS'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueueFrontendScripts() {
        // Only load on pages with shortcode
        if ($this->hasShortcode()) {
            // Mapbox GL JS (gratis alternativ til Google Maps)
            wp_enqueue_script('mapbox-gl-js', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js', array(), '2.15.0', true);
            wp_enqueue_style('mapbox-gl-css', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css', array(), '2.15.0');
            
            // Font Awesome for icons
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
            
            // Plugin scripts
            wp_enqueue_script('adl-frontend-js', ADL_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'mapbox-gl-js'), ADL_VERSION, true);
            wp_enqueue_style('adl-frontend-css', ADL_PLUGIN_URL . 'assets/css/frontend.css', array(), ADL_VERSION);
            
            // Localize script
            wp_localize_script('adl-frontend-js', 'adl_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('adl_frontend_nonce'),
                'strings' => array(
                    'search_placeholder' => __('Enter your address, city or country...', 'anonymous-dealer-locator'),
                    'search_button' => __('Find Dealers', 'anonymous-dealer-locator'),
                    'no_results' => __('No dealers found in your area. Try expanding your search radius.', 'anonymous-dealer-locator'),
                    'contact_dealer' => __('Contact this dealer', 'anonymous-dealer-locator'),
                    'loading' => __('Searching...', 'anonymous-dealer-locator'),
                    'error' => __('Something went wrong. Please try again.', 'anonymous-dealer-locator'),
                    'form_required' => __('This field is required', 'anonymous-dealer-locator'),
                    'form_email_invalid' => __('Please enter a valid email address', 'anonymous-dealer-locator'),
                    'form_success' => __('Your message has been sent successfully! The dealer will contact you soon.', 'anonymous-dealer-locator'),
                    'form_error' => __('Unable to send your message. Please try again.', 'anonymous-dealer-locator'),
                    'submit_button' => __('Send Message', 'anonymous-dealer-locator')
                )
            ));
        }
    }
    
    /**
     * Add Mapbox CSS to head (backup if external loading fails)
     */
    public function addMapboxCSS() {
        if ($this->hasShortcode()) {
            echo '<style>
                .mapboxgl-popup { z-index: 999999; }
                .mapboxgl-popup-content { border-radius: 8px; }
            </style>';
        }
    }
    
    /**
     * Check if current page has dealer locator shortcode
     */
    private function hasShortcode() {
        global $post;
        return is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dealer_locator');
    }
    
    /**
     * Dealer locator shortcode
     */
    public function dealerLocatorShortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '500px',
            'zoom' => '8',
            'center_lat' => '55.6761', // Copenhagen
            'center_lng' => '12.5683',
            'search_radius' => '0', // unlimited - always return closest dealers
            'show_search' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div id="adl-dealer-locator" class="adl-container">
            <?php if ($atts['show_search'] === 'true'): ?>
                <div class="adl-search-container">
                    <div class="adl-search-box">
                        <div class="adl-search-input-wrapper">
                            <input type="text" id="adl-search-input" placeholder="<?php _e('Enter your address, city or country...', 'anonymous-dealer-locator'); ?>" />
                            <button id="adl-search-btn" type="button" class="adl-search-icon-btn" aria-label="<?php _e('Search', 'anonymous-dealer-locator'); ?>">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div id="adl-search-results" class="adl-search-results"></div>
                </div>
            <?php endif; ?>
            
            <div class="adl-map-container">
                <div id="adl-map" class="adl-map" style="height: <?php echo esc_attr($atts['height']); ?>;">
                    <div class="adl-map-loading">
                        <p><?php _e('Loading map...', 'anonymous-dealer-locator'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Contact modal -->
            <div id="adl-contact-modal" class="adl-modal" style="display: none;">
                <div class="adl-modal-content">
                    <div class="adl-modal-header">
                        <h3><?php _e('Contact Dealer', 'anonymous-dealer-locator'); ?></h3>
                        <span class="adl-modal-close">&times;</span>
                    </div>
                    <div class="adl-modal-body">
                        <form id="adl-contact-form">
                            <input type="hidden" id="dealer_id" name="dealer_id" value="">
                            
                            <div class="adl-form-group">
                                <label for="customer_name"><?php _e('Your name', 'anonymous-dealer-locator'); ?> *</label>
                                <input type="text" id="customer_name" name="customer_name" required>
                            </div>
                            
                            <div class="adl-form-group">
                                <label for="customer_email"><?php _e('Your email', 'anonymous-dealer-locator'); ?> *</label>
                                <input type="email" id="customer_email" name="customer_email" required>
                            </div>
                            
                            <div class="adl-form-group">
                                <label for="customer_phone"><?php _e('Your phone number', 'anonymous-dealer-locator'); ?></label>
                                <input type="tel" id="customer_phone" name="customer_phone">
                            </div>
                            
                            <div class="adl-form-group">
                                <label for="customer_message"><?php _e('Your message', 'anonymous-dealer-locator'); ?> *</label>
                                <textarea id="customer_message" name="customer_message" rows="4" required placeholder="<?php _e('Describe what you need help with...', 'anonymous-dealer-locator'); ?>"></textarea>
                            </div>
                            
                            <div class="adl-form-group">
                                <button type="submit" class="adl-submit-btn"><?php _e('Send Message', 'anonymous-dealer-locator'); ?></button>
                                <button type="button" class="adl-cancel-btn" onclick="ADL.closeContactModal()"><?php _e('Cancel', 'anonymous-dealer-locator'); ?></button>
                            </div>
                        </form>
                        
                        <div id="adl-form-messages" class="adl-form-messages"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Initialize map when page is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof ADL !== 'undefined') {
                ADL.initMap({
                    center: [<?php echo esc_js($atts['center_lng']); ?>, <?php echo esc_js($atts['center_lat']); ?>],
                    zoom: <?php echo esc_js($atts['zoom']); ?>,
                    searchRadius: <?php echo esc_js($atts['search_radius']); ?>
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
