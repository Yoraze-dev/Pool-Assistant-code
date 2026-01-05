<?php
/**
 * Template pour la page Connexion (/connexion/)
 * Utilise le système de templates personnalisés de PoolTracker
 */

// Sécurité WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que les constantes PoolTracker sont définies
if (!defined('POOLTRACKER_PATH') || !defined('POOLTRACKER_URL') || !defined('POOLTRACKER_VERSION')) {
    wp_die('PoolTracker non initialisé correctement');
}

// Vérifier la configuration Auth0
$auth0_domain = get_option('pooltracker_auth0_domain', '');
$auth0_client_id = get_option('pooltracker_auth0_client_id', '');

// Charger les assets PoolTracker
wp_enqueue_style(
    'pooltracker-styles',
    POOLTRACKER_URL . 'assets/pooltracker.css',
    array(),
    POOLTRACKER_VERSION
);

// Auth0 SDK
wp_enqueue_script(
    'auth0-js',
    'https://cdn.auth0.com/js/auth0/9.19.0/auth0.min.js',
    array(),
    '9.19.0',
    true
);

// JavaScript PoolTracker
wp_enqueue_script(
    'pooltracker-js',
    POOLTRACKER_URL . 'assets/pooltracker.js',
    array('auth0-js'),
    POOLTRACKER_VERSION,
    true
);

// Configuration JavaScript pour PoolTracker
wp_localize_script('pooltracker-js', 'poolTracker', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('pooltracker_nonce'),
    'auth0_domain' => $auth0_domain,
    'auth0_client_id' => $auth0_client_id,
    'login_url' => home_url('/connexion/'),
    'dashboard_url' => home_url('/espace-client/'),
    'is_logged_in' => pooltracker_is_user_authenticated(),
    'current_page' => 'login'
));

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>PoolTracker - Connexion</title>
    
    <!-- Favicon du site -->
    <?php if (function_exists('get_site_icon_url') && get_site_icon_url()) : ?>
        <link rel="icon" href="<?php echo get_site_icon_url(); ?>">
    <?php endif; ?>
    
    <!-- WordPress Head -->
    <?php wp_head(); ?>
    
    <!-- Styles spécifiques à la page login -->
    <style>
        /* Reset WordPress admin bar si présent */
        html { margin-top: 0 !important; }
        * html body { margin-top: 0 !important; }
        
        /* Masquer certains éléments WordPress non nécessaires */
        #wpadminbar { display: none !important; }
        
        /* Style de base pour la page de connexion */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Loading initial */
        .pooltracker-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
            font-size: 18px;
        }
        
        .pooltracker-loading .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.3);
            border-left: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Conteneur principal de connexion */
        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        /* Message d'erreur configuration */
        .config-error {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px;
        }
        
        .config-error h3 {
            margin-top: 0;
            color: #721c24;
        }
    </style>
</head>
<body <?php body_class('pooltracker-login-page'); ?>>

    <!-- Loading initial -->
    <div class="pooltracker-loading" id="pooltracker-initial-loading">
        <div class="spinner"></div>
        <span>Initialisation de PoolTracker...</span>
    </div>

    <!-- Contenu principal -->
    <div id="pooltracker-main-content" style="display: none;">
        <div class="login-container">
            <?php
            // Vérifier la configuration Auth0
            if (empty($auth0_domain) || empty($auth0_client_id)) {
                ?>
                <div class="config-error">
                    <h3>Configuration manquante</h3>
                    <p>Les paramètres Auth0 ne sont pas configurés. Contactez l'administrateur.</p>
                    <p><strong>Domaine:</strong> <?php echo $auth0_domain ? 'Configuré' : 'Manquant'; ?></p>
                    <p><strong>Client ID:</strong> <?php echo $auth0_client_id ? 'Configuré' : 'Manquant'; ?></p>
                    
                    <?php if (current_user_can('manage_options')) : ?>
                        <a href="<?php echo admin_url('admin.php?page=pooltracker-auth0'); ?>" 
                           style="background: #3AA6B9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 15px;">
                            Configurer Auth0
                        </a>
                    <?php endif; ?>
                </div>
                <?php
            } else {
                // Charger le template de connexion
                $login_template = POOLTRACKER_PATH . 'templates/login.php';
                
                if (file_exists($login_template)) {
                    include $login_template;
                } else {
                    // Fallback si le template login.php n'existe pas encore
                    ?>
                    <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="margin-bottom: 30px;">
                            <img src="https://poolassistant.fr/wp-content/uploads/2025/08/Coconut-Logo-6.png" 
                                 alt="Pool Assistant" style="height: 80px; width: auto;">
                        </div>
                        
                        <h2 style="color: #3AA6B9; margin-bottom: 15px;">Connexion PoolTracker</h2>
                        <p style="color: #666; margin-bottom: 25px;">Le template de connexion complet sera bientôt créé.</p>
                        
                        <!-- Bouton de test -->
                        <button id="test-auth0" style="background: #3AA6B9; color: white; border: none; padding: 15px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600;">
                            Test Connexion Auth0
                        </button>
                        
                        <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; font-size: 14px; text-align: left;">
                            <strong>Configuration Auth0 détectée :</strong><br>
                            <div style="margin-top: 10px;">
                                <div>Domaine: <code><?php echo esc_html($auth0_domain); ?></code></div>
                                <div>Client ID: <code><?php echo esc_html(substr($auth0_client_id, 0, 10) . '...'); ?></code></div>
                            </div>
                        </div>
                        
                        <script>
                        document.getElementById('test-auth0').addEventListener('click', function() {
                            alert('Test Auth0 - Le système est configuré !\n\nDomaine: <?php echo esc_js($auth0_domain); ?>\nClient ID: <?php echo esc_js(substr($auth0_client_id, 0, 10) . '...'); ?>');
                        });
                        </script>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>

    <!-- WordPress Footer -->
    <?php wp_footer(); ?>
    
    <!-- Script de fin de chargement -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si déjà connecté
        if (typeof poolTracker !== 'undefined' && poolTracker.is_logged_in === true) {
            // Redirection si déjà connecté
            window.location.href = poolTracker.dashboard_url;
            return;
        }
        
        // Masquer le loading et afficher le contenu après un court délai
        setTimeout(function() {
            document.getElementById('pooltracker-initial-loading').style.display = 'none';
            document.getElementById('pooltracker-main-content').style.display = 'block';
        }, 500);
        
        console.log('PoolTracker Login Page chargée');
        console.log('Auth0 Config:', {
            domain: poolTracker.auth0_domain,
            clientId: poolTracker.auth0_client_id
        });
    });
    </script>

</body>
</html>