<?php
/**
 * Plugin Name: PoolTracker Auth0
 * Description: Système complet de suivi de piscine avec authentification Auth0
 * Version: 2.6.0
 * Author: Pool Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('POOLTRACKER_PATH', plugin_dir_path(__FILE__));
define('POOLTRACKER_URL', plugin_dir_url(__FILE__));
define('POOLTRACKER_VERSION', '2.6.0');

// Autoloader simple
spl_autoload_register(function ($class) {
    if (strpos($class, 'PoolTracker') === 0) {
        $file = POOLTRACKER_PATH . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Chargement des fichiers principaux
require_once POOLTRACKER_PATH . 'includes/class-pooltracker.php';
require_once POOLTRACKER_PATH . 'includes/auth0-functions.php';
require_once POOLTRACKER_PATH . 'includes/user-functions.php';
require_once POOLTRACKER_PATH . 'includes/ajax-handlers.php';
require_once POOLTRACKER_PATH . 'includes/database.php';

// Initialisation
function pooltracker_init() {
    new PoolTracker();
}
add_action('plugins_loaded', 'pooltracker_init');

// Activation/Désactivation
register_activation_hook(__FILE__, 'pooltracker_activate');
register_deactivation_hook(__FILE__, 'pooltracker_deactivate');

function pooltracker_activate() {
    pooltracker_create_tables();
    flush_rewrite_rules();
}

function pooltracker_deactivate() {
    flush_rewrite_rules();
}