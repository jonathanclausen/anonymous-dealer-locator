/**
 * Frontend JavaScript for Anonymous Dealer Locator
 */

var ADL = {
    map: null,
    markers: [],
    currentDealerId: null,
    dealersData: null, // Store dealers data for clustering
    hoverPopup: null, // Popup for showing city on hover
    
    /**
     * Initialize map
     */
    initMap: function(options) {
        // Use OpenStreetMap as base (free alternative)
        mapboxgl.accessToken = 'pk.eyJ1Ijoiam9uYTg4MjAiLCJhIjoiY21laGlsZDc0MDcyaDJrcjVzaG1tM2xpNCJ9.wZmYY3_J8xv6yOPv75_TqQ'; // Demo token - change to your own
        
        this.map = new mapboxgl.Map({
            container: 'adl-map',
            style: 'mapbox://styles/mapbox/light-v10', // Light grayed out style
            center: options.center,
            zoom: options.zoom
        });
        
        this.map.on('load', function() {
            // Remove loading indicator
            jQuery('.adl-map-loading').fadeOut();
            
            // Load initial dealers
            ADL.loadDealers();
        });
        
        // Ensure map is ready for clustering
        if (this.map.loaded()) {
            // Map already loaded
        }
        
        // Save search radius
        this.searchRadius = options.searchRadius;
        
        // Initialize search functionality
        this.initSearch();
    },
    
    /**
     * Initialize search functionality
     */
    initSearch: function() {
        var $searchInput = jQuery('#adl-search-input');
        var $searchBtn = jQuery('#adl-search-btn');
        
        // Search button event
        $searchBtn.on('click', function() {
            ADL.performSearch();
        });
        
        // Add geolocation button
        ADL.addGeolocationButton();
        
        // Enter key in search input
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                ADL.performSearch();
            }
        });
        
        // Real-time search (debounced)
        var searchTimeout;
        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                var query = $searchInput.val();
                if (query.length >= 3) {
                    ADL.performSearch();
                }
            }, 500);
        });
    },
    
    /**
     * Perform search
     */
    performSearch: function() {
        var query = jQuery('#adl-search-input').val();
        if (!query) {
            this.loadDealers(); // Show all dealers
            return;
        }
        
        this.showSearchLoading();
        
        jQuery.ajax({
            url: adl_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'adl_search_dealers',
                query: query,
                nonce: adl_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    ADL.displaySearchResults(response.data);
                } else {
                    ADL.showSearchError(response.data || adl_frontend.strings.error);
                }
            },
            error: function() {
                ADL.showSearchError(adl_frontend.strings.error);
            },
            complete: function() {
                ADL.hideSearchLoading();
            }
        });
    },
    
    /**
     * Load all dealers
     */
    loadDealers: function() {
        jQuery.ajax({
            url: adl_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'adl_get_dealers',
                nonce: adl_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    ADL.displayDealers(response.data, false); // false = initial load, fit all dealers
                }
            }
        });
    },
    
    /**
     * Display dealers on map with clustering
     */
    displayDealers: function(dealers, zoomToClosest) {
        // Remove existing markers
        this.clearMarkers();
        
        // Remove cluster layers and event listeners (but keep source if it exists)
        if (this.map && this.map.loaded()) {
            // Remove event listeners
            this.map.off('click', 'clusters');
            this.map.off('click', 'unclustered-point');
            this.map.off('mouseenter', 'clusters');
            this.map.off('mouseleave', 'clusters');
            this.map.off('mouseenter', 'unclustered-point');
            this.map.off('mouseleave', 'unclustered-point');
            
            // Remove layers
            if (this.map.getLayer('clusters')) {
                this.map.removeLayer('clusters');
            }
            if (this.map.getLayer('cluster-count')) {
                this.map.removeLayer('cluster-count');
            }
            if (this.map.getLayer('unclustered-point')) {
                this.map.removeLayer('unclustered-point');
            }
        }
        
        if (!dealers || dealers.length === 0) {
            this.showNoResults();
            return;
        }
        
        // Ensure map is loaded
        if (!this.map || !this.map.loaded()) {
            var self = this;
            this.map.on('load', function() {
                self.displayDealers(dealers, zoomToClosest);
            });
            return;
        }
        
        // Store dealers data
        this.dealersData = dealers;
        
        // Convert dealers to GeoJSON format
        var geoJsonData = this.convertToGeoJSON(dealers);
        
        // Add GeoJSON source with clustering
        if (this.map.getSource('dealers')) {
            this.map.getSource('dealers').setData(geoJsonData);
        } else {
            this.map.addSource('dealers', {
                type: 'geojson',
                data: geoJsonData,
                cluster: true,
                clusterMaxZoom: 12, // Stop clustering at zoom 12
                clusterRadius: 50 // Balanced radius for better distribution at low zoom
            });
        }
        
        // Add cluster circles layer (only for clusters with 2+ points) - only if not already added
        if (!this.map.getLayer('clusters')) {
            this.map.addLayer({
                id: 'clusters',
                type: 'circle',
                source: 'dealers',
                filter: ['all', ['has', 'point_count'], ['>=', ['get', 'point_count'], 2]],
                paint: {
                    'circle-color': [
                        'step',
                        ['get', 'point_count'],
                        '#98c82f',
                        10,
                        '#87b027',
                        50,
                        '#769820'
                    ],
                    'circle-radius': [
                        'step',
                        ['get', 'point_count'],
                        20,
                        10,
                        25,
                        50,
                        30
                    ],
                    'circle-stroke-width': 2,
                    'circle-stroke-color': '#fff'
                }
            });
        }
        
        // Add cluster count labels (only for clusters with 2+ points) - only if not already added
        if (!this.map.getLayer('cluster-count')) {
            this.map.addLayer({
                id: 'cluster-count',
                type: 'symbol',
                source: 'dealers',
                filter: ['all', ['has', 'point_count'], ['>=', ['get', 'point_count'], 2]],
                layout: {
                    'text-field': '{point_count_abbreviated}',
                    'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                    'text-size': 14
                },
                paint: {
                    'text-color': '#fff'
                }
            });
        }
        
        // Add unclustered points (individual dealers) - only if not already added
        if (!this.map.getLayer('unclustered-point')) {
            this.map.addLayer({
                id: 'unclustered-point',
                type: 'circle',
                source: 'dealers',
                filter: ['!', ['has', 'point_count']],
                paint: {
                    'circle-color': '#98c82f',
                    'circle-radius': 8,
                    'circle-stroke-width': 2,
                    'circle-stroke-color': '#fff'
                }
            });
        }
        
        // Handle cluster click - zoom in
        this.map.on('click', 'clusters', function(e) {
            var features = this.map.queryRenderedFeatures(e.point, {
                layers: ['clusters']
            });
            var clusterId = features[0].properties.cluster_id;
            this.map.getSource('dealers').getClusterExpansionZoom(
                clusterId,
                function(err, zoom) {
                    if (err) return;
                    
                    this.map.easeTo({
                        center: features[0].geometry.coordinates,
                        zoom: zoom
                    });
                }.bind(this)
            );
        }.bind(this));
        
        // Handle individual marker click - open contact modal
        this.map.on('click', 'unclustered-point', function(e) {
            var coordinates = e.features[0].geometry.coordinates.slice();
            var dealerId = e.features[0].properties.dealerId;
            ADL.openContactModal(dealerId);
        });
        
        // Change cursor on hover
        this.map.on('mouseenter', 'clusters', function() {
            this.map.getCanvas().style.cursor = 'pointer';
        }.bind(this));
        
        this.map.on('mouseleave', 'clusters', function() {
            this.map.getCanvas().style.cursor = '';
        }.bind(this));
        
        this.map.on('mouseenter', 'unclustered-point', function(e) {
            this.map.getCanvas().style.cursor = 'pointer';
            
            // Show city popup on hover
            var feature = e.features[0];
            var city = feature.properties.city;
            
            if (city) {
                // Escape HTML
                var cityText = city.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                
                // Remove existing popup if any
                if (this.hoverPopup) {
                    this.hoverPopup.remove();
                }
                
                // Create and show popup
                this.hoverPopup = new mapboxgl.Popup({
                    closeButton: false,
                    closeOnClick: false,
                    className: 'adl-marker-popup'
                })
                .setLngLat(e.lngLat)
                .setHTML('<div class="adl-popup-content">' + cityText + '</div>')
                .addTo(this.map);
            }
        }.bind(this));
        
        this.map.on('mouseleave', 'unclustered-point', function() {
            this.map.getCanvas().style.cursor = '';
            
            // Remove popup on mouse leave
            if (this.hoverPopup) {
                this.hoverPopup.remove();
                this.hoverPopup = null;
            }
        }.bind(this));
        
        // Handle map zooming based on context
        if (zoomToClosest === true) {
            // Search results or geolocation - zoom to closest dealer
            if (dealers.length > 0) {
                // Always zoom to the closest dealer (first in array)
                this.map.flyTo({
                    center: [parseFloat(dealers[0].longitude), parseFloat(dealers[0].latitude)],
                    zoom: 12
                });
            }
        } else if (zoomToClosest === false) {
            // Initial load - fit bounds to show all dealers
            if (dealers.length > 1) {
                this.fitMapToBounds(dealers);
            } else if (dealers.length === 1) {
                // Even single dealer should be shown at moderate zoom for initial load
                this.map.flyTo({
                    center: [parseFloat(dealers[0].longitude), parseFloat(dealers[0].latitude)],
                    zoom: 8
                });
            }
        }
        // If zoomToClosest is undefined, don't change map view (markers only)
    },
    
    /**
     * Convert dealers array to GeoJSON format
     */
    convertToGeoJSON: function(dealers) {
        // Remove duplicates based on coordinates to prevent false clustering
        var seen = {};
        var uniqueDealers = dealers.filter(function(dealer) {
            var key = parseFloat(dealer.latitude).toFixed(6) + ',' + parseFloat(dealer.longitude).toFixed(6);
            if (seen[key]) {
                return false;
            }
            seen[key] = true;
            return true;
        });
        
        var features = uniqueDealers.map(function(dealer) {
            return {
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [parseFloat(dealer.longitude), parseFloat(dealer.latitude)]
                },
                properties: {
                    dealerId: dealer.id,
                    city: dealer.city || ''
                }
            };
        });
        
        return {
            type: 'FeatureCollection',
            features: features
        };
    },
    
    /**
     * Remove cluster layers and source
     */
    removeClusterLayers: function() {
        if (this.map.getLayer('clusters')) {
            this.map.removeLayer('clusters');
        }
        if (this.map.getLayer('cluster-count')) {
            this.map.removeLayer('cluster-count');
        }
        if (this.map.getLayer('unclustered-point')) {
            this.map.removeLayer('unclustered-point');
        }
        if (this.map.getSource('dealers')) {
            this.map.removeSource('dealers');
        }
    },
    
    /**
     * Add dealer marker to map
     */
    addDealerMarker: function(dealer) {
        // Create modern anonymous marker
        var el = document.createElement('div');
        el.className = 'adl-marker';
        el.innerHTML = '<div class="adl-marker-icon"></div>';
        
        var marker = new mapboxgl.Marker({
            element: el,
            anchor: 'center'
        })
            .setLngLat([parseFloat(dealer.longitude), parseFloat(dealer.latitude)])
            .addTo(this.map);
        
        // Create popup for hover (showing town/city)
        var popup = new mapboxgl.Popup({
            closeButton: false,
            closeOnClick: false,
            className: 'adl-marker-popup'
        });
        
        var map = this.map; // Capture map reference for event listeners
        
        // Show popup on hover
        el.addEventListener('mouseenter', function() {
            if (dealer.city) {
                var cityText = dealer.city.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                popup.setLngLat([parseFloat(dealer.longitude), parseFloat(dealer.latitude)])
                    .setHTML('<div class="adl-popup-content">' + cityText + '</div>')
                    .addTo(map);
            }
        });
        
        // Hide popup on mouse leave
        el.addEventListener('mouseleave', function() {
            popup.remove();
        });
        
        // Click event for marker
        el.addEventListener('click', function() {
            ADL.openContactModal(dealer.id);
        });
        
        this.markers.push(marker);
    },
    
    /**
     * Fit map to show all dealers
     */
    fitMapToBounds: function(dealers) {
        var bounds = new mapboxgl.LngLatBounds();
        
        dealers.forEach(function(dealer) {
            bounds.extend([parseFloat(dealer.longitude), parseFloat(dealer.latitude)]);
        });
        
        this.map.fitBounds(bounds, {
            padding: 50,
            maxZoom: 15
        });
    },
    
    /**
     * Remove all markers
     */
    clearMarkers: function() {
        this.markers.forEach(function(marker) {
            marker.remove();
        });
        this.markers = [];
    },
    
    /**
     * Remove cluster layers and event listeners
     */
    removeClusterLayers: function() {
        if (!this.map || !this.map.loaded()) {
            return;
        }
        
        // Remove event listeners
        if (this.map.off) {
            this.map.off('click', 'clusters');
            this.map.off('click', 'unclustered-point');
            this.map.off('mouseenter', 'clusters');
            this.map.off('mouseleave', 'clusters');
            this.map.off('mouseenter', 'unclustered-point');
            this.map.off('mouseleave', 'unclustered-point');
        }
        
        // Remove layers
        if (this.map.getLayer('clusters')) {
            this.map.removeLayer('clusters');
        }
        if (this.map.getLayer('cluster-count')) {
            this.map.removeLayer('cluster-count');
        }
        if (this.map.getLayer('unclustered-point')) {
            this.map.removeLayer('unclustered-point');
        }
        
        // Remove source
        if (this.map.getSource('dealers')) {
            this.map.removeSource('dealers');
        }
    },
    
    /**
     * Display search results
     */
    displaySearchResults: function(data) {
        var $results = jQuery('#adl-search-results');
        
        if (!data.dealers || data.dealers.length === 0) {
            $results.html('<div class="adl-no-results">' + adl_frontend.strings.no_results + '</div>');
            this.clearMarkers();
            return;
        }
        
        // Display dealers on map and zoom to closest
        this.displayDealers(data.dealers, true);
        
        // Display textual results
        var resultsHtml = '<div class="adl-results-header">';
        if (data.dealers[0] && data.dealers[0].distance) {
            resultsHtml += 'Closest dealer: ' + data.dealers[0].distance + ' km away';
        }
        resultsHtml += '</div>';
        
        $results.html(resultsHtml);
        
        // Note: Map zooming is handled by displayDealers() function
        // which focuses on the dealers themselves, not the search location
    },
    
    /**
     * Open contact modal
     */
    openContactModal: function(dealerId) {
        this.currentDealerId = dealerId;
        jQuery('#dealer_id').val(dealerId);
        jQuery('#adl-contact-modal').fadeIn();
        
        // Reset form
        jQuery('#adl-contact-form')[0].reset();
        jQuery('#adl-form-messages').empty();
    },
    
    /**
     * Close contact modal
     */
    closeContactModal: function() {
        jQuery('#adl-contact-modal').fadeOut();
        this.currentDealerId = null;
    },
    
    /**
     * Show search loading
     */
    showSearchLoading: function() {
        var $btn = jQuery('#adl-search-btn');
        $btn.prop('disabled', true);
        $btn.addClass('adl-loading');
    },
    
    /**
     * Hide search loading
     */
    hideSearchLoading: function() {
        var $btn = jQuery('#adl-search-btn');
        $btn.prop('disabled', false);
        $btn.removeClass('adl-loading');
    },
    
    /**
     * Show search error
     */
    showSearchError: function(message) {
        jQuery('#adl-search-results').html('<div class="adl-error">' + message + '</div>');
    },
    
    /**
     * Show no results
     */
    showNoResults: function() {
        jQuery('#adl-search-results').html('<div class="adl-no-results">' + adl_frontend.strings.no_results + '</div>');
    },
    
    /**
     * Add geolocation button
     */
    addGeolocationButton: function() {
        if (!navigator.geolocation) {
            return; // Geolocation not supported
        }
        
        var $mapContainer = jQuery('.adl-map-container');
        var $geoBtn = jQuery('<button type="button" id="adl-geolocation-btn" class="adl-geo-btn" title="Use my current location" aria-label="Use my current location"></button>');
        
        $mapContainer.append($geoBtn);
        
        $geoBtn.on('click', function() {
            ADL.getCurrentLocation();
        });
    },
    
    /**
     * Get user's current location
     */
    getCurrentLocation: function() {
        var $geoBtn = jQuery('#adl-geolocation-btn');
        $geoBtn.prop('disabled', true).addClass('adl-geo-loading');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                
                // Add user marker first
                ADL.addUserMarker(lat, lng);
                
                // Search for dealers near user's position (this will handle map zooming to dealers)
                ADL.searchNearbyDealers(lat, lng);
                
                $geoBtn.prop('disabled', false).removeClass('adl-geo-loading');
            },
            function(error) {
                var errorMessage;
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = "Location access was denied.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = "Location data is not available.";
                        break;
                    case error.TIMEOUT:
                        errorMessage = "Location request timed out.";
                        break;
                    default:
                        errorMessage = "An unknown error occurred.";
                        break;
                }
                
                jQuery('#adl-search-results').html('<div class="adl-error">' + errorMessage + '</div>');
                $geoBtn.prop('disabled', false).removeClass('adl-geo-loading');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // Cache position for 5 minutes
            }
        );
    },
    
    /**
     * Search for dealers near coordinates
     */
    searchNearbyDealers: function(lat, lng) {
        this.showSearchLoading();
        
        jQuery.ajax({
            url: adl_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'adl_search_nearby_dealers',
                latitude: lat,
                longitude: lng,
                nonce: adl_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    ADL.displayDealers(response.data.dealers, true);
                    
                    // Show number of results
                    var resultsHtml = '<div class="adl-results-header">';
                    if (response.data.dealers.length === 0) {
                        resultsHtml += 'No dealers found';
                    } else if (response.data.dealers.length === 1) {
                        resultsHtml += 'Found <strong>1</strong> dealer';
                        if (response.data.dealers[0].distance) {
                            resultsHtml += ' (' + response.data.dealers[0].distance + ' km away)';
                        }
                    } else {
                        resultsHtml += 'Found <strong>' + response.data.dealers.length + '</strong> dealers';
                        if (response.data.dealers[0].distance) {
                            resultsHtml += ' (closest: ' + response.data.dealers[0].distance + ' km away)';
                        }
                    }
                    resultsHtml += '</div>';
                    jQuery('#adl-search-results').html(resultsHtml);
                    
                } else {
                    ADL.showSearchError(response.data || 'No dealers found in the area.');
                }
            },
            error: function() {
                ADL.showSearchError('Error searching for dealers nearby.');
            },
            complete: function() {
                ADL.hideSearchLoading();
            }
        });
    },
    
    /**
     * Add user marker
     */
    addUserMarker: function(lat, lng) {
        // Remove existing user marker
        if (this.userMarker) {
            this.userMarker.remove();
        }
        
        // Create user marker
        var el = document.createElement('div');
        el.className = 'adl-user-marker';
        el.innerHTML = '<div class="adl-user-marker-icon"></div>';
        
        this.userMarker = new mapboxgl.Marker({
            element: el,
            anchor: 'center'
        })
            .setLngLat([lng, lat])
            .addTo(this.map);
    }
};

