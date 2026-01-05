<?php
/**
 * Template pour la page Espace Client (/espace-client/)
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

// Charger les assets PoolTracker
wp_enqueue_style(
    'pooltracker-styles',
    POOLTRACKER_URL . 'assets/pooltracker.css',
    array(),
    POOLTRACKER_VERSION
);

wp_enqueue_script(
    'chart-js', 
    'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
    array(), 
    '3.9.1', 
    true
);

wp_enqueue_script(
    'pooltracker-js',
    POOLTRACKER_URL . 'assets/pooltracker.js',
    array('chart-js'),
    POOLTRACKER_VERSION,
    true
);

// Configuration JavaScript pour PoolTracker
wp_localize_script('pooltracker-js', 'poolTracker', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('pooltracker_nonce'),
    'auth0_domain' => get_option('pooltracker_auth0_domain', ''),
    'auth0_client_id' => get_option('pooltracker_auth0_client_id', ''),
    'login_url' => home_url('/connexion/'),
    'dashboard_url' => home_url('/espace-client/'),
    'is_logged_in' => pooltracker_is_user_authenticated(),
    'current_page' => 'dashboard'
));

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>PoolTracker - Espace Client</title>
    
    <!-- Favicon du site -->
    <?php if (function_exists('get_site_icon_url') && get_site_icon_url()) : ?>
        <link rel="icon" href="<?php echo get_site_icon_url(); ?>">
    <?php endif; ?>
    
    <!-- WordPress Head -->
    <?php wp_head(); ?>
    
    <!-- Styles spécifiques à la page dashboard -->
    <style>
        /* Reset WordPress admin bar si présent */
        html { margin-top: 0 !important; }
        * html body { margin-top: 0 !important; }
        
        /* Masquer certains éléments WordPress non nécessaires */
        #wpadminbar { display: none !important; }
        
        /* Style de base pour l'app */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        /* Loading initial */
        .pooltracker-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: #3AA6B9;
            font-size: 18px;
        }
        
        .pooltracker-loading .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-left: 4px solid #3AA6B9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body <?php body_class('pooltracker-dashboard-page'); ?>>

    <!-- Loading initial -->
    <div class="pooltracker-loading" id="pooltracker-initial-loading">
        <div class="spinner"></div>
        <span>Chargement de votre espace PoolTracker...</span>
    </div>

    <!-- Contenu principal -->
    <div id="pooltracker-main-content" style="display: none;">
        <?php
        // Charger le template dashboard
        $dashboard_template = POOLTRACKER_PATH . 'templates/dashboard.php';
        
        if (file_exists($dashboard_template)) {
            include $dashboard_template;
        } else {
            // Fallback si le template dashboard.php n'existe pas encore
            ?>
            <div style="background: #3AA6B9; margin: 0; min-height: 100vh; color: white; padding: 40px; text-align: center;">
                <div style="max-width: 600px; margin: 0 auto; padding-top: 100px;">
                    <h2 style="color: white !important; margin-bottom: 15px; font-size: 28px;">Votre Espace PoolTracker</h2>
                    <p style="margin-bottom: 25px; color: white;">Bienvenue dans votre espace client !</p>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 40px;">Le template dashboard.php sera bientôt créé.</p>
                    
                    <!-- Boutons d'action -->
                    <div style="display: flex; flex-direction: column; gap: 15px; align-items: center; max-width: 300px; margin: 0 auto;">
                        <button id="return-site" style="background: white; color: #3AA6B9; border: none; padding: 15px 30px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; width: 100%; transition: all 0.3s;">
                            ← Revenir au site
                        </button>
                        
                        <button id="temp-logout" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 12px 24px; border-radius: 6px; cursor: pointer; width: 100%; transition: all 0.3s;">
                            Déconnexion
                        </button>
                    </div>
                </div>
                
                <script>
                document.getElementById('return-site').addEventListener('click', function() {
                    console.log('Clic sur Revenir au site');
                    window.location.href = '<?php echo home_url(); ?>';
                });
                
                document.getElementById('temp-logout').addEventListener('click', function() {
                    if (confirm('Se déconnecter ?')) {
                        console.log('Déconnexion demandée');
                        fetch(poolTracker.ajax_url, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: new URLSearchParams({
                                'action': 'pool_logout',
                                '_wpnonce': poolTracker.nonce
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = '/connexion/';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur déconnexion:', error);
                        });
                    }
                });
                
                // Debug pour vérifier que les éléments existent
                console.log('Bouton return-site:', document.getElementById('return-site'));
                console.log('Bouton temp-logout:', document.getElementById('temp-logout'));
                </script>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- WordPress Footer -->
    <?php wp_footer(); ?>
    
    <!-- Script de fin de chargement -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier l'authentification
        if (typeof poolTracker !== 'undefined' && poolTracker.is_logged_in === false) {
            // Redirection si pas connecté
            window.location.href = poolTracker.login_url;
            return;
        }
        
        // Masquer le loading et afficher le contenu après un court délai
        setTimeout(function() {
            document.getElementById('pooltracker-initial-loading').style.display = 'none';
            document.getElementById('pooltracker-main-content').style.display = 'block';
        }, 500);
        
        console.log('PoolTracker Dashboard Page chargée');
    });
    </script>
    
    <script>
