<?php
/**
 * Demo data script til Anonymous Dealer Locator Plugin
 * 
 * VIGTIGT: Denne fil skal kun køres én gang og derefter slettes af sikkerhedsmæssige årsager.
 * Kør denne fil ved at gå til: yoursite.com/wp-content/plugins/anonymous-dealer-locator/demo-data.php
 */

// Sikkerhedscheck - kun kør hvis WordPress er loadet og bruger er admin
if (!defined('ABSPATH')) {
    // Load WordPress hvis ikke allerede loadet
    require_once('../../../wp-config.php');
}

if (!current_user_can('manage_options')) {
    die('Kun administratorer kan køre dette script.');
}

// Inkluder plugin filer
require_once('anonymous-dealer-locator.php');
require_once('includes/class-adl-database.php');

// Demo forhandlere data
$demo_dealers = array(
    array(
        'name' => 'København Forhandler',
        'email' => 'koebenhavn@example.com',
        'phone' => '+45 12 34 56 78',
        'address' => 'Rådhuspladsen 1, 1550 København',
        'city' => 'København',
        'postal_code' => '1550',
        'country' => 'Denmark',
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'status' => 'active'
    ),
    array(
        'name' => 'Aarhus Forhandler',
        'email' => 'aarhus@example.com',
        'phone' => '+45 87 65 43 21',
        'address' => 'Rådhuspladsen 2, 8000 Aarhus',
        'city' => 'Aarhus',
        'postal_code' => '8000',
        'country' => 'Denmark',
        'latitude' => 56.1629,
        'longitude' => 10.2039,
        'status' => 'active'
    ),
    array(
        'name' => 'Odense Forhandler',
        'email' => 'odense@example.com',
        'phone' => '+45 66 12 34 56',
        'address' => 'Flakhaven 2, 5000 Odense',
        'city' => 'Odense',
        'postal_code' => '5000',
        'country' => 'Denmark',
        'latitude' => 55.4038,
        'longitude' => 10.4024,
        'status' => 'active'
    ),
    array(
        'name' => 'Aalborg Forhandler',
        'email' => 'aalborg@example.com',
        'phone' => '+45 98 76 54 32',
        'address' => 'Budolfi Plads 1, 9000 Aalborg',
        'city' => 'Aalborg',
        'postal_code' => '9000',
        'country' => 'Denmark',
        'latitude' => 57.0488,
        'longitude' => 9.9217,
        'status' => 'active'
    ),
    array(
        'name' => 'Esbjerg Forhandler',
        'email' => 'esbjerg@example.com',
        'phone' => '+45 75 11 22 33',
        'address' => 'Torvet 19, 6700 Esbjerg',
        'city' => 'Esbjerg',
        'postal_code' => '6700',
        'country' => 'Denmark',
        'latitude' => 55.4669,
        'longitude' => 8.4592,
        'status' => 'active'
    )
);

echo '<h1>Anonymous Dealer Locator - Demo Data Installation</h1>';

// Opret tabeller hvis de ikke eksisterer
ADL_Database::createTables();
echo '<p>✓ Database tabeller oprettet/verificeret</p>';

// Tilføj demo forhandlere
$added_count = 0;
$error_count = 0;

foreach ($demo_dealers as $dealer) {
    $result = ADL_Database::addDealer($dealer);
    if ($result) {
        echo '<p>✓ Tilføjet: ' . esc_html($dealer['name']) . ' i ' . esc_html($dealer['city']) . '</p>';
        $added_count++;
    } else {
        echo '<p>✗ Fejl ved tilføjelse af: ' . esc_html($dealer['name']) . '</p>';
        $error_count++;
    }
}

echo '<hr>';
echo '<h2>Resultat</h2>';
echo '<p><strong>Tilføjet:</strong> ' . $added_count . ' forhandlere</p>';
echo '<p><strong>Fejl:</strong> ' . $error_count . ' fejl</p>';

if ($added_count > 0) {
    echo '<hr>';
    echo '<h2>Næste skridt</h2>';
    echo '<ol>';
    echo '<li>Gå til WordPress admin → Dealer Locator for at se dine forhandlere</li>';
    echo '<li>Opret en side og tilføj shortcoden: <code>[dealer_locator]</code></li>';
    echo '<li>Test funktionaliteten på frontend</li>';
    echo '<li><strong>VIGTIGT:</strong> Slet denne demo-data.php fil af sikkerhedsmæssige årsager</li>';
    echo '</ol>';
    
    echo '<hr>';
    echo '<h2>Test email adresser</h2>';
    echo '<p>Forhandlere bruger følgende test email adresser:</p>';
    echo '<ul>';
    foreach ($demo_dealers as $dealer) {
        echo '<li>' . esc_html($dealer['name']) . ': ' . esc_html($dealer['email']) . '</li>';
    }
    echo '</ul>';
    echo '<p><em>Bemærk: Disse er test emails. I produktionsmiljø skal du erstatte med rigtige email adresser.</em></p>';
}

// Sikkerheds påmindelse
echo '<hr>';
echo '<div style="background: #ffebcd; padding: 15px; border: 1px solid #ffa500; border-radius: 5px;">';
echo '<h3 style="color: #ff6600;">⚠️ Sikkerhedspåmindelse</h3>';
echo '<p><strong>Slet denne fil (demo-data.php) efter brug!</strong></p>';
echo '<p>Denne fil indeholder funktionalitet der kan bruges til at manipulere databasen og bør ikke være tilgængelig i produktionsmiljø.</p>';
echo '</div>';

echo '<hr>';
echo '<p><small>Anonymous Dealer Locator Plugin - Demo Data Script</small></p>';
?>