// Document ready
jQuery(document).ready(function($) {
    
    // Contact form submission
    $('#adl-contact-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'adl_send_contact_form',
            dealer_id: $('#dealer_id').val(),
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            customer_message: $('#customer_message').val(),
            nonce: adl_frontend.nonce
        };
        
        // Validate form
        if (!formData.customer_name || !formData.customer_email || !formData.customer_message) {
            ADL.showFormMessage(adl_frontend.strings.form_required, 'error');
            return;
        }
        
        // Disable submit button
        var $submitBtn = $('.adl-submit-btn');
        $submitBtn.prop('disabled', true).text('Sending...');
        
        // Send AJAX request
        $.ajax({
            url: adl_frontend.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    ADL.showFormMessage(adl_frontend.strings.form_success, 'success');
                    setTimeout(function() {
                        ADL.closeContactModal();
                    }, 2000);
                } else {
                    ADL.showFormMessage(response.data || adl_frontend.strings.form_error, 'error');
                }
            },
            error: function() {
                ADL.showFormMessage(adl_frontend.strings.form_error, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(adl_frontend.strings.submit_button);
            }
        });
    });
    
    // Modal closing
    $('.adl-modal-close').on('click', function() {
        ADL.closeContactModal();
    });
    
    // Close modal when clicking outside of it
    $('#adl-contact-modal').on('click', function(e) {
        if (e.target === this) {
            ADL.closeContactModal();
        }
    });
    
    // ESC key lukker modal
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC key
            ADL.closeContactModal();
        }
    });
});

// Add showFormMessage method
ADL.showFormMessage = function(message, type) {
    var className = type === 'success' ? 'adl-success' : 'adl-error';
    jQuery('#adl-form-messages').html('<div class="' + className + '">' + message + '</div>');
};