console.log('🔍 POOLTRACKER DEBUG DÉMARRÉ');

// Test 1: Vérifier l'objet poolTracker
console.log('=== TEST 1: Objet poolTracker ===');
console.log('poolTracker exists:', typeof window.poolTracker !== 'undefined');
if (window.poolTracker) {
    console.log('poolTracker.ajax_url:', window.poolTracker.ajax_url);
    console.log('poolTracker.nonce:', window.poolTracker.nonce);
    console.log('poolTracker.is_logged_in:', window.poolTracker.is_logged_in);
} else {
    console.log('❌ poolTracker object not found!');
}

// Test 2: Debug système complet
console.log('=== TEST 2: Debug système ===');
jQuery.post(
    window.poolTracker ? window.poolTracker.ajax_url : '/wp-admin/admin-ajax.php',
    {
        action: 'pooltracker_debug_system',
        nonce: window.poolTracker ? window.poolTracker.nonce : 'test'
    },
    function(response) {
        console.log('✅ Debug système réussi:', response);
        
        if (response.data && response.data.debug_data) {
            const debug = response.data.debug_data;
            
            console.log('🔍 Session status:', debug.session.session_status);
            console.log('🔍 Session data:', debug.session.session_data);
            console.log('🔍 Auth0 authenticated:', debug.auth0.is_authenticated);
            console.log('🔍 Current user ID:', debug.auth0.current_user_id);
            console.log('🔍 Actions AJAX:', debug.ajax_actions);
            
            // Afficher les problèmes détectés
            let problems = [];
            
            if (!debug.auth0.is_authenticated) {
                problems.push('❌ Utilisateur NON authentifié');
            }
            
            if (!debug.session.session_id) {
                problems.push('❌ Pas de session active');
            }
            
            if (Object.keys(debug.session.session_data).length === 0) {
                problems.push('❌ Session vide');
            }
            
            if (debug.ajax_actions.pooltracker_get_profile !== 'ENREGISTRÉE') {
                problems.push('❌ Action pooltracker_get_profile non enregistrée');
            }
            
            if (problems.length > 0) {
                console.log('🚨 PROBLÈMES DÉTECTÉS:');
                problems.forEach(p => console.log(p));
            } else {
                console.log('✅ Aucun problème majeur détecté');
            }
        }
    }
).fail(function(xhr, status, error) {
    console.log('❌ Debug système échoué:', xhr.status, xhr.responseText);
});

// Test 3: Test simple nonce
console.log('=== TEST 3: Test nonce ===');
jQuery.post(
    window.poolTracker ? window.poolTracker.ajax_url : '/wp-admin/admin-ajax.php',
    {
        action: 'pooltracker_test',
        nonce: window.poolTracker ? window.poolTracker.nonce : 'test'
    },
    function(response) {
        console.log('✅ Test nonce réussi:', response);
    }
).fail(function(xhr, status, error) {
    console.log('❌ Test nonce échoué:', xhr.status, xhr.responseText);
});

// Test 4: Le test qui pose problème
console.log('=== TEST 4: pooltracker_get_profile ===');
jQuery.post(
    window.poolTracker ? window.poolTracker.ajax_url : '/wp-admin/admin-ajax.php',
    {
        action: 'pooltracker_get_profile',
        nonce: window.poolTracker ? window.poolTracker.nonce : 'test'
    },
    function(response) {
        console.log('✅ pooltracker_get_profile réussi:', response);
    }
).fail(function(xhr, status, error) {
    console.log('❌ pooltracker_get_profile échoué:', xhr.status, xhr.responseText);
    
    try {
        const errorData = JSON.parse(xhr.responseText);
        console.log('Détails erreur:', errorData);
    } catch(e) {
        console.log('Réponse brute:', xhr.responseText);
    }
});
</script>

</body>
</html>