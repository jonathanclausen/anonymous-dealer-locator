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
			__('Import Dealers', 'anonymous-dealer-locator'),
			__('Import Dealers', 'anonymous-dealer-locator'),
			'manage_options',
			'adl-import-dealers',
			array($this, 'importDealersPage')
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
		
		// Import dealers
		if (isset($_POST['adl_import_dealers']) && wp_verify_nonce($_POST['adl_nonce'], 'adl_import_dealers')) {
			$this->processImportDealers();
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
        
        // Compute missing coordinates count and optionally filter
        $missing_count = 0;
        $show_missing_only = isset($_GET['adl_missing']) && $_GET['adl_missing'] === '1';
        
        $isMissing = function($dealer) {
            $lat = floatval($dealer->latitude);
            $lng = floatval($dealer->longitude);
            if (!is_numeric($lat) || !is_numeric($lng)) {
                return true;
            }
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return true;
            }
            // Consider 0,0 as missing (import fallback)
            if ($lat == 0 && $lng == 0) {
                return true;
            }
            return false;
        };
        
        foreach ($dealers as $d) {
            if ($isMissing($d)) {
                $missing_count++;
            }
        }
        
        if ($show_missing_only) {
            $dealers = array_values(array_filter($dealers, $isMissing));
        }
        
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
	 * Import dealers page
	 */
	public function importDealersPage() {
		include ADL_PLUGIN_PATH . 'includes/admin-templates/import-page.php';
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
	
	/**
	 * Process dealers import (CSV or server path)
	 */
	private function processImportDealers() {
		$uploaded_file_path = '';
		
		// Try uploaded file first
		if (!empty($_FILES['import_file']['name'])) {
			$overrides = array('test_form' => false, 'mimes' => array(
				'csv' => 'text/csv',
				'txt' => 'text/plain',
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			));
			$upload = wp_handle_upload($_FILES['import_file'], $overrides);
			if (!isset($upload['error'])) {
				$uploaded_file_path = $upload['file'];
			} else {
				add_action('admin_notices', function() use ($upload) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($upload['error']) . '</p></div>';
				});
				return;
			}
		}
		
		// Or server file path
		$server_path = isset($_POST['file_path']) ? trim(wp_unslash($_POST['file_path'])) : '';
		$file_path = $uploaded_file_path ?: $server_path;
		
		if (empty($file_path)) {
			add_action('admin_notices', function() {
				echo '<div class="notice notice-error is-dismissible"><p>' . __('Please upload a file or provide a valid server file path.', 'anonymous-dealer-locator') . '</p></div>';
			});
			return;
		}
		
		// If a server path was provided, validate it
		if ($file_path === $server_path) {
			// Normalize Windows path backslashes
			$file_path = str_replace(array('\\', '//'), DIRECTORY_SEPARATOR, $file_path);
			if (!file_exists($file_path) || !is_readable($file_path)) {
				add_action('admin_notices', function() {
					echo '<div class="notice notice-error is-dismissible"><p>' . __('The provided file path does not exist or is not readable.', 'anonymous-dealer-locator') . '</p></div>';
				});
				return;
			}
		}
		
		$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
		
		$rows = array();
		if ($ext === 'csv' || $ext === 'txt') {
			$rows = $this->readCsv($file_path);
		} elseif ($ext === 'xlsx') {
			add_action('admin_notices', function() {
				echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('XLSX import is not enabled yet. Please export the Excel file as CSV (UTF-8) and try again.', 'anonymous-dealer-locator') . '</p></div>';
			});
			return;
		} else {
			add_action('admin_notices', function() {
				echo '<div class="notice notice-error is-dismissible"><p>' . __('Unsupported file type. Please upload a CSV file.', 'anonymous-dealer-locator') . '</p></div>';
			});
			return;
		}
		
		if (empty($rows) || count($rows) < 2) {
			add_action('admin_notices', function() {
				echo '<div class="notice notice-error is-dismissible"><p>' . __('The file seems empty or missing data rows.', 'anonymous-dealer-locator') . '</p></div>';
			});
			return;
		}
		
		// Map headers
		$header = array_map(array($this, 'normalizeHeader'), array_shift($rows));
		$index = array();
		foreach ($header as $i => $col) {
			$index[$col] = $i;
		}
		
		$required = array('name', 'email', 'address');
		foreach ($required as $req) {
			if (!isset($index[$req])) {
				add_action('admin_notices', function() use ($req) {
					echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(esc_html__('Missing required column: %s', 'anonymous-dealer-locator'), esc_html($req)) . '</p></div>';
				});
				return;
			}
		}
		
		$added = 0;
		$failed = 0;
		
		foreach ($rows as $row) {
			if ($this->rowIsEmpty($row)) {
				continue;
			}
			
			$get = function($key, $default = '') use ($row, $index) {
                return isset($index[$key], $row[$index[$key]]) ? trim((string) $row[$index[$key]]) : $default;
            };
			
			$address = $get('address');
			$city = $get('city');
			$postal = $get('postal_code') ?: $get('postal') ?: $get('zip');
			$country = $get('country');
			
			$lat = $get('latitude');
			$lng = $get('longitude');
			
			if ($lat === '' || $lng === '') {
				$parts = array_filter(array($address, $postal, $city, $country));
				$query = implode(', ', $parts);
				if (!empty($query)) {
					$coords = $this->getCoordinatesFromAddress($query);
					if ($coords && !isset($coords['error'])) {
						$lat = $coords['latitude'];
						$lng = $coords['longitude'];
						// Fill back city/postcode/country if available
						if ($city === '' && !empty($coords['city'])) {
							$city = $coords['city'];
						}
						if ($postal === '' && !empty($coords['postcode'])) {
							$postal = $coords['postcode'];
						}
						if ($country === '' && !empty($coords['country'])) {
							$country = $coords['country'];
						}
					}
				}
			}
			
			$data = array(
				'name' => $get('name'),
				'email' => $get('email'),
				'phone' => $get('phone'),
				'address' => $address,
				'city' => $city,
				'postal_code' => $postal,
				'country' => $country,
				'latitude' => $lat !== '' ? $lat : 0,
				'longitude' => $lng !== '' ? $lng : 0,
				'status' => $get('status', 'active') ?: 'active'
			);
			
			$result = ADL_Database::addDealer($data);
			if ($result) {
				$added++;
			} else {
				$failed++;
			}
		}
		
		add_action('admin_notices', function() use ($added, $failed) {
			echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d dealers imported, %d failed.', 'anonymous-dealer-locator'), intval($added), intval($failed)) . '</p></div>';
		});
	}
	
	/**
	 * Normalize header names to snake-case keys
	 */
	private function normalizeHeader($label) {
		$label = strtolower(trim((string) $label));
		$label = str_replace(array('#', '.', '  '), array('', '', ' '), $label);
		$label = preg_replace('/[^a-z0-9]+/', '_', $label);
		$aliases = array(
			'zip' => 'postal_code',
			'postcode' => 'postal_code',
			'postal' => 'postal_code'
		);
		if (isset($aliases[$label])) {
			return $aliases[$label];
		}
		return $label;
	}
	
	/**
	 * Check if CSV row is empty
	 */
	private function rowIsEmpty($row) {
		foreach ($row as $cell) {
			if (trim((string) $cell) !== '') {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Read CSV into array of rows
	 */
	private function readCsv($path) {
		$rows = array();
		$handle = fopen($path, 'r');
		if ($handle === false) {
			return $rows;
		}
		
		$delimiter = $this->detectDelimiter($path);
		while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
			$rows[] = $data;
		}
		fclose($handle);
		return $rows;
	}
	
	/**
	 * Attempt to detect CSV delimiter
	 */
	private function detectDelimiter($path) {
		$delimiters = array(',', ';', "\t", '|');
		$firstLine = '';
		$fh = fopen($path, 'r');
		if ($fh !== false) {
			$firstLine = fgets($fh);
			fclose($fh);
		}
		$best = ',';
		$bestCount = 0;
		foreach ($delimiters as $d) {
			$count = substr_count((string) $firstLine, $d);
			if ($count > $bestCount) {
				$best = $d;
				$bestCount = $count;
			}
		}
		return $best;
	}
}
