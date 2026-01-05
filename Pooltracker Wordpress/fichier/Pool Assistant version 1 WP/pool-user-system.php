<?php
/**
 * Pool User System - Extension PoolTracker avec Auth0
 * Version: 2.5.0 - VERSION COMPLÈTE avec authentification corrigée
 * Fusion de l'interface complète + redirection intelligente sans boucles
 */

if (!defined('ABSPATH')) exit;

class PoolUserSystemAuth0 {
    
    private $table_users;
    private $table_measurements;
    private $table_conversations;
    private $table_alerts;
    private $table_maintenance;
    private $table_goals;
    private $table_auth0_users;
    
    public function __construct() {
        global $wpdb;
        
        // Noms des tables
        $this->table_users = $wpdb->prefix . 'pool_users';
        $this->table_measurements = $wpdb->prefix . 'pool_measurements';
        $this->table_conversations = $wpdb->prefix . 'pool_user_conversations';
        $this->table_alerts = $wpdb->prefix . 'pool_user_alerts';
        $this->table_maintenance = $wpdb->prefix . 'pool_maintenance_log';
        $this->table_goals = $wpdb->prefix . 'pool_user_goals';
        $this->table_auth0_users = $wpdb->prefix . 'pool_auth0_users';
        
        // Hooks WordPress
        add_action('init', array($this, 'init_user_system'));
        add_action('wp_ajax_pool_save_measurement', array($this, 'save_measurement'));
        add_action('wp_ajax_pool_get_user_data', array($this, 'get_user_dashboard_data'));
        add_action('wp_ajax_pool_update_profile', array($this, 'update_user_profile'));
        add_action('wp_ajax_pool_get_chart_data', array($this, 'get_chart_data'));
        add_action('wp_ajax_pool_get_ai_advice', array($this, 'get_personalized_ai_advice'));
        add_action('wp_ajax_pool_mark_alert_read', array($this, 'mark_alert_read'));
        
        // HOOKS AUTH0 CORRIGÉS
        add_action('wp_ajax_pool_auth0_callback', array($this, 'handle_auth0_callback'));
        add_action('wp_ajax_nopriv_pool_auth0_callback', array($this, 'handle_auth0_callback'));
        add_action('wp_ajax_pool_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_pool_get_auth_status', array($this, 'get_auth_status'));
        add_action('wp_ajax_nopriv_pool_get_auth_status', array($this, 'get_auth_status'));
        
        // Gestion des tests
        add_action('wp_ajax_pool_get_user_tests', array($this, 'get_user_tests_paginated'));
        add_action('wp_ajax_pool_update_measurement', array($this, 'update_measurement'));
        add_action('wp_ajax_pool_delete_measurement', array($this, 'delete_measurement'));
        add_action('wp_ajax_pool_export_tests', array($this, 'export_tests_csv'));
        
        // Hook pour enrichir l'IA avec contexte utilisateur
        add_filter('pool_ai_context', array($this, 'add_user_context'), 10, 2);
        
        // 🎯 SHORTCODES AVEC REDIRECTION INTELLIGENTE CORRIGÉE
        add_shortcode('pooltracker_main', array($this, 'render_main_page')); // Pour /espace-client/
        add_shortcode('pooltracker_login', array($this, 'render_login_page')); // Pour /connexion/ (cachée)
        add_shortcode('pooltracker_debug', array($this, 'render_debug_page')); // Pour debug session
        
        // DEBUG SESSION ENDPOINT
        add_action('wp_ajax_pool_debug_session', array($this, 'debug_session_status'));
        add_action('wp_ajax_nopriv_pool_debug_session', array($this, 'debug_session_status'));
        
        // Admin
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Session management
        add_action('init', array($this, 'start_session'));
    }
    
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }
    
    public function init_user_system() {
        $this->maybe_create_missing_tables();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_pooltracker_assets'));
    }
    
    public function enqueue_pooltracker_assets() {
        if (is_page('connexion') || is_page('espace-client') || is_page('debug-pooltracker') || get_query_var('pooltracker')) {
            wp_enqueue_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1', true);
            
            wp_localize_script('chart-js', 'poolTracker', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pooltracker_nonce'),
                'auth0_domain' => get_option('pooltracker_auth0_domain', ''),
                'auth0_client_id' => get_option('pooltracker_auth0_client_id', ''),
                'login_url' => home_url('/connexion/'),
                'dashboard_url' => home_url('/espace-client/'),
                'is_logged_in' => $this->is_user_authenticated()
            ));
        }
    }
    
    // =====================================
    // AUTHENTIFICATION CORRIGÉE (SESSION PHP)
    // =====================================
    
    public function is_user_authenticated() {
        error_log('🔍 PoolTracker: === VÉRIFICATION AUTHENTIFICATION ===');
        error_log('🔍 PoolTracker: Session status: ' . session_status());
        error_log('🔍 PoolTracker: Session ID: ' . (session_id() ?: 'AUCUN'));
        
        // Vérifier le statut de session
        if (session_status() === PHP_SESSION_NONE) {
            error_log('⚠️ PoolTracker: Session non démarrée, tentative de démarrage...');
            if (session_start()) {
                error_log('✅ PoolTracker: Session démarrée');
            } else {
                error_log('❌ PoolTracker: Impossible de démarrer la session');
                return false;
            }
        }
        
        // Vérifier la présence de l'ID utilisateur
        if (!isset($_SESSION['pooltracker_user_id']) || empty($_SESSION['pooltracker_user_id'])) {
            error_log('❌ PoolTracker: Pas d\'ID utilisateur en session');
            error_log('🔍 PoolTracker: Contenu $_SESSION: ' . print_r($_SESSION, true));
            return false;
        }
        
        $user_id = intval($_SESSION['pooltracker_user_id']);
        error_log('✅ PoolTracker: ID utilisateur trouvé en session: ' . $user_id);
        
        // Vérifier l'expiration de session si configurée
        if (isset($_SESSION['pooltracker_login_time'])) {
            $login_time = $_SESSION['pooltracker_login_time'];
            $session_duration = 24 * 60 * 60; // 24 heures
            $elapsed = time() - $login_time;
            
            error_log('🕐 PoolTracker: Login time: ' . date('Y-m-d H:i:s', $login_time));
            error_log('🕐 PoolTracker: Elapsed: ' . $elapsed . 's (max: ' . $session_duration . 's)');
            
            if ($elapsed > $session_duration) {
                error_log('❌ PoolTracker: Session expirée, nettoyage...');
                $this->clear_user_session();
                return false;
            } else {
                error_log('✅ PoolTracker: Session non expirée');
            }
        }
        
        // Vérification supplémentaire : l'utilisateur existe-t-il en base ?
        global $wpdb;
        $user_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_auth0_users} WHERE pooltracker_user_id = %d AND is_active = 1",
            $user_id
        ));
        
        if (!$user_exists) {
            error_log('❌ PoolTracker: Utilisateur inexistant en base ou inactif, nettoyage session...');
            $this->clear_user_session();
            return false;
        }
        
        error_log('✅ PoolTracker: AUTHENTIFICATION VALIDÉE pour user ID: ' . $user_id);
        return true;
    }
    
    public function get_current_pooltracker_user_id() {
        return $this->is_user_authenticated() ? intval($_SESSION['pooltracker_user_id']) : false;
    }
    
    public function get_current_user_info() {
        if (!$this->is_user_authenticated()) {
            return null;
        }
        
        global $wpdb;
        $user_id = $this->get_current_pooltracker_user_id();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_auth0_users} WHERE pooltracker_user_id = %d",
            $user_id
        ));
    }
    
    private function set_user_session($user_id, $user_data) {
        error_log('🎯 PoolTracker: === DÉFINITION SESSION ===');
        error_log('🎯 PoolTracker: User ID: ' . $user_id);
        error_log('🎯 PoolTracker: Session ID avant: ' . (session_id() ?: 'AUCUN'));
        error_log('🎯 PoolTracker: Session status: ' . session_status());
        
        // S'assurer que la session est démarrée
        if (session_status() === PHP_SESSION_NONE) {
            error_log('🔄 PoolTracker: Démarrage session...');
            if (session_start()) {
                error_log('✅ PoolTracker: Session démarrée avec ID: ' . session_id());
            } else {
                error_log('❌ PoolTracker: Échec démarrage session');
                return false;
            }
        } else {
            error_log('✅ PoolTracker: Session déjà active avec ID: ' . session_id());
        }
        
        // Nettoyer d'abord les données existantes
        if (isset($_SESSION['pooltracker_user_id'])) {
            error_log('🧹 PoolTracker: Nettoyage session existante pour user: ' . $_SESSION['pooltracker_user_id']);
        }
        unset($_SESSION['pooltracker_user_id']);
        unset($_SESSION['pooltracker_user_data']);
        unset($_SESSION['pooltracker_login_time']);
        
        // Définir les nouvelles données
        $_SESSION['pooltracker_user_id'] = intval($user_id);
        $_SESSION['pooltracker_user_data'] = $user_data;
        $_SESSION['pooltracker_login_time'] = time();
        
        error_log('💾 PoolTracker: Données session définies:');
        error_log('   - User ID: ' . $_SESSION['pooltracker_user_id']);
        error_log('   - User data keys: ' . implode(', ', array_keys($_SESSION['pooltracker_user_data'])));
        error_log('   - Login time: ' . date('Y-m-d H:i:s', $_SESSION['pooltracker_login_time']));
        
        // Test immédiat de lecture
        $test_user_id = $_SESSION['pooltracker_user_id'] ?? 'ABSENT';
        $test_login_time = $_SESSION['pooltracker_login_time'] ?? 'ABSENT';
        
        error_log('🧪 PoolTracker: Test lecture immédiate:');
        error_log('   - User ID lu: ' . $test_user_id);
        error_log('   - Login time lu: ' . $test_login_time);
        
        if ($test_user_id === intval($user_id)) {
            error_log('✅ PoolTracker: Session définie et vérifiée avec succès');
            return true;
        } else {
            error_log('❌ PoolTracker: Échec vérification session');
            error_log('   - Attendu: ' . intval($user_id));
            error_log('   - Reçu: ' . $test_user_id);
            return false;
        }
    }
    
    private function clear_user_session() {
        unset($_SESSION['pooltracker_user_id']);
        unset($_SESSION['pooltracker_user_data']);
        unset($_SESSION['pooltracker_login_time']);
        
        error_log("🧹 PoolTracker: Session nettoyée");
        return true;
    }
    
    // =====================================
    // 🎯 SHORTCODE PRINCIPAL AVEC REDIRECTION INTELLIGENTE
    // =====================================
    
    /**
     * Shortcode principal pour /espace-client/ (SEULE PAGE VISIBLE)
     * Redirige automatiquement selon l'état de connexion
     */
    public function render_main_page($atts) {
        error_log('🎯 PoolTracker: render_main_page appelé sur /espace-client/');
        
        // Détecter si on arrive d'Auth0 (avec hash dans l'URL)
        $is_auth0_callback = isset($_GET['auth0_callback']) || 
                           (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'auth0.com') !== false);
        
        // Détecter aussi si on vient juste de se connecter via sessionStorage
        $has_callback_hash = strpos($_SERVER['REQUEST_URI'], '#') !== false;
        
        error_log('🔍 PoolTracker: Auth0 callback détecté: ' . ($is_auth0_callback ? 'OUI' : 'NON'));
        error_log('🔍 PoolTracker: Has callback hash: ' . ($has_callback_hash ? 'OUI' : 'NON'));
        error_log('🔍 PoolTracker: User authenticated: ' . ($this->is_user_authenticated() ? 'OUI' : 'NON'));
        error_log('🔍 PoolTracker: Request URI: ' . $_SERVER['REQUEST_URI']);
        error_log('🔍 PoolTracker: HTTP Referer: ' . ($_SERVER['HTTP_REFERER'] ?? 'AUCUN'));
        
        // Si c'est un callback Auth0 OU si on détecte un hash, traiter côté client
        if ($is_auth0_callback || $has_callback_hash) {
            error_log('🔄 PoolTracker: Traitement callback Auth0...');
            return $this->render_auth0_callback_handler();
        }
        
        // Si utilisateur connecté, afficher le dashboard COMPLET
        if ($this->is_user_authenticated()) {
            error_log('✅ PoolTracker: Utilisateur connecté, affichage interface complète');
            return $this->render_pooltracker_interface_complete();
        }
        
        // Si pas connecté, redirection vers /connexion/
        error_log('🔄 PoolTracker: Utilisateur non connecté, redirection vers /connexion/');
        return $this->render_redirect_to_login();
    }
    
    /**
     * Shortcode pour la page /connexion/ (PAGE CACHÉE)
     */
    public function render_login_page($atts) {
        error_log('🔑 PoolTracker: render_login_page appelé sur /connexion/');
        
        // Si déjà connecté, rediriger vers /espace-client/
        if ($this->is_user_authenticated()) {
            error_log('✅ PoolTracker: Utilisateur déjà connecté, redirection vers /espace-client/');
            return $this->render_redirect_to_dashboard();
        }
        
        // Sinon afficher la page de connexion
        return $this->render_auth0_login_form();
    }
    
    // =====================================
    // HANDLERS DE REDIRECTION
    // =====================================
    
    private function render_redirect_to_login() {
        ob_start();
        ?>
        <div style="text-align: center; padding: 40px;">
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto;">
                <h3 style="color: #3AA6B9; margin-bottom: 15px;">🔐 Accès à votre espace client</h3>
                <p style="color: #666; margin-bottom: 20px;">Redirection vers la page de connexion...</p>
                <div class="spinner" style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-left: 4px solid #3AA6B9; border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto;"></div>
            </div>
        </div>
        
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script>
        console.log('🔄 PoolTracker: Redirection automatique vers /connexion/');
        
        // Redirection immédiate
        setTimeout(function() {
            window.location.href = '/connexion/';
        }, 500);
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function render_redirect_to_dashboard() {
        ob_start();
        ?>
        <div style="text-align: center; padding: 40px;">
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto;">
                <h3 style="color: #3AA6B9; margin-bottom: 15px;">✅ Déjà connecté</h3>
                <p style="color: #666; margin-bottom: 20px;">Redirection vers votre espace client...</p>
                <div class="spinner" style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-left: 4px solid #3AA6B9; border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto;"></div>
            </div>
        </div>
        
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script>
        console.log('🔄 PoolTracker: Redirection automatique vers /espace-client/');
        
        // Redirection immédiate
        setTimeout(function() {
            window.location.href = '/espace-client/';
        }, 500);
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function render_auth0_callback_handler() {
        ob_start();
        ?>
        <div style="text-align: center; padding: 40px;">
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto;">
                <h3 style="color: #3AA6B9; margin-bottom: 15px;">🔄 Connexion en cours</h3>
                <p style="color: #666; margin-bottom: 20px;">Traitement de votre authentification...</p>
                <div class="spinner" style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-left: 4px solid #3AA6B9; border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto;"></div>
                
                <!-- DEBUG CONSOLE -->
                <div id="callback-debug" style="margin-top: 20px; padding: 15px; background: #2c3e50; color: #ecf0f1; border-radius: 8px; font-size: 12px; font-family: monospace; max-height: 200px; overflow-y: auto;">
                    <div style="color: #3498db; font-weight: bold; margin-bottom: 10px;">🔍 Debug Callback Auth0:</div>
                    <div id="callback-logs"></div>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script src="https://cdn.auth0.com/js/auth0/9.19.0/auth0.min.js"></script>
        <script>
        // Fonction debug pour callback
        function callbackLog(message, type = 'info') {
            var debugLogs = document.getElementById('callback-logs');
            if (!debugLogs) return;
            
            var timestamp = new Date().toLocaleTimeString();
            var color = type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db';
            
            debugLogs.innerHTML += '<div style="color: ' + color + '; margin: 2px 0;">[' + timestamp + '] ' + message + '</div>';
            debugLogs.scrollTop = debugLogs.scrollHeight;
            
            // Log aussi dans la console
            console.log('[PoolTracker Callback] ' + message);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            callbackLog('🔄 Début traitement callback Auth0 sur /espace-client/', 'info');
            
            // Vérifications préliminaires
            if (typeof auth0 === 'undefined') {
                callbackLog('❌ Auth0 SDK non chargé', 'error');
                alert('Erreur: Auth0 SDK non chargé');
                window.location.href = '/connexion/';
                return;
            }
            
            if (typeof poolTracker === 'undefined') {
                callbackLog('❌ poolTracker non défini', 'error');
                alert('Erreur: poolTracker non défini');
                window.location.href = '/connexion/';
                return;
            }
            
            callbackLog('✅ Auth0 SDK et poolTracker disponibles', 'success');
            callbackLog('Domain: <?php echo esc_js(get_option('pooltracker_auth0_domain', '')); ?>', 'info');
            callbackLog('Redirect URI: ' + window.location.origin + '/espace-client/', 'info');
            
            try {
                var auth0Client = new auth0.WebAuth({
                    domain: '<?php echo esc_js(get_option('pooltracker_auth0_domain', '')); ?>',
                    clientID: '<?php echo esc_js(get_option('pooltracker_auth0_client_id', '')); ?>',
                    redirectUri: window.location.origin + '/espace-client/',
                    responseType: 'token id_token',
                    scope: 'openid profile email'
                });
                
                callbackLog('✅ Auth0 Client initialisé', 'success');
                
                // Vérifier si on a un hash
                var currentHash = window.location.hash;
                callbackLog('Hash actuel: ' + (currentHash || 'AUCUN'), 'info');
                
                if (currentHash && currentHash.length > 1) {
                    callbackLog('🔍 Hash Auth0 détecté: ' + currentHash.substring(0, 100) + '...', 'info');
                    
                    // Nettoyer l'URL immédiatement pour éviter les problèmes
                    var hashToProcess = currentHash;
                    window.history.replaceState({}, document.title, '/espace-client/');
                    callbackLog('🧹 URL nettoyée, hash sauvegardé pour traitement', 'info');
                    
                    // Traiter le hash
                    auth0Client.parseHash({ hash: hashToProcess }, function(err, authResult) {
                        if (authResult && authResult.accessToken && authResult.idToken) {
                            callbackLog('✅ Tokens Auth0 reçus avec succès', 'success');
                            callbackLog('Access Token: ' + authResult.accessToken.substring(0, 20) + '...', 'info');
                            callbackLog('ID Token: ' + authResult.idToken.substring(0, 20) + '...', 'info');
                            
                            // Marquer qu'on est en train de traiter la connexion
                            sessionStorage.setItem('pooltracker_processing_auth', 'true');
                            
                            // Envoyer au serveur
                            callbackLog('📤 Envoi des tokens au serveur...', 'info');
                            
                            var requestData = {
                                'action': 'pool_auth0_callback',
                                '_wpnonce': poolTracker.nonce,
                                'access_token': authResult.accessToken,
                                'id_token': authResult.idToken
                            };
                            
                            callbackLog('Données à envoyer: ' + JSON.stringify({action: requestData.action, nonce_length: requestData._wpnonce.length}), 'info');
                            
                            fetch(poolTracker.ajax_url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: new URLSearchParams(requestData)
                            })
                            .then(function(response) {
                                callbackLog('📥 Réponse serveur reçue (Status: ' + response.status + ')', 'info');
                                
                                if (!response.ok) {
                                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                                }
                                
                                return response.text();
                            })
                            .then(function(responseText) {
                                callbackLog('📄 Contenu réponse brut: ' + responseText.substring(0, 200) + '...', 'info');
                                
                                var data;
                                try {
                                    data = JSON.parse(responseText);
                                } catch (parseError) {
                                    callbackLog('❌ Erreur parsing JSON: ' + parseError.message, 'error');
                                    callbackLog('Réponse brute: ' + responseText, 'error');
                                    throw new Error('Réponse serveur invalide: ' + parseError.message);
                                }
                                
                                callbackLog('📊 Données parsées: ' + JSON.stringify(data), 'info');
                                
                                if (data.success) {
                                    callbackLog('✅ Connexion réussie côté serveur !', 'success');
                                    callbackLog('User ID: ' + (data.data.user_id || 'N/A'), 'success');
                                    callbackLog('User Name: ' + (data.data.user_info ? data.data.user_info.name : 'N/A'), 'success');
                                    
                                    // Marquer que la connexion vient d'être effectuée
                                    sessionStorage.setItem('pooltracker_just_connected', 'true');
                                    sessionStorage.removeItem('pooltracker_processing_auth');
                                    
                                    // Stocker temporairement les infos utilisateur
                                    if (data.data.user_info) {
                                        sessionStorage.setItem('pooltracker_temp_user', JSON.stringify(data.data.user_info));
                                        callbackLog('💾 Infos utilisateur stockées temporairement', 'info');
                                    }
                                    
                                    callbackLog('🔄 Rechargement de la page...', 'info');
                                    
                                    // Attendre un peu puis recharger
                                    setTimeout(function() {
                                        callbackLog('🔄 RECHARGEMENT MAINTENANT', 'success');
                                        window.location.reload();
                                    }, 1000);
                                    
                                } else {
                                    callbackLog('❌ Erreur côté serveur: ' + (data.data || 'Erreur inconnue'), 'error');
                                    sessionStorage.removeItem('pooltracker_processing_auth');
                                    alert('❌ Erreur de connexion: ' + (data.data || 'Erreur inconnue'));
                                    
                                    setTimeout(function() {
                                        window.location.href = '/connexion/';
                                    }, 2000);
                                }
                            })
                            .catch(function(error) {
                                callbackLog('❌ Erreur réseau/serveur: ' + error.message, 'error');
                                sessionStorage.removeItem('pooltracker_processing_auth');
                                alert('❌ Erreur de communication: ' + error.message);
                                
                                setTimeout(function() {
                                    window.location.href = '/connexion/';
                                }, 2000);
                            });
                            
                        } else if (err) {
                            callbackLog('❌ Erreur Auth0 parseHash: ' + (err.error_description || err.error), 'error');
                            callbackLog('Erreur complète: ' + JSON.stringify(err), 'error');
                            sessionStorage.removeItem('pooltracker_processing_auth');
                            alert('❌ Erreur Auth0: ' + (err.error_description || err.error));
                            
                            setTimeout(function() {
                                window.location.href = '/connexion/';
                            }, 2000);
                        } else {
                            callbackLog('❌ Aucun résultat d\'authentification', 'error');
                            sessionStorage.removeItem('pooltracker_processing_auth');
                            
                            setTimeout(function() {
                                window.location.href = '/connexion/';
                            }, 2000);
                        }
                    });
                    
                } else {
                    callbackLog('🔍 Pas de hash Auth0 trouvé', 'info');
                    
                    // Vérifier si on vient de se connecter
                    var justConnected = sessionStorage.getItem('pooltracker_just_connected');
                    var processingAuth = sessionStorage.getItem('pooltracker_processing_auth');
                    
                    callbackLog('Just connected: ' + justConnected, 'info');
                    callbackLog('Processing auth: ' + processingAuth, 'info');
                    
                    if (justConnected === 'true') {
                        callbackLog('🎉 Connexion fraîche détectée, rechargement...', 'success');
                        sessionStorage.removeItem('pooltracker_just_connected');
                        
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                        
                    } else if (processingAuth === 'true') {
                        callbackLog('⏳ Traitement auth en cours, attente...', 'info');
                        
                        // Attendre et vérifier de nouveau
                        setTimeout(function() {
                            if (sessionStorage.getItem('pooltracker_processing_auth') === 'true') {
                                callbackLog('⚠️ Timeout traitement auth, redirection', 'error');
                                sessionStorage.removeItem('pooltracker_processing_auth');
                                window.location.href = '/connexion/';
                            }
                        }, 10000); // 10 secondes max
                        
                    } else {
                        callbackLog('🔄 Pas de connexion récente, redirection vers /connexion/', 'info');
                        
                        setTimeout(function() {
                            window.location.href = '/connexion/';
                        }, 2000);
                    }
                }
                
            } catch (error) {
                callbackLog('❌ Erreur critique traitement callback: ' + error.message, 'error');
                callbackLog('Stack trace: ' + error.stack, 'error');
                sessionStorage.removeItem('pooltracker_processing_auth');
                alert('❌ Erreur critique: ' + error.message);
                
                setTimeout(function() {
                    window.location.href = '/connexion/';
                }, 2000);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    // =====================================
    // CALLBACK AUTH0 ET DÉCONNEXION CORRIGÉS
    // =====================================
    
    public function handle_auth0_callback() {
        error_log('🔄 PoolTracker Auth0 Callback - Début détaillé');
        error_log('📡 Méthode: ' . $_SERVER['REQUEST_METHOD']);
        error_log('📡 Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Non défini'));
        error_log('📡 POST data: ' . print_r($_POST, true));
        error_log('📡 Headers: ' . print_r(getallheaders(), true));
        
        // Vérifier la méthode
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log('❌ PoolTracker: Méthode non POST - ' . $_SERVER['REQUEST_METHOD']);
            wp_send_json_error('Méthode non autorisée');
            return;
        }
        
        // Vérifier que les tables existent
        $this->maybe_create_missing_tables();
        
        // Vérifier les tokens
        if (!isset($_POST['access_token']) || !isset($_POST['id_token'])) {
            error_log('❌ PoolTracker: Tokens manquants');
            error_log('- access_token présent: ' . (isset($_POST['access_token']) ? 'OUI' : 'NON'));
            error_log('- id_token présent: ' . (isset($_POST['id_token']) ? 'OUI' : 'NON'));
            wp_send_json_error('Tokens manquants');
            return;
        }
        
        // Vérifier le nonce
        if (!isset($_POST['_wpnonce'])) {
            error_log('❌ PoolTracker: Nonce manquant');
            wp_send_json_error('Nonce manquant');
            return;
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            error_log('❌ PoolTracker: Nonce invalide');
            error_log('📍 Nonce reçu: ' . $_POST['_wpnonce']);
            error_log('📍 Nonce attendu: ' . wp_create_nonce('pooltracker_nonce'));
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        error_log('✅ PoolTracker: Vérifications préliminaires OK');
        
        // Extraire et valider les tokens
        $access_token = sanitize_text_field($_POST['access_token']);
        $id_token = sanitize_text_field($_POST['id_token']);
        
        error_log('🔑 PoolTracker: Access token length: ' . strlen($access_token));
        error_log('🔑 PoolTracker: ID token length: ' . strlen($id_token));
        error_log('🔑 PoolTracker: Access token début: ' . substr($access_token, 0, 20) . '...');
        error_log('🔑 PoolTracker: ID token début: ' . substr($id_token, 0, 20) . '...');
        
        // Vérifier si ce sont des tokens de test (sécurité)
        if ($access_token === 'fake_access_token_for_test' || $id_token === 'fake_id_token_for_test') {
            error_log('🧪 PoolTracker: Tokens de test détectés - refusés');
            wp_send_json_error('Tokens de test non autorisés');
            return;
        }
        
        // Décoder et valider le ID token
        error_log('🔍 PoolTracker: Validation du token en cours...');
        $user_data = $this->decode_jwt_token($id_token);
        
        if (!$user_data) {
            error_log('❌ PoolTracker: Validation token échouée');
            wp_send_json_error('Token invalide - vérifiez votre configuration Auth0');
            return;
        }
        
        error_log('✅ PoolTracker: Token validé avec succès');
        error_log('👤 PoolTracker: Données utilisateur extraites:');
        error_log('   - Sub: ' . ($user_data['sub'] ?? 'N/A'));
        error_log('   - Email: ' . ($user_data['email'] ?? 'N/A'));
        error_log('   - Name: ' . ($user_data['name'] ?? 'N/A'));
        error_log('   - Picture: ' . ($user_data['picture'] ?? 'N/A'));
        
        // Créer ou mettre à jour l'utilisateur
        error_log('👤 PoolTracker: Création/MAJ utilisateur...');
        $pooltracker_user_id = $this->create_or_update_auth0_user($user_data);
        
        if ($pooltracker_user_id) {
            // Définir la session
            error_log('🎯 PoolTracker: Définition session pour user ID: ' . $pooltracker_user_id);
            $session_result = $this->set_user_session($pooltracker_user_id, $user_data);
            
            if ($session_result) {
                error_log('✅ PoolTracker: Session définie avec succès');
                
                // Test immédiat de la session
                $immediate_test = $this->is_user_authenticated();
                error_log('🧪 PoolTracker: Test immédiat session: ' . ($immediate_test ? 'OK' : 'KO'));
                
                if ($immediate_test) {
                    error_log('🎉 PoolTracker: Connexion COMPLÈTEMENT réussie pour utilisateur ID: ' . $pooltracker_user_id);
                    
                    $response_data = array(
                        'message' => 'Connexion réussie',
                        'user_id' => $pooltracker_user_id,
                        'user_info' => array(
                            'name' => $user_data['name'] ?? '',
                            'email' => $user_data['email'] ?? '',
                            'picture' => $user_data['picture'] ?? '',
                            'provider' => $this->determine_provider_from_sub($user_data['sub'])
                        ),
                        'session_test' => $immediate_test,
                        'debug' => array(
                            'session_id' => session_id(),
                            'session_data_count' => count($_SESSION),
                            'user_id_in_session' => $_SESSION['pooltracker_user_id'] ?? 'ABSENT',
                            'timestamp' => current_time('mysql')
                        )
                    );
                    
                    error_log('📤 PoolTracker: Réponse de succès envoyée: ' . print_r($response_data, true));
                    wp_send_json_success($response_data);
                    
                } else {
                    error_log('❌ PoolTracker: Session définie mais test immédiat échoué');
                    wp_send_json_error('Erreur: session non persistante');
                }
                
            } else {
                error_log('❌ PoolTracker: Échec définition session');
                wp_send_json_error('Erreur définition session');
            }
            
        } else {
            error_log('❌ PoolTracker: Erreur création/MAJ utilisateur');
            wp_send_json_error('Erreur création utilisateur');
        }
    }
    
    public function handle_logout() {
        error_log('🚪 PoolTracker: Déconnexion demandée');
        
        $this->clear_user_session();
        
        if (session_id()) {
            session_destroy();
        }
        
        wp_send_json_success(array(
            'message' => 'Déconnexion réussie',
            'redirect_to' => '/connexion/'
        ));
    }
    
    public function get_auth_status() {
        $is_authenticated = $this->is_user_authenticated();
        $user_id = $this->get_current_pooltracker_user_id();
        $user_info = null;
        
        if ($is_authenticated && $user_id) {
            $user_info = $this->get_current_user_info();
        }
        
        wp_send_json_success(array(
            'authenticated' => $is_authenticated,
            'user_id' => $user_id,
            'user_info' => $user_info
        ));
    }
    
    // =====================================
    // PAGE DE CONNEXION
    // =====================================
    
    private function render_auth0_login_form() {
        $auth0_domain = get_option('pooltracker_auth0_domain', '');
        $auth0_client_id = get_option('pooltracker_auth0_client_id', '');
        
        if (empty($auth0_domain) || empty($auth0_client_id)) {
            return '<div style="padding: 40px; text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px;">
                <h3>⚙️ Configuration manquante</h3>
                <p>Les paramètres Auth0 ne sont pas configurés. Contactez l\'administrateur.</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div id="pooltracker-auth" style="max-width: 500px; margin: 50px auto; padding: 30px; background: #f8f9fa; border-radius: 15px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="margin-bottom: 20px;">
                <img src="https://poolassistant.fr/wp-content/uploads/2025/08/Coconut-Logo-6.png" alt="Pool Assistant" style="height: 80px; width: auto;">
            </div>
            
            <h2 id="auth-title" style="color: #3AA6B9; margin-bottom: 15px;">Connexion PoolTracker</h2>
            <p id="auth-subtitle" style="color: #666; margin-bottom: 25px;">Connectez-vous pour accéder à votre espace client personnalisé.</p>
            
            <!-- CONTENEUR PRINCIPAL -->
            <div id="auth-container">
                
                <!-- ONGLETS CONNEXION/INSCRIPTION -->
                <div id="auth-tabs" style="display: flex; margin-bottom: 25px; border-radius: 25px; background: #e9ecef; padding: 3px;">
                    <button id="login-tab" class="auth-tab active" style="flex: 1; padding: 10px; border: none; background: #3AA6B9; color: white; border-radius: 22px; font-weight: 500; cursor: pointer; transition: all 0.3s;">
                        Connexion
                    </button>
                    <button id="signup-tab" class="auth-tab" style="flex: 1; padding: 10px; border: none; background: transparent; color: #666; border-radius: 22px; font-weight: 500; cursor: pointer; transition: all 0.3s;">
                        Inscription
                    </button>
                </div>
                
                <!-- FORMULAIRE CONNEXION -->
                <div id="login-form" class="auth-form">
                    <form id="email-login-form" style="margin: 25px 0;">
                        <div style="margin-bottom: 20px;">
                            <label for="login-email" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; text-align: left;">📧 Email</label>
                            <input type="email" id="login-email" required 
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box;"
                                   placeholder="votre@email.com">
                        </div>
                        <div style="margin-bottom: 25px;">
                            <label for="login-password" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; text-align: left;">🔒 Mot de passe</label>
                            <input type="password" id="login-password" required
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box;"
                                   placeholder="Votre mot de passe">
                        </div>
                        <button type="submit" id="login-submit" 
                                style="width: 100%; background: linear-gradient(135deg, #3AA6B9, #2997AA); color: white; border: none; padding: 15px; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                            Se connecter
                        </button>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="#" id="forgot-password" style="color: #3AA6B9; text-decoration: none; font-size: 14px;">Mot de passe oublié ?</a>
                        </div>
                    </form>
                </div>
                
                <!-- FORMULAIRE INSCRIPTION -->
                <div id="signup-form" class="auth-form" style="display: none;">
                    <form id="email-signup-form" style="margin: 25px 0;">
                        <div style="margin-bottom: 20px;">
                            <label for="signup-email" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; text-align: left;">📧 Email</label>
                            <input type="email" id="signup-email" required 
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box;"
                                   placeholder="votre@email.com">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label for="signup-password" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; text-align: left;">🔒 Mot de passe</label>
                            <input type="password" id="signup-password" required
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box;"
                                   placeholder="Minimum 8 caractères" minlength="8">
                        </div>
                        <div style="margin-bottom: 25px;">
                            <label for="signup-password-confirm" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; text-align: left;">🔒 Confirmer le mot de passe</label>
                            <input type="password" id="signup-password-confirm" required
                                   style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box;"
                                   placeholder="Répétez votre mot de passe">
                        </div>
                        <button type="submit" id="signup-submit" 
                                style="width: 100%; background: linear-gradient(135deg, #27AE60, #2ECC71); color: white; border: none; padding: 15px; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                            Créer mon compte
                        </button>
                    </form>
                </div>
                
                <!-- SÉPARATEUR -->
                <div style="text-align: center; margin: 30px 0; color: #999; position: relative;">
                    <span style="background: #f8f9fa; padding: 0 15px; font-size: 14px;">━━━ Ou continuer avec ━━━</span>
                </div>
                
                <!-- BOUTONS SOCIAUX -->
                <div style="display: flex; flex-direction: column; gap: 12px; margin: 20px 0;">
                    <button id="google-login" style="background: #fff; color: #333; border: 1px solid #dadce0; padding: 15px 20px; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; font-size: 16px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" style="margin-right: 12px;">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continuer avec Google
                    </button>
                    
                    <button id="facebook-login" style="background: #1877f2; color: white; border: none; padding: 15px 20px; border-radius: 8px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; font-size: 16px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white" style="margin-right: 12px;">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Continuer avec Facebook
                    </button>
                </div>
                
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: rgba(58, 166, 185, 0.1); border-radius: 10px; font-size: 14px;">
                <strong>🎯 Avec PoolTracker :</strong><br>
                📊 Suivi graphique de vos mesures<br>
                🤖 Conseils IA personnalisés<br>
                🔔 Alertes automatiques<br>
                📝 Carnet d'entretien digital
            </div>
            
            <!-- DEBUG LIVE -->
            <div id="debug-console" style="margin-top: 20px; padding: 15px; background: #2c3e50; color: #ecf0f1; border-radius: 8px; font-size: 12px; font-family: monospace; max-height: 200px; overflow-y: auto; display: none;">
                <div style="color: #3498db; font-weight: bold; margin-bottom: 10px;">🔍 Console Debug Auth0:</div>
                <div id="debug-logs"></div>
                <div style="margin-top: 10px;">
                    <button onclick="toggleDebug()" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;">Masquer</button>
                    <button onclick="clearDebugLogs()" style="background: #f39c12; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; margin-left: 5px;">Clear</button>
                </div>
            </div>
            
            <!-- BOUTON DEBUG -->
            <div style="margin-top: 15px;">
                <button onclick="toggleDebug()" style="background: #95a5a6; color: white; border: none; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-size: 12px;">🔧 Debug Console</button>
            </div>
        </div>
        
        <style>
        .auth-tab.active {
            background: #3AA6B9 !important;
            color: white !important;
        }
        
        .auth-tab:not(.active) {
            background: transparent !important;
            color: #666 !important;
        }
        
        .auth-tab:hover:not(.active) {
            background: rgba(58, 166, 185, 0.1) !important;
            color: #3AA6B9 !important;
        }
        
        #login-submit:hover, #signup-submit:hover, #google-login:hover, #facebook-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        input:focus {
            border-color: #3AA6B9 !important;
        }
        </style>
        
        <script src="https://cdn.auth0.com/js/auth0/9.19.0/auth0.min.js"></script>
        <script>
        // Variables globales
        var auth0Client;
        var isSignupMode = false;
        
        // Fonctions debug
        function debugLog(message, type = 'info') {
            var debugLogs = document.getElementById('debug-logs');
            if (!debugLogs) return;
            
            var timestamp = new Date().toLocaleTimeString();
            var color = type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db';
            
            debugLogs.innerHTML += '<div style="color: ' + color + '; margin: 2px 0;">[' + timestamp + '] ' + message + '</div>';
            debugLogs.scrollTop = debugLogs.scrollHeight;
        }
        
        function toggleDebug() {
            var debug = document.getElementById('debug-console');
            debug.style.display = debug.style.display === 'none' ? 'block' : 'none';
        }
        
        function clearDebugLogs() {
            document.getElementById('debug-logs').innerHTML = '';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('🚀 PoolTracker Login Page - Initialisation', 'info');
            
            // Vérifications préliminaires
            if (typeof poolTracker === 'undefined') {
                debugLog('❌ poolTracker non défini', 'error');
                return;
            }
            
            if (typeof auth0 === 'undefined') {
                debugLog('❌ Auth0 SDK non chargé', 'error');
                return;
            }
            
            debugLog('✅ poolTracker et Auth0 SDK chargés', 'success');
            debugLog('Domain: <?php echo esc_js($auth0_domain); ?>', 'info');
            debugLog('Client ID: <?php echo esc_js($auth0_client_id); ?>', 'info');
            
            try {
                // Initialisation Auth0
                auth0Client = new auth0.WebAuth({
                    domain: '<?php echo esc_js($auth0_domain); ?>',
                    clientID: '<?php echo esc_js($auth0_client_id); ?>',
                    redirectUri: window.location.origin + '/espace-client/',
                    responseType: 'token id_token',
                    scope: 'openid profile email'
                });
                
                debugLog('✅ Auth0 Client initialisé', 'success');
                
                // Gestion des onglets
                setupAuthTabs();
                
                // Gestion des formulaires
                setupEmailForms();
                
                // Gestion des boutons sociaux
                setupSocialButtons();
                
            } catch (error) {
                debugLog('❌ Erreur initialisation: ' + error.message, 'error');
            }
        });
        
        function setupAuthTabs() {
            var loginTab = document.getElementById('login-tab');
            var signupTab = document.getElementById('signup-tab');
            var loginForm = document.getElementById('login-form');
            var signupForm = document.getElementById('signup-form');
            var title = document.getElementById('auth-title');
            var subtitle = document.getElementById('auth-subtitle');
            
            loginTab.addEventListener('click', function() {
                isSignupMode = false;
                loginTab.classList.add('active');
                signupTab.classList.remove('active');
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                title.textContent = 'Connexion PoolTracker';
                subtitle.textContent = 'Connectez-vous pour accéder à votre espace client personnalisé.';
                debugLog('🔄 Basculé vers mode Connexion', 'info');
            });
            
            signupTab.addEventListener('click', function() {
                isSignupMode = true;
                signupTab.classList.add('active');
                loginTab.classList.remove('active');
                signupForm.style.display = 'block';
                loginForm.style.display = 'none';
                title.textContent = 'Créer un compte PoolTracker';
                subtitle.textContent = 'Rejoignez PoolTracker et gérez votre piscine comme un pro !';
                debugLog('🔄 Basculé vers mode Inscription', 'info');
            });
        }
        
        function setupEmailForms() {
            // Formulaire de connexion
            document.getElementById('email-login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                var email = document.getElementById('login-email').value.trim();
                var password = document.getElementById('login-password').value;
                
                debugLog('🔐 Tentative de connexion pour: ' + email, 'info');
                
                if (!email || !password) {
                    alert('⚠️ Veuillez remplir tous les champs');
                    return;
                }
                
                var submitBtn = document.getElementById('login-submit');
                var originalText = submitBtn.textContent;
                submitBtn.textContent = 'Connexion...';
                submitBtn.disabled = true;
                
                auth0Client.login({
                    realm: 'Username-Password-Authentication',
                    username: email,
                    password: password
                }, function(err, authResult) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    if (err) {
                        debugLog('❌ Erreur connexion: ' + (err.description || err.error), 'error');
                        
                        var errorMsg = 'Erreur de connexion';
                        if (err.description && err.description.indexOf('Wrong email or password') !== -1) {
                            errorMsg = 'Email ou mot de passe incorrect';
                        } else if (err.description && err.description.indexOf('user does not exist') !== -1) {
                            errorMsg = 'Aucun compte trouvé avec cet email.\\nVoulez-vous créer un compte ?';
                            if (confirm(errorMsg)) {
                                // Basculer vers inscription
                                document.getElementById('signup-tab').click();
                                document.getElementById('signup-email').value = email;
                            }
                            return;
                        }
                        alert('❌ ' + errorMsg);
                    } else {
                        debugLog('✅ Connexion réussie', 'success');
                        // Redirection gérée par Auth0
                    }
                });
            });
            
            // Formulaire d'inscription
            document.getElementById('email-signup-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                var email = document.getElementById('signup-email').value.trim();
                var password = document.getElementById('signup-password').value;
                var passwordConfirm = document.getElementById('signup-password-confirm').value;
                
                debugLog('📝 Tentative d\'inscription pour: ' + email, 'info');
                
                if (!email || !password || !passwordConfirm) {
                    alert('⚠️ Veuillez remplir tous les champs');
                    return;
                }
                
                if (password !== passwordConfirm) {
                    alert('⚠️ Les mots de passe ne correspondent pas');
                    return;
                }
                
                if (password.length < 8) {
                    alert('⚠️ Le mot de passe doit contenir au moins 8 caractères');
                    return;
                }
                
                var submitBtn = document.getElementById('signup-submit');
                var originalText = submitBtn.textContent;
                submitBtn.textContent = 'Création...';
                submitBtn.disabled = true;
                
                auth0Client.signup({
                    connection: 'Username-Password-Authentication',
                    email: email,
                    password: password
                }, function(err, result) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    if (err) {
                        debugLog('❌ Erreur inscription: ' + (err.description || err.error), 'error');
                        
                        var errorMsg = 'Erreur lors de la création du compte';
                        if (err.description && err.description.indexOf('user exists') !== -1) {
                            errorMsg = 'Un compte existe déjà avec cet email.\\nVoulez-vous vous connecter ?';
                            if (confirm(errorMsg)) {
                                // Basculer vers connexion
                                document.getElementById('login-tab').click();
                                document.getElementById('login-email').value = email;
                            }
                            return;
                        }
                        alert('❌ ' + errorMsg);
                    } else {
                        debugLog('✅ Inscription réussie', 'success');
                        alert('✅ Compte créé avec succès !\\nVous pouvez maintenant vous connecter.');
                        
                        // Basculer vers connexion
                        document.getElementById('login-tab').click();
                        document.getElementById('login-email').value = email;
                    }
                });
            });
            
            // Mot de passe oublié
            document.getElementById('forgot-password').addEventListener('click', function(e) {
                e.preventDefault();
                var email = document.getElementById('login-email').value.trim();
                
                if (!email) {
                    alert('⚠️ Veuillez d\'abord entrer votre email');
                    document.getElementById('login-email').focus();
                    return;
                }
                
                debugLog('🔐 Demande de réinitialisation pour: ' + email, 'info');
                
                auth0Client.changePassword({
                    connection: 'Username-Password-Authentication',
                    email: email
                }, function(err, resp) {
                    if (err) {
                        debugLog('❌ Erreur réinitialisation: ' + (err.description || err.error), 'error');
                        alert('❌ Erreur: ' + (err.description || err.message));
                    } else {
                        debugLog('✅ Email de réinitialisation envoyé', 'success');
                        alert('✅ Un email de réinitialisation a été envoyé à ' + email);
                    }
                });
            });
        }
        
        function setupSocialButtons() {
            document.getElementById('google-login').addEventListener('click', function() {
                debugLog('🔵 Connexion Google demandée', 'info');
                this.innerHTML = '<span style="margin-right: 12px;">⏳</span>Connexion...';
                this.disabled = true;
                
                try {
                    auth0Client.authorize({ 
                        connection: 'google-oauth2',
                        prompt: 'select_account'
                    });
                } catch (error) {
                    debugLog('❌ Erreur lancement Google: ' + error.message, 'error');
                    this.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" style="margin-right: 12px;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>Continuer avec Google';
                    this.disabled = false;
                }
            });
            
            document.getElementById('facebook-login').addEventListener('click', function() {
                debugLog('🔵 Connexion Facebook demandée', 'info');
                this.innerHTML = '<span style="margin-right: 12px;">⏳</span>Connexion...';
                this.disabled = true;
                
                try {
                    auth0Client.authorize({ 
                        connection: 'facebook',
                        prompt: 'select_account'
                    });
                } catch (error) {
                    debugLog('❌ Erreur lancement Facebook: ' + error.message, 'error');
                    this.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="white" style="margin-right: 12px;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>Continuer avec Facebook';
                    this.disabled = false;
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    // =====================================
    // INTERFACE COMPLÈTE POOLTRACKER 
    // =====================================
    
    private function render_pooltracker_interface_complete() {
        $user_info = $this->get_current_user_info();
        $user_name = $user_info ? $user_info->name : 'Utilisateur';
        
        ob_start();
        ?>
        <!-- INTERFACE POOLTRACKER COMPLÈTE -->
        <div id="pooltracker-app">
            
            <!-- HEADER -->
            <div class="pooltracker-header">
                <div class="header-content">
                    <h1>Votre Espace PoolTracker</h1>
                    <div class="user-info">
                        Bonjour <strong><?php echo esc_html($user_name); ?></strong> !
                        <button id="logout-btn" class="logout-link">Déconnexion</button>
                    </div>
                </div>
            </div>
            
            <!-- NAVIGATION ONGLETS -->
            <nav class="pooltracker-nav">
                <button class="nav-tab active" data-tab="dashboard">📊 Tableau de bord</button>
                <button class="nav-tab" data-tab="measurement">📝 Nouveau test</button>
                <button class="nav-tab" data-tab="tests">📋 Mes tests</button>
                <button class="nav-tab" data-tab="charts">📈 Graphiques</button>
                <button class="nav-tab" data-tab="profile">⚙️ Ma piscine</button>
            </nav>
            
            <!-- CHARGEMENT -->
            <div id="pooltracker-loading" class="loading-container">
                <div class="spinner"></div>
                <p>Chargement de vos données...</p>
            </div>
            
            <!-- CONTENU ONGLETS -->
            <div class="pooltracker-content">
                
                <!-- ONGLET 1: DASHBOARD -->
                <div id="tab-dashboard" class="tab-content active">
                    <div class="dashboard-grid">
                        
                        <!-- Widget conseil IA -->
                        <div class="widget-card ai-advice">
                            <h3>🤖 Conseil Pool Assistant</h3>
                            <div id="daily-ai-advice">
                                <div class="ai-loading">💭 Génération de votre conseil personnalisé...</div>
                            </div>
                        </div>
                        
                        <!-- Widget statistiques rapides -->
                        <div class="widget-card quick-stats">
                            <h3>📊 Résumé rapide</h3>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-number" id="total-tests">-</div>
                                    <div class="stat-label">Tests réalisés</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="current-ph">-</div>
                                    <div class="stat-label">pH actuel</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="current-chlorine">-</div>
                                    <div class="stat-label">Chlore (mg/L)</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="days-since-test">-</div>
                                    <div class="stat-label">Jours depuis test</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Widget derniers tests -->
                        <div class="widget-card recent-tests">
                            <h3>📋 Derniers tests</h3>
                            <div id="recent-tests-list">
                                <div class="no-data">Aucun test enregistré</div>
                            </div>
                            <button class="btn-primary" onclick="switchTab('measurement')">
                                ➕ Nouveau test
                            </button>
                        </div>
                        
                        <!-- Widget alertes -->
                        <div class="widget-card alerts-widget">
                            <h3>🔔 Alertes</h3>
                            <div id="alerts-list">
                                <div class="no-alerts">✅ Aucune alerte</div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- ONGLET 2: NOUVEAU TEST -->
                <div id="tab-measurement" class="tab-content">
                    <div class="measurement-container">
                        <h2>📝 Nouveau test d'eau</h2>
                        
                        <form id="measurement-form" class="measurement-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="test-date">📅 Date du test</label>
                                    <input type="date" id="test-date" name="test_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="test-time">⏰ Heure</label>
                                    <input type="time" id="test-time" name="test_time">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ph-value">🧪 pH</label>
                                    <input type="number" id="ph-value" name="ph_value" 
                                           min="6.0" max="8.5" step="0.1" placeholder="Entrez votre valeur pH">
                                    <small>Valeur idéale : 7.0 - 7.4</small>
                                </div>
                                <div class="form-group">
                                    <label for="chlorine-value">💧 Chlore libre (mg/L)</label>
                                    <input type="number" id="chlorine-value" name="chlorine_mg_l" 
                                           min="0" max="5" step="0.1" placeholder="Entrez votre taux de chlore">
                                    <small>Valeur idéale : 0.5 - 2.0</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="temperature-value">🌡️ Température (°C)</label>
                                    <input type="number" id="temperature-value" name="temperature_c" 
                                           min="5" max="40" step="0.5" placeholder="Entrez la température">
                                </div>
                                <div class="form-group">
                                    <label for="weather-condition">☀️ Météo</label>
                                    <select id="weather-condition" name="weather_condition">
                                        <option value="">-- Optionnel --</option>
                                        <option value="soleil">☀️ Ensoleillé</option>
                                        <option value="nuageux">☁️ Nuageux</option>
                                        <option value="pluie">🌧️ Pluie</option>
                                        <option value="orage">⛈️ Orage</option>
                                        <option value="forte_chaleur">🔥 Forte chaleur</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="alkalinity">🔬 TAC (Optionnel)</label>
                                <input type="number" id="alkalinity" name="alkalinity" 
                                       min="50" max="300" placeholder="Entrez votre TAC si mesuré">
                                <small>Valeur recommandée : 80-120 ppm</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">📝 Notes</label>
                                <textarea id="notes" name="notes" rows="3" 
                                          placeholder="Observations, produits ajoutés..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn-primary btn-large" id="save-measurement">
                                💾 Enregistrer le test
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- ONGLET 3: MES TESTS -->
                <div id="tab-tests" class="tab-content">
                    <div class="tests-container">
                        <h2>📋 Gestion de mes tests</h2>
                        
                        <!-- Filtres et recherche -->
                        <div class="tests-filters">
                            <div class="filters-row">
                                <div class="filter-group">
                                    <input type="text" id="search-tests" placeholder="🔍 Rechercher dans mes tests...">
                                </div>
                                <div class="filter-group">
                                    <input type="date" id="date-from" placeholder="Du">
                                </div>
                                <div class="filter-group">
                                    <input type="date" id="date-to" placeholder="Au">
                                </div>
                                <div class="filter-group">
                                    <button id="filter-tests" class="btn-primary">Filtrer</button>
                                    <button id="reset-filters" class="btn-secondary">Reset</button>
                                </div>
                            </div>
                            
                            <div class="actions-row">
                                <div class="per-page-group">
                                    <select id="tests-per-page">
                                        <option value="10">10 par page</option>
                                        <option value="25">25 par page</option>
                                        <option value="50">50 par page</option>
                                    </select>
                                </div>
                                <div class="export-group">
                                    <button id="export-csv" class="btn-export">📥 Export CSV</button>
                                    <span id="tests-count">0 tests au total</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statistiques rapides -->
                        <div id="tests-stats" class="tests-stats">
                            <div class="stat-card">
                                <div class="stat-value" id="avg-ph">-</div>
                                <div class="stat-label">pH moyen</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="avg-chlorine">-</div>
                                <div class="stat-label">Chlore moyen</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="avg-temperature">-</div>
                                <div class="stat-label">Température moyenne</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="tests-period">-</div>
                                <div class="stat-label">Période</div>
                            </div>
                        </div>
                        
                        <!-- Loading -->
                        <div id="tests-loading" class="tests-loading">
                            <div class="spinner"></div>
                            <p>Chargement de vos tests...</p>
                        </div>
                        
                        <!-- Table des tests -->
                        <div id="tests-table-container" class="tests-table-container">
                            <table class="tests-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Heure</th>
                                        <th>pH</th>
                                        <th>Chlore</th>
                                        <th>Temp.</th>
                                        <th>TAC</th>
                                        <th>Météo</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tests-tbody">
                                    <!-- Rempli dynamiquement -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div id="tests-pagination" class="pagination">
                            <!-- Rempli dynamiquement -->
                        </div>
                        
                        <!-- Message si pas de tests -->
                        <div id="no-tests" class="no-tests" style="display: none;">
                            <h3>📝 Aucun test enregistré</h3>
                            <p>Commencez par ajouter votre premier test d'eau !</p>
                            <button class="btn-primary" onclick="switchTab('measurement')">
                                ➕ Ajouter un test
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- ONGLET 4: GRAPHIQUES -->
                <div id="tab-charts" class="tab-content">
                    <div class="charts-container">
                        <h2>📈 Évolution de votre piscine</h2>
                        
                        <div class="chart-controls">
                            <button class="period-btn active" data-period="7">7 jours</button>
                            <button class="period-btn" data-period="30">30 jours</button>
                            <button class="period-btn" data-period="90">90 jours</button>
                        </div>
                        
                        <div class="charts-grid">
                            <div class="chart-card">
                                <h3>pH</h3>
                                <canvas id="ph-chart" width="400" height="200"></canvas>
                            </div>
                            
                            <div class="chart-card">
                                <h3>Chlore (mg/L)</h3>
                                <canvas id="chlorine-chart" width="400" height="200"></canvas>
                            </div>
                            
                            <div class="chart-card full-width">
                                <h3>Température (°C)</h3>
                                <canvas id="temperature-chart" width="800" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ONGLET 5: PROFIL -->
                <div id="tab-profile" class="tab-content">
                    <div class="profile-container">
                        <h2>⚙️ Configuration de ma piscine</h2>
                        
                        <form id="profile-form" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pool-volume">💧 Volume d'eau (m³)</label>
                                    <input type="number" id="pool-volume" name="pool_volume" 
                                           min="5" max="200" step="0.5" placeholder="32">
                                </div>
                                <div class="form-group">
                                    <label for="pool-shape">🏊‍♂️ Forme</label>
                                    <select id="pool-shape" name="pool_shape">
                                        <option value="rectangulaire">Rectangulaire</option>
                                        <option value="ronde">Ronde</option>
                                        <option value="haricot">Haricot</option>
                                        <option value="libre">Forme libre</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="treatment-type">🧪 Type de traitement</label>
                                    <select id="treatment-type" name="pool_treatment_type">
                                        <option value="chlore">Chlore</option>
                                        <option value="brome">Brome</option>
                                        <option value="oxygene">Oxygène actif</option>
                                        <option value="sel">Électrolyse au sel</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filtration-type">🔄 Filtration</label>
                                    <select id="filtration-type" name="pool_filtration_type">
                                        <option value="sable">Sable</option>
                                        <option value="verre">Verre</option>
                                        <option value="cartouche">Cartouche</option>
                                        <option value="terre">Terre de diatomée</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pool-depth">📏 Profondeur moyenne (m)</label>
                                    <input type="number" id="pool-depth" name="pool_depth_avg" 
                                           min="0.5" max="3" step="0.1" placeholder="1.5">
                                </div>
                                <div class="form-group">
                                    <label for="filtration-hours">⏱️ Heures filtration/jour</label>
                                    <input type="number" id="filtration-hours" name="filtration_hours" 
                                           min="4" max="24" placeholder="8">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group checkbox-group">
                                    <label>
                                        <input type="checkbox" id="has-cover" name="has_cover" value="1">
                                        🛡️ Bâche / Volet roulant
                                    </label>
                                </div>
                                <div class="form-group checkbox-group">
                                    <label>
                                        <input type="checkbox" id="has-heat-pump" name="has_heat_pump" value="1">
                                        🔥 Pompe à chaleur
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary btn-large">
                                💾 Sauvegarder le profil
                            </button>
                        </form>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- STYLES CSS INTÉGRÉS COMPLETS -->
        <style>
        /* VARIABLES CSS */
        :root {
            --pool-primary: #3AA6B9;
            --pool-secondary: #2997AA;
            --pool-light: #E9F8F9;
            --pool-gradient: linear-gradient(135deg, #3AA6B9, #2997AA);
            --pool-shadow: 0 4px 15px rgba(58, 166, 185, 0.2);
            --pool-radius: 12px;
        }
        
        /* STYLES GÉNÉRAUX */
        #pooltracker-app {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .pooltracker-header {
            background: var(--pool-gradient);
            color: white;
            padding: 20px;
            border-radius: var(--pool-radius);
            margin-bottom: 20px;
            box-shadow: var(--pool-shadow);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-content h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .user-info {
            color: rgba(255,255,255,0.9);
        }
        
        .logout-link {
            color: white;
            text-decoration: none;
            margin-left: 10px;
            padding: 5px 10px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 15px;
            font-size: 12px;
            transition: background 0.3s;
            background: transparent;
            cursor: pointer;
        }
        
        .logout-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
        }
        
        /* NAVIGATION ONGLETS */
        .pooltracker-nav {
            display: flex;
            background: white;
            border-radius: var(--pool-radius);
            padding: 5px;
            margin-bottom: 20px;
            box-shadow: var(--pool-shadow);
            overflow-x: auto;
        }
        
        .nav-tab {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 15px;
            border-radius: calc(var(--pool-radius) - 3px);
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            font-size: 14px;
            min-width: 120px;
            color: var(--pool-primary);
            font-weight: 500;
        }
        
        .nav-tab:hover {
            background: var(--pool-light);
            color: var(--pool-secondary);
        }
        
        .nav-tab.active {
            background: var(--pool-gradient);
            color: white;
        }
        
        /* LOADING */
        .loading-container {
            text-align: center;
            padding: 50px;
            display: none;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--pool-light);
            border-left: 4px solid var(--pool-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* CONTENU ONGLETS */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* DASHBOARD GRID */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .widget-card {
            background: white;
            padding: 20px;
            border-radius: var(--pool-radius);
            box-shadow: var(--pool-shadow);
            border: 1px solid rgba(58, 166, 185, 0.1);
        }
        
        .widget-card h3 {
            margin: 0 0 15px 0;
            color: var(--pool-secondary);
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: var(--pool-light);
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--pool-primary);
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* FORMULAIRES */
        .measurement-form, .profile-form {
            background: white;
            padding: 30px;
            border-radius: var(--pool-radius);
            box-shadow: var(--pool-shadow);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--pool-secondary);
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: var(--pool-primary);
        }
        
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        
        /* BOUTONS */
        .btn-primary {
            background: var(--pool-gradient);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58, 166, 185, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .btn-large {
            padding: 15px 30px;
            font-size: 16px;
            width: 100%;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-export {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        /* GESTION DES TESTS */
        .tests-container {
            background: white;
            padding: 30px;
            border-radius: var(--pool-radius);
            box-shadow: var(--pool-shadow);
        }
        
        .tests-filters {
            background: var(--pool-light);
            padding: 20px;
            border-radius: var(--pool-radius);
            margin-bottom: 20px;
        }
        
        .filters-row, .actions-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .actions-row {
            justify-content: space-between;
            margin-bottom: 0;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        #search-tests {
            min-width: 250px;
        }
        
        .tests-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: var(--pool-radius);
            text-align: center;
            border: 1px solid rgba(58, 166, 185, 0.2);
        }
        
        .stat-card .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: var(--pool-primary);
            margin-bottom: 5px;
        }
        
        .stat-card .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .tests-loading {
            text-align: center;
            padding: 40px;
            display: none;
        }
        
        .tests-table-container {
            overflow-x: auto;
            border-radius: var(--pool-radius);
            border: 1px solid #e0e0e0;
        }
        
        .tests-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .tests-table th {
            background: var(--pool-gradient);
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            border: none;
        }
        
        .tests-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        
        .tests-table tr:hover {
            background: var(--pool-light);
        }
        
        .test-actions {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            background: transparent;
            border: 1px solid #ddd;
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .action-btn.delete {
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .action-btn.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .page-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .page-btn:hover, .page-btn.active {
            background: var(--pool-primary);
            color: white;
            border-color: var(--pool-primary);
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .no-tests {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-tests h3 {
            color: var(--pool-secondary);
            margin-bottom: 15px;
        }
        
        .charts-container {
            background: white;
            padding: 30px;
            border-radius: var(--pool-radius);
            box-shadow: var(--pool-shadow);
        }
        
        .chart-controls {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .period-btn {
            background: transparent;
            border: 2px solid var(--pool-primary);
            color: var(--pool-primary);
            padding: 8px 16px;
            margin: 0 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .period-btn:hover,
        .period-btn.active {
            background: var(--pool-primary);
            color: white;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .chart-card {
            padding: 20px;
            background: #fafafa;
            border-radius: var(--pool-radius);
            min-height: 300px;
            position: relative;
        }
        
        .chart-card.full-width {
            grid-column: 1 / -1;
        }
        
        .chart-card h3 {
            text-align: center;
            color: var(--pool-secondary);
            margin-bottom: 15px;
        }
        
        .chart-card canvas {
            max-width: 100% !important;
            height: 250px !important;
            width: 100% !important;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .chart-card {
                padding: 15px;
                min-height: 250px;
            }
            
            .chart-card canvas {
                height: 200px !important;
            }
            
            .charts-container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                text-align: center;
            }
            
            .header-content h1 {
                font-size: 20px;
                margin-bottom: 10px;
            }
            
            .pooltracker-nav {
                overflow-x: auto;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            
            .pooltracker-nav::-webkit-scrollbar {
                display: none;
            }
            
            .nav-tab {
                font-size: 12px;
                padding: 10px 12px;
                min-width: 100px;
            }
            
            .period-btn {
                font-size: 12px;
                padding: 6px 12px;
                margin: 0 3px;
            }
            
            .tests-container {
                padding: 20px;
            }
            
            .filters-row, .actions-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .filter-group {
                justify-content: stretch;
            }
            
            .filter-group input, .filter-group select {
                flex: 1;
            }
            
            #search-tests {
                min-width: auto;
            }
            
            .tests-stats {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .tests-table-container {
                font-size: 12px;
            }
            
            .tests-table th, .tests-table td {
                padding: 8px 4px;
            }
            
            .test-actions {
                flex-direction: column;
                gap: 3px;
            }
            
            .action-btn {
                font-size: 10px;
                padding: 4px 6px;
            }
            
            .pagination {
                gap: 5px;
            }
            
            .page-btn {
                padding: 6px 8px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 1024px) and (min-width: 769px) {
            .chart-card canvas {
                height: 220px !important;
            }
            
            .charts-container {
                padding: 25px;
            }
        }
        
        .no-data, .no-alerts {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 20px;
        }
        
        .ai-loading {
            color: var(--pool-primary);
            font-style: italic;
        }
        
        .recent-test-item {
            background: var(--pool-light);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .alert-item {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .alert-urgent {
            background: #fee;
            border-left: 4px solid #e74c3c;
        }
        
        .alert-warning {
            background: #fef9e7;
            border-left: 4px solid #f39c12;
        }
        </style>
        
        <!-- JAVASCRIPT ADAPTÉ AUTH0 avec Chart.js complet -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 PoolTracker v2.5.0 - Interface Complète avec Auth Corrigée');
            
            // Vérifier que poolTracker est disponible
            if (typeof poolTracker === 'undefined') {
                console.error('❌ poolTracker non défini');
                return;
            }
            
            // Variables globales
            var currentTab = 'dashboard';
            var poolData = {};
            var charts = {};
            var currentPeriod = 30;
            var testsData = {
                currentPage: 1,
                perPage: 10,
                total: 0,
                filters: {
                    search: '',
                    dateFrom: '',
                    dateTo: ''
                }
            };
            
            // Initialisation de l'app
            initApp();
            
            function initApp() {
                setupTabNavigation();
                setupLogoutButton();
                loadUserData();
                setupForms();
                setupCharts();
                setupTestsManagement();
                
                // Définir la date d'aujourd'hui par défaut
                var today = new Date().toISOString().split('T')[0];
                var now = new Date().toTimeString().split(' ')[0].substring(0,5);
                document.getElementById('test-date').value = today;
                document.getElementById('test-time').value = now;
            }
            
            // Gestion déconnexion
            function setupLogoutButton() {
                var logoutBtn = document.getElementById('logout-btn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                            this.textContent = 'Déconnexion...';
                            this.disabled = true;
                            
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
                                    window.location.href = data.data.redirect_to || '/connexion/';
                                } else {
                                    alert('Erreur de déconnexion');
                                    this.textContent = 'Déconnexion';
                                    this.disabled = false;
                                }
                            })
                            .catch(error => {
                                console.error('Erreur déconnexion:', error);
                                window.location.href = '/connexion/';
                            });
                        }
                    });
                }
            }
            
            // Navigation onglets
            function setupTabNavigation() {
                var tabs = document.querySelectorAll('.nav-tab');
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].addEventListener('click', function() {
                        var tabName = this.dataset.tab;
                        switchTab(tabName);
                    });
                }
            }
            
            window.switchTab = function(tabName) {
                // Mettre à jour navigation
                var allTabs = document.querySelectorAll('.nav-tab');
                for (var i = 0; i < allTabs.length; i++) {
                    allTabs[i].classList.remove('active');
                }
                document.querySelector('[data-tab="' + tabName + '"]').classList.add('active');
                
                // Mettre à jour contenu
                var allContent = document.querySelectorAll('.tab-content');
                for (var i = 0; i < allContent.length; i++) {
                    allContent[i].classList.remove('active');
                }
                document.getElementById('tab-' + tabName).classList.add('active');
                
                currentTab = tabName;
                
                // Actions spécifiques par onglet
                if (tabName === 'charts') {
                    setTimeout(function() {
                        loadChartData();
                    }, 100);
                } else if (tabName === 'profile') {
                    loadProfile();
                } else if (tabName === 'tests') {
                    loadUserTests();
                }
            }
            
            // Chargement des données utilisateur
            function loadUserData() {
                console.log('📊 Chargement des données utilisateur...');
                
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        'action': 'pool_get_user_data',
                        '_wpnonce': poolTracker.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var stats = data.data.stats;
                        document.getElementById('total-tests').textContent = stats.total_tests || '0';
                        document.getElementById('current-ph').textContent = stats.current_ph || '-';
                        document.getElementById('current-chlorine').textContent = stats.current_chlorine || '-';
                        
                        if (stats.last_test_date) {
                            var daysSince = Math.floor((new Date() - new Date(stats.last_test_date)) / (1000 * 60 * 60 * 24));
                            document.getElementById('days-since-test').textContent = daysSince;
                        } else {
                            document.getElementById('days-since-test').textContent = '-';
                        }
                        
                        // Afficher les derniers tests
                        displayRecentTests(data.data.recent_measurements);
                        
                        // Afficher les alertes
                        displayAlerts(data.data.alerts);
                        
                        // Charger conseil IA
                        loadAIAdvice();
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement données:', error);
                    // Affichage par défaut en cas d'erreur
                    document.getElementById('total-tests').textContent = '0';
                    document.getElementById('current-ph').textContent = '-';
                    document.getElementById('current-chlorine').textContent = '-';
                    document.getElementById('days-since-test').textContent = '-';
                    
                    document.getElementById('daily-ai-advice').innerHTML = '💡 Bienvenue dans PoolTracker ! Commencez par configurer votre piscine dans l\'onglet "Ma piscine", puis ajoutez votre premier test.';
                });
            }
            
            function displayRecentTests(tests) {
                var container = document.getElementById('recent-tests-list');
                if (!tests || tests.length === 0) {
                    container.innerHTML = '<div class="no-data">Aucun test enregistré</div>';
                    return;
                }
                
                var html = '';
                tests.forEach(function(test) {
                    html += '<div class="recent-test-item">';
                    html += '<strong>' + test.test_date + '</strong> - ';
                    html += 'pH: ' + (test.ph_value || '-') + ', ';
                    html += 'Cl: ' + (test.chlorine_mg_l || '-') + ' mg/L';
                    if (test.temperature_c) {
                        html += ', ' + test.temperature_c + '°C';
                    }
                    html += '</div>';
                });
                container.innerHTML = html;
            }
            
            function displayAlerts(alerts) {
                var container = document.getElementById('alerts-list');
                if (!alerts || alerts.length === 0) {
                    container.innerHTML = '<div class="no-alerts">✅ Aucune alerte</div>';
                    return;
                }
                
                var html = '';
                alerts.forEach(function(alert) {
                    var alertClass = alert.alert_category === 'urgent' ? 'alert-urgent' : 'alert-warning';
                    html += '<div class="alert-item ' + alertClass + '">';
                    html += '<strong>' + alert.alert_title + '</strong><br>';
                    html += '<small>' + alert.alert_message + '</small>';
                    html += '</div>';
                });
                container.innerHTML = html;
            }
            
            function loadAIAdvice() {
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        'action': 'pool_get_ai_advice',
                        '_wpnonce': poolTracker.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('daily-ai-advice').innerHTML = data.data.advice;
                    }
                })
                .catch(error => {
                    document.getElementById('daily-ai-advice').innerHTML = '💡 Conseil : Maintenez une routine de tests réguliers pour une piscine parfaite !';
                });
            }
            
            // Configuration des formulaires
            function setupForms() {
                // Formulaire de mesure
                var measurementForm = document.getElementById('measurement-form');
                if (measurementForm) {
                    measurementForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        saveMeasurement();
                    });
                }
                
                // Formulaire de profil
                var profileForm = document.getElementById('profile-form');
                if (profileForm) {
                    profileForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        saveProfile();
                    });
                }
            }
            
            function saveMeasurement() {
                var formData = new FormData(document.getElementById('measurement-form'));
                formData.append('action', 'pool_save_measurement');
                formData.append('_wpnonce', poolTracker.nonce);
                
                var submitBtn = document.getElementById('save-measurement');
                submitBtn.textContent = 'Enregistrement...';
                submitBtn.disabled = true;
                
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Test enregistré avec succès !');
                        document.getElementById('measurement-form').reset();
                        
                        // Recharger les données
                        loadUserData();
                        
                        // Retour au dashboard
                        switchTab('dashboard');
                    } else {
                        alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    alert('❌ Erreur de communication : ' + error.message);
                })
                .finally(() => {
                    submitBtn.textContent = '💾 Enregistrer le test';
                    submitBtn.disabled = false;
                });
            }
            
            function saveProfile() {
                var formData = new FormData(document.getElementById('profile-form'));
                formData.append('action', 'pool_update_profile');
                formData.append('_wpnonce', poolTracker.nonce);
                
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Profil mis à jour avec succès !');
                    } else {
                        alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    alert('❌ Erreur de communication : ' + error.message);
                });
            }
            
            // Configuration des graphiques
            function setupCharts() {
                var periodBtns = document.querySelectorAll('.period-btn');
                periodBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        document.querySelector('.period-btn.active').classList.remove('active');
                        this.classList.add('active');
                        currentPeriod = parseInt(this.dataset.period);
                        loadChartData();
                    });
                });
            }
            
            function loadChartData() {
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        'action': 'pool_get_chart_data',
                        '_wpnonce': poolTracker.nonce,
                        'days': currentPeriod
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.measurements) {
                        createCharts(data.data.measurements);
                    } else {
                        createEmptyCharts();
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement graphiques:', error);
                    createEmptyCharts();
                });
            }
            
            function createCharts(measurements) {
                // Préparer les données
                var labels = [];
                var phData = [];
                var chlorineData = [];
                var tempData = [];
                
                measurements.reverse().forEach(function(m) {
                    labels.push(m.test_date);
                    phData.push(m.ph_value || null);
                    chlorineData.push(m.chlorine_mg_l || null);
                    tempData.push(m.temperature_c || null);
                });
                
                // Graphique pH
                createChart('ph-chart', 'pH', labels, phData, '#3AA6B9');
                
                // Graphique Chlore
                createChart('chlorine-chart', 'Chlore (mg/L)', labels, chlorineData, '#27AE60');
                
                // Graphique Température
                createChart('temperature-chart', 'Température (°C)', labels, tempData, '#E74C3C');
            }
            
            function createEmptyCharts() {
                var emptyLabels = ['Pas de données'];
                var emptyData = [0];
                
                createChart('ph-chart', 'pH', emptyLabels, emptyData, '#3AA6B9');
                createChart('chlorine-chart', 'Chlore (mg/L)', emptyLabels, emptyData, '#27AE60');
                createChart('temperature-chart', 'Température (°C)', emptyLabels, emptyData, '#E74C3C');
            }
            
            function createChart(canvasId, label, labels, data, color) {
                var ctx = document.getElementById(canvasId);
                if (!ctx || typeof Chart === 'undefined') return;
                
                // Détruire le graphique existant
                if (charts[canvasId]) {
                    charts[canvasId].destroy();
                }
                
                charts[canvasId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: data,
                            borderColor: color,
                            backgroundColor: color + '20',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false
                            }
                        }
                    }
                });
            }
            
            function loadProfile() {
                // Placeholder pour le chargement du profil
                console.log('⚙️ Chargement profil...');
            }
            
            function setupTestsManagement() {
                // Configuration de la gestion des tests
                console.log('📋 Configuration gestion des tests...');
            }
            
            function loadUserTests() {
                console.log('📋 Chargement tests utilisateur...');
                
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        'action': 'pool_get_user_tests',
                        '_wpnonce': poolTracker.nonce,
                        'page': 1,
                        'per_page': 10
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.tests && data.data.tests.length > 0) {
                            displayTestsTable(data.data.tests);
                            document.getElementById('no-tests').style.display = 'none';
                            document.getElementById('tests-table-container').style.display = 'block';
                        } else {
                            document.getElementById('no-tests').style.display = 'block';
                            document.getElementById('tests-table-container').style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement tests:', error);
                    document.getElementById('no-tests').style.display = 'block';
                    document.getElementById('tests-table-container').style.display = 'none';
                });
            }
            
            function displayTestsTable(tests) {
                var tbody = document.getElementById('tests-tbody');
                var html = '';
                
                tests.forEach(function(test) {
                    html += '<tr>';
                    html += '<td>' + test.test_date + '</td>';
                    html += '<td>' + (test.test_time || '-') + '</td>';
                    html += '<td>' + (test.ph_value || '-') + '</td>';
                    html += '<td>' + (test.chlorine_mg_l || '-') + '</td>';
                    html += '<td>' + (test.temperature_c || '-') + '</td>';
                    html += '<td>' + (test.alkalinity || '-') + '</td>';
                    html += '<td>' + (test.weather_condition || '-') + '</td>';
                    html += '<td>';
                    html += '<div class="test-actions">';
                    html += '<button class="action-btn delete" onclick="deleteTest(' + test.id + ')">🗑️</button>';
                    html += '</div>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                tbody.innerHTML = html;
            }
            
            window.deleteTest = function(testId) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce test ?')) return;
                
                fetch(poolTracker.ajax_url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        'action': 'pool_delete_measurement',
                        '_wpnonce': poolTracker.nonce,
                        'measurement_id': testId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Test supprimé');
                        loadUserTests();
                        loadUserData(); // Recharger aussi le dashboard
                    } else {
                        alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    alert('❌ Erreur : ' + error.message);
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    // =====================================
    // MÉTHODES UTILITAIRES
    // =====================================
    
    // Méthode helper pour déterminer le provider depuis le sub
    private function determine_provider_from_sub($auth0_sub) {
        if (strpos($auth0_sub, 'google-oauth2') !== false) {
            return 'google';
        } elseif (strpos($auth0_sub, 'facebook') !== false) {
            return 'facebook';
        } elseif (strpos($auth0_sub, 'apple') !== false) {
            return 'apple';
        }
        return 'auth0';
    }
    
    private function decode_jwt_token($token) {
        error_log('🔍 PoolTracker: === DÉBUT DÉCODAGE JWT ===');
        error_log('🔍 PoolTracker: Token length: ' . strlen($token));
        error_log('🔍 PoolTracker: Token début: ' . substr($token, 0, 50) . '...');
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            error_log('❌ PoolTracker: Token format invalide - ' . count($parts) . ' parties au lieu de 3');
            return false;
        }
        
        try {
            // Décoder le header pour diagnostic
            $header_b64 = str_pad(strtr($parts[0], '-_', '+/'), strlen($parts[0]) % 4, '=', STR_PAD_RIGHT);
            $header = json_decode(base64_decode($header_b64), true);
            error_log('🔍 PoolTracker: Header JWT: ' . print_r($header, true));
            
            // Décoder le payload
            $payload_b64 = str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT);
            $payload = json_decode(base64_decode($payload_b64), true);
            
            if (!$payload) {
                error_log('❌ PoolTracker: Impossible de décoder le payload JWT');
                return false;
            }
            
            error_log('🔍 PoolTracker: Payload JWT décodé: ' . print_r($payload, true));
            
            // Validation des champs obligatoires
            if (!isset($payload['sub'])) {
                error_log('❌ PoolTracker: Champ "sub" manquant dans le token');
                return false;
            }
            
            error_log('✅ PoolTracker: Sub trouvé: ' . $payload['sub']);
            
            // Vérifier l'expiration si présente
            if (isset($payload['exp'])) {
                $exp_date = date('Y-m-d H:i:s', $payload['exp']);
                $now_date = date('Y-m-d H:i:s');
                error_log('🔍 PoolTracker: Token expire le: ' . $exp_date);
                error_log('🔍 PoolTracker: Maintenant: ' . $now_date);
                
                if ($payload['exp'] < time()) {
                    error_log('❌ PoolTracker: Token expiré');
                    return false;
                } else {
                    error_log('✅ PoolTracker: Token non expiré');
                }
            }
            
            // Vérifier l'audience si présente
            if (isset($payload['aud'])) {
                $client_id = get_option('pooltracker_auth0_client_id', '');
                error_log('🔍 PoolTracker: Audience reçue: ' . $payload['aud']);
                error_log('🔍 PoolTracker: Client ID attendu: ' . $client_id);
                
                if ($payload['aud'] !== $client_id) {
                    error_log('⚠️ PoolTracker: Audience incorrecte (accepté quand même pour debug)');
                    // Accepter quand même pour le développement
                } else {
                    error_log('✅ PoolTracker: Audience correcte');
                }
            }
            
            // Détecter le provider depuis le sub
            $provider = $this->determine_provider_from_sub($payload['sub']);
            error_log('🔍 PoolTracker: Provider détecté: ' . $provider);
            
            // Traitement de l'email
            if (!isset($payload['email'])) {
                error_log('⚠️ PoolTracker: Email manquant, génération...');
                
                if ($provider === 'google' && isset($payload['name'])) {
                    $payload['email'] = $payload['name'];
                    error_log('🔧 PoolTracker: Email Google généré depuis name: ' . $payload['email']);
                } else {
                    // Générer un email temporaire basé sur le sub
                    $payload['email'] = 'user_' . substr($payload['sub'], -8) . '@temp.poolassistant.fr';
                    error_log('🔧 PoolTracker: Email temporaire généré: ' . $payload['email']);
                }
            } else {
                error_log('✅ PoolTracker: Email présent: ' . $payload['email']);
            }
            
            // Traitement du nom
            if (!isset($payload['name'])) {
                error_log('⚠️ PoolTracker: Name manquant, génération...');
                
                if (isset($payload['email'])) {
                    $payload['name'] = explode('@', $payload['email'])[0];
                    error_log('🔧 PoolTracker: Name généré depuis email: ' . $payload['name']);
                } else {
                    $payload['name'] = 'Utilisateur ' . substr($payload['sub'], -6);
                    error_log('🔧 PoolTracker: Name généré depuis sub: ' . $payload['name']);
                }
            } else {
                error_log('✅ PoolTracker: Name présent: ' . $payload['name']);
            }
            
            // Ajouter des infos debug au payload
            $payload['_debug_provider'] = $provider;
            $payload['_debug_validation_time'] = time();
            
            error_log('✅ PoolTracker: === JWT VALIDÉ AVEC SUCCÈS ===');
            error_log('✅ PoolTracker: Provider: ' . $provider);
            error_log('✅ PoolTracker: Sub: ' . $payload['sub']);
            error_log('✅ PoolTracker: Email final: ' . $payload['email']);
            error_log('✅ PoolTracker: Name final: ' . $payload['name']);
            
            return $payload;
            
        } catch (Exception $e) {
            error_log('❌ PoolTracker: Erreur décodage JWT - ' . $e->getMessage());
            return false;
        }
    }
    
    private function create_or_update_auth0_user($user_data) {
        global $wpdb;
        
        $auth0_sub = $user_data['sub'];
        $email = $user_data['email'];
        $name = $user_data['name'];
        $picture = $user_data['picture'] ?? '';
        
        error_log('👤 PoolTracker: === CRÉATION/MAJ UTILISATEUR ===');
        error_log('👤 PoolTracker: Sub: ' . $auth0_sub);
        error_log('👤 PoolTracker: Email: ' . $email);
        error_log('👤 PoolTracker: Name: ' . $name);
        error_log('👤 PoolTracker: Picture: ' . ($picture ?: 'VIDE'));
        
        $provider = $this->determine_provider_from_sub($auth0_sub);
        error_log('👤 PoolTracker: Provider déterminé: ' . $provider);
        
        // Vérifier si l'utilisateur existe déjà
        error_log('🔍 PoolTracker: Recherche utilisateur existant...');
        $existing_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_auth0_users} WHERE auth0_sub = %s",
            $auth0_sub
        ));
        
        if ($existing_user) {
            error_log('✅ PoolTracker: Utilisateur existant trouvé - ID: ' . $existing_user->pooltracker_user_id);
            error_log('👤 PoolTracker: Mise à jour des informations...');
            
            $update_result = $wpdb->update(
                $this->table_auth0_users,
                array(
                    'name' => $name,
                    'email' => $email,
                    'picture' => $picture,
                    'last_login' => current_time('mysql')
                ),
                array('auth0_sub' => $auth0_sub),
                array('%s', '%s', '%s', '%s'),
                array('%s')
            );
            
            if ($update_result !== false) {
                error_log('✅ PoolTracker: Mise à jour réussie (lignes affectées: ' . $update_result . ')');
            } else {
                error_log('⚠️ PoolTracker: Mise à jour sans changement ou erreur: ' . $wpdb->last_error);
            }
            
            return $existing_user->pooltracker_user_id;
            
        } else {
            error_log('🆕 PoolTracker: Nouvel utilisateur - création...');
            
            // Générer un ID unique
            $pooltracker_user_id = $this->generate_unique_user_id();
            error_log('🔢 PoolTracker: ID généré: ' . $pooltracker_user_id);
            
            $insert_result = $wpdb->insert(
                $this->table_auth0_users,
                array(
                    'pooltracker_user_id' => $pooltracker_user_id,
                    'auth0_sub' => $auth0_sub,
                    'email' => $email,
                    'name' => $name,
                    'picture' => $picture,
                    'provider' => $provider,
                    'created_at' => current_time('mysql'),
                    'last_login' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($insert_result) {
                $inserted_id = $wpdb->insert_id;
                error_log('✅ PoolTracker: Utilisateur créé avec ID DB: ' . $inserted_id . ', PoolTracker ID: ' . $pooltracker_user_id);
                
                // Créer le profil piscine par défaut
                error_log('🏊‍♂️ PoolTracker: Création profil piscine par défaut...');
                $profile_result = $this->create_default_profile($pooltracker_user_id);
                if ($profile_result) {
                    error_log('✅ PoolTracker: Profil piscine créé avec succès');
                } else {
                    error_log('⚠️ PoolTracker: Erreur création profil: ' . $wpdb->last_error);
                }
                
                return $pooltracker_user_id;
                
            } else {
                error_log('❌ PoolTracker: Erreur insertion utilisateur: ' . $wpdb->last_error);
                error_log('❌ PoolTracker: Requête: ' . $wpdb->last_query);
                return false;
            }
        }
    }
    
    private function generate_unique_user_id() {
        global $wpdb;
        
        do {
            $user_id = rand(100000, 999999);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_auth0_users} WHERE pooltracker_user_id = %d",
                $user_id
            ));
        } while ($exists > 0);
        
        return $user_id;
    }
    
    public function create_default_profile($user_id) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_users,
            array(
                'user_id' => $user_id,
                'pool_volume' => null,
                'pool_treatment_type' => null,
                'pool_filtration_type' => null,
                'filtration_hours' => 8
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
    }
    
    // =====================================
    // MÉTHODES AJAX COMPLÈTES
    // =====================================
    
    public function save_measurement() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        global $wpdb;
        
        $data = array(
            'user_id' => $user_id,
            'ph_value' => !empty($_POST['ph_value']) ? floatval($_POST['ph_value']) : null,
            'chlorine_mg_l' => !empty($_POST['chlorine_mg_l']) ? floatval($_POST['chlorine_mg_l']) : null,
            'temperature_c' => !empty($_POST['temperature_c']) ? floatval($_POST['temperature_c']) : null,
            'alkalinity' => !empty($_POST['alkalinity']) ? intval($_POST['alkalinity']) : null,
            'hardness' => !empty($_POST['hardness']) ? intval($_POST['hardness']) : null,
            'notes' => sanitize_textarea_field($_POST['notes']),
            'test_date' => sanitize_text_field($_POST['test_date']),
            'test_time' => sanitize_text_field($_POST['test_time']),
            'weather_condition' => sanitize_text_field($_POST['weather_condition'])
        );
        
        $result = $wpdb->insert($this->table_measurements, $data);
        
        if ($result) {
            $measurement_id = $wpdb->insert_id;
            
            // Génération automatique d'alertes
            $this->generate_automatic_alerts($user_id, $data, $measurement_id);
            
            wp_send_json_success(array(
                'message' => 'Mesure enregistrée avec succès',
                'measurement_id' => $measurement_id
            ));
        } else {
            wp_send_json_error('Erreur lors de l\'enregistrement');
        }
    }
    
    public function get_user_dashboard_data() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        global $wpdb;
        
        // Statistiques utilisateur
        $total_tests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_measurements} WHERE user_id = %d",
            $user_id
        ));
        
        $latest_measurement = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} WHERE user_id = %d ORDER BY test_date DESC, test_time DESC LIMIT 1",
            $user_id
        ));
        
        $recent_measurements = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} WHERE user_id = %d ORDER BY test_date DESC, test_time DESC LIMIT 5",
            $user_id
        ));
        
        $active_alerts = $this->get_user_alerts($user_id, true);
        
        wp_send_json_success(array(
            'stats' => array(
                'total_tests' => $total_tests,
                'current_ph' => $latest_measurement ? $latest_measurement->ph_value : null,
                'current_chlorine' => $latest_measurement ? $latest_measurement->chlorine_mg_l : null,
                'last_test_date' => $latest_measurement ? $latest_measurement->test_date : null
            ),
            'recent_measurements' => $recent_measurements,
            'alerts' => $active_alerts
        ));
    }
    
    public function update_user_profile() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        global $wpdb;
        
        $data = array(
            'pool_volume' => !empty($_POST['pool_volume']) ? floatval($_POST['pool_volume']) : null,
            'pool_treatment_type' => sanitize_text_field($_POST['pool_treatment_type']),
            'pool_filtration_type' => sanitize_text_field($_POST['pool_filtration_type']),
            'pool_depth_avg' => !empty($_POST['pool_depth_avg']) ? floatval($_POST['pool_depth_avg']) : null,
            'pool_shape' => sanitize_text_field($_POST['pool_shape']),
            'has_cover' => !empty($_POST['has_cover']) ? 1 : 0,
            'has_heat_pump' => !empty($_POST['has_heat_pump']) ? 1 : 0,
            'filtration_hours' => !empty($_POST['filtration_hours']) ? intval($_POST['filtration_hours']) : 8
        );
        
        $result = $wpdb->update(
            $this->table_users,
            $data,
            array('user_id' => $user_id),
            array('%f', '%s', '%s', '%f', '%s', '%d', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Profil mis à jour avec succès');
        } else {
            wp_send_json_error('Erreur lors de la mise à jour');
        }
    }
    
    public function get_chart_data() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        $days = intval($_POST['days']) ?: 30;
        
        $measurements = $this->get_user_measurements($user_id, $days);
        
        wp_send_json_success(array(
            'measurements' => $measurements,
            'period' => $days
        ));
    }
    
    public function get_user_measurements($user_id, $days_limit = 30) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} 
             WHERE user_id = %d 
             AND test_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             ORDER BY test_date DESC, test_time DESC",
            $user_id, $days_limit
        ));
    }
    
    public function get_personalized_ai_advice() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        
        // Analyser les dernières données pour un conseil personnalisé
        $recent_measurements = $this->get_user_measurements($user_id, 7);
        $advice = $this->generate_personalized_advice($recent_measurements);
        
        wp_send_json_success(array(
            'advice' => $advice
        ));
    }
    
    private function generate_personalized_advice($measurements) {
        if (empty($measurements)) {
            return '💡 Bienvenue dans PoolTracker ! Commencez par ajouter votre premier test d\'eau pour recevoir des conseils personnalisés.';
        }
        
        $latest = $measurements[0];
        $advice_parts = array();
        
        // Analyse pH
        if ($latest->ph_value) {
            if ($latest->ph_value < 7.0) {
                $advice_parts[] = "🔴 Votre pH est trop bas (" . $latest->ph_value . "). Ajoutez du pH+ pour le remonter vers 7.2.";
            } elseif ($latest->ph_value > 7.4) {
                $advice_parts[] = "🔴 Votre pH est trop haut (" . $latest->ph_value . "). Ajoutez du pH- pour le baisser vers 7.2.";
            } else {
                $advice_parts[] = "✅ Excellent pH (" . $latest->ph_value . ") ! Maintenez cette valeur.";
            }
        }
        
        // Analyse Chlore
        if ($latest->chlorine_mg_l) {
            if ($latest->chlorine_mg_l < 0.5) {
                $advice_parts[] = "🔴 Taux de chlore insuffisant (" . $latest->chlorine_mg_l . " mg/L). Effectuez un traitement choc.";
            } elseif ($latest->chlorine_mg_l > 2.0) {
                $advice_parts[] = "⚠️ Taux de chlore élevé (" . $latest->chlorine_mg_l . " mg/L). Réduisez le dosage et attendez.";
            } else {
                $advice_parts[] = "✅ Bon taux de chlore (" . $latest->chlorine_mg_l . " mg/L).";
            }
        }
        
        // Analyse température
        if ($latest->temperature_c) {
            if ($latest->temperature_c > 28) {
                $advice_parts[] = "🌡️ Température élevée (" . $latest->temperature_c . "°C). Surveillez le chlore qui s'évapore plus vite.";
            }
        }
        
        // Conseil général selon la fréquence
        $days_since_last = 0;
        if (count($measurements) > 1) {
            $last_date = new DateTime($latest->test_date);
            $prev_date = new DateTime($measurements[1]->test_date);
            $days_since_last = $last_date->diff($prev_date)->days;
        }
        
        if ($days_since_last > 7) {
            $advice_parts[] = "📅 Pensez à tester votre eau plus régulièrement (au moins 2 fois par semaine).";
        }
        
        return implode(' ', $advice_parts) ?: '💡 Continuez votre excellent suivi ! Votre piscine est en bonne santé.';
    }
    
    public function mark_alert_read() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        global $wpdb;
        $alert_id = intval($_POST['alert_id']);
        
        $result = $wpdb->update(
            $this->table_alerts,
            array('is_read' => 1),
            array('id' => $alert_id),
            array('%d'),
            array('%d')
        );
        
        wp_send_json_success('Alerte marquée comme lue');
    }
    
    public function get_user_tests_paginated() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        global $wpdb;
        
        $page = intval($_POST['page']) ?: 1;
        $per_page = intval($_POST['per_page']) ?: 10;
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = array("user_id = %d");
        $where_params = array($user_id);
        
        // Filtres
        if (!empty($_POST['search'])) {
            $search = '%' . $wpdb->esc_like($_POST['search']) . '%';
            $where_conditions[] = "(notes LIKE %s OR weather_condition LIKE %s)";
            $where_params[] = $search;
            $where_params[] = $search;
        }
        
        if (!empty($_POST['date_from'])) {
            $where_conditions[] = "test_date >= %s";
            $where_params[] = $_POST['date_from'];
        }
        
        if (!empty($_POST['date_to'])) {
            $where_conditions[] = "test_date <= %s";
            $where_params[] = $_POST['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Compter le total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_measurements} WHERE {$where_clause}",
            $where_params
        ));
        
        // Récupérer les tests
        $tests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} 
             WHERE {$where_clause} 
             ORDER BY test_date DESC, test_time DESC 
             LIMIT %d OFFSET %d",
            array_merge($where_params, array($per_page, $offset))
        ));
        
        wp_send_json_success(array(
            'tests' => $tests,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    public function update_measurement() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        global $wpdb;
        $measurement_id = intval($_POST['measurement_id']);
        $user_id = $this->get_current_pooltracker_user_id();
        
        // Vérifier que la mesure appartient à l'utilisateur
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} WHERE id = %d AND user_id = %d",
            $measurement_id, $user_id
        ));
        
        if (!$existing) {
            wp_send_json_error('Mesure non trouvée');
            return;
        }
        
        $data = array(
            'ph_value' => !empty($_POST['ph_value']) ? floatval($_POST['ph_value']) : null,
            'chlorine_mg_l' => !empty($_POST['chlorine_mg_l']) ? floatval($_POST['chlorine_mg_l']) : null,
            'temperature_c' => !empty($_POST['temperature_c']) ? floatval($_POST['temperature_c']) : null,
            'alkalinity' => !empty($_POST['alkalinity']) ? intval($_POST['alkalinity']) : null,
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        $result = $wpdb->update(
            $this->table_measurements,
            $data,
            array('id' => $measurement_id),
            array('%f', '%f', '%f', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Mesure mise à jour');
        } else {
            wp_send_json_error('Erreur de mise à jour');
        }
    }
    
    public function delete_measurement() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        global $wpdb;
        $measurement_id = intval($_POST['measurement_id']);
        $user_id = $this->get_current_pooltracker_user_id();
        
        $result = $wpdb->delete(
            $this->table_measurements,
            array(
                'id' => $measurement_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            wp_send_json_success('Mesure supprimée');
        } else {
            wp_send_json_error('Erreur de suppression');
        }
    }
    
    public function export_tests_csv() {
        if (!$this->is_user_authenticated() || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            wp_send_json_error('Accès non autorisé');
            return;
        }
        
        $user_id = $this->get_current_pooltracker_user_id();
        global $wpdb;
        
        $tests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} WHERE user_id = %d ORDER BY test_date DESC",
            $user_id
        ));
        
        $filename = 'pooltracker_tests_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($output, array(
            'Date', 'Heure', 'pH', 'Chlore (mg/L)', 'Température (°C)', 
            'TAC', 'Dureté', 'Météo', 'Notes'
        ));
        
        // Données
        foreach ($tests as $test) {
            fputcsv($output, array(
                $test->test_date,
                $test->test_time,
                $test->ph_value,
                $test->chlorine_mg_l,
                $test->temperature_c,
                $test->alkalinity,
                $test->hardness,
                $test->weather_condition,
                $test->notes
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private function generate_automatic_alerts($user_id, $measurement_data, $measurement_id) {
        global $wpdb;
        
        $alerts = array();
        
        // Alerte pH
        if (!empty($measurement_data['ph_value'])) {
            $ph = floatval($measurement_data['ph_value']);
            if ($ph < 6.8 || $ph > 7.6) {
                $category = ($ph < 6.5 || $ph > 8.0) ? 'urgent' : 'warning';
                $alerts[] = array(
                    'alert_type' => 'ph_anomaly',
                    'alert_category' => $category,
                    'alert_title' => 'pH anormal détecté',
                    'alert_message' => "pH mesuré: {$ph}. Valeur recommandée: 7.0-7.4",
                    'related_measurement_id' => $measurement_id
                );
            }
        }
        
        // Alerte chlore
        if (!empty($measurement_data['chlorine_mg_l'])) {
            $chlorine = floatval($measurement_data['chlorine_mg_l']);
            if ($chlorine < 0.5 || $chlorine > 2.5) {
                $category = ($chlorine < 0.2 || $chlorine > 3.0) ? 'urgent' : 'warning';
                $alerts[] = array(
                    'alert_type' => 'chlorine_anomaly',
                    'alert_category' => $category,
                    'alert_title' => 'Taux de chlore anormal',
                    'alert_message' => "Chlore mesuré: {$chlorine} mg/L. Valeur recommandée: 0.5-2.0 mg/L",
                    'related_measurement_id' => $measurement_id
                );
            }
        }
        
        // Sauvegarder les alertes
        foreach ($alerts as $alert) {
            $alert['user_id'] = $user_id;
            $wpdb->insert($this->table_alerts, $alert);
        }
    }
    
    public function get_user_alerts($user_id, $unread_only = false) {
        global $wpdb;
        
        $where_clause = "user_id = %d";
        $params = array($user_id);
        
        if ($unread_only) {
            $where_clause .= " AND is_read = 0";
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_alerts} WHERE {$where_clause} ORDER BY created_at DESC LIMIT 10",
            $params
        ));
    }
    
    public function add_user_context($context, $user_id) {
        if (!$user_id || !$this->is_user_authenticated()) {
            return $context;
        }
        
        $profile = $this->get_user_profile($user_id);
        $recent_measurements = $this->get_user_measurements($user_id, 7);
        
        $personalized_context = "\n\n🏊‍♂️ PROFIL UTILISATEUR PERSONNALISÉ :\n";
        $personalized_context .= "=====================================\n";
        
        if ($profile && $profile->pool_volume) {
            $personalized_context .= "• Volume piscine : {$profile->pool_volume}m³\n";
        }
        if ($profile && $profile->pool_treatment_type) {
            $personalized_context .= "• Type traitement : {$profile->pool_treatment_type}\n";
        }
        if ($profile && $profile->pool_filtration_type) {
            $personalized_context .= "• Filtration : {$profile->pool_filtration_type}\n";
        }
        
        if (!empty($recent_measurements)) {
            $personalized_context .= "\n📊 DERNIÈRES MESURES (7 jours) :\n";
            foreach (array_slice($recent_measurements, 0, 5) as $measure) {
                $personalized_context .= "• {$measure->test_date} : pH {$measure->ph_value}, Cl {$measure->chlorine_mg_l}mg/L";
                if ($measure->temperature_c) {
                    $personalized_context .= ", {$measure->temperature_c}°C";
                }
                $personalized_context .= "\n";
            }
        }
        
        // Alertes actives
        $active_alerts = $this->get_user_alerts($user_id, true);
        if (!empty($active_alerts)) {
            $personalized_context .= "\n🚨 ALERTES ACTIVES :\n";
            foreach (array_slice($active_alerts, 0, 3) as $alert) {
                $personalized_context .= "• {$alert->alert_title}\n";
            }
        }
        
        $personalized_context .= "=====================================\n";
        $personalized_context .= "Utilise ces données pour des conseils précis et personnalisés.\n";
        
        return $context . $personalized_context;
    }
    
    // =====================================
    // MÉTHODE POUR FORCER UNE NOUVELLE SESSION (DEBUG)
    // =====================================
    
    public function force_session_refresh() {
        error_log('🔄 PoolTracker: === FORCE SESSION REFRESH ===');
        
        // Démarrer ou redémarrer la session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            error_log('🔄 PoolTracker: Session existante fermée');
        }
        
        // Générer un nouvel ID de session
        if (session_start()) {
            session_regenerate_id(true);
            error_log('✅ PoolTracker: Nouvelle session générée: ' . session_id());
            return true;
        } else {
            error_log('❌ PoolTracker: Échec génération nouvelle session');
            return false;
        }
    }
    
    public function debug_session_status() {
        error_log('🔍 PoolTracker DEBUG: === STATUS SESSION DEMANDÉ ===');
        
        $response = array(
            'timestamp' => current_time('mysql'),
            'session' => array(
                'status' => session_status(),
                'status_text' => $this->get_session_status_text(),
                'id' => session_id(),
                'data_count' => isset($_SESSION) ? count($_SESSION) : 0,
                'pooltracker_user_id' => $_SESSION['pooltracker_user_id'] ?? 'ABSENT',
                'pooltracker_login_time' => $_SESSION['pooltracker_login_time'] ?? 'ABSENT',
                'all_session_data' => $_SESSION ?? array()
            ),
            'auth_check' => array(
                'is_authenticated' => $this->is_user_authenticated(),
                'current_user_id' => $this->get_current_pooltracker_user_id(),
                'user_info_available' => $this->get_current_user_info() !== null
            ),
            'database' => array(),
            'server' => array(
                'php_version' => PHP_VERSION,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'AUCUN',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
            )
        );
        
        // Vérifier la base de données
        if ($this->is_user_authenticated()) {
            global $wpdb;
            $user_id = $this->get_current_pooltracker_user_id();
            
            $db_user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_auth0_users} WHERE pooltracker_user_id = %d",
                $user_id
            ));
            
            $response['database'] = array(
                'user_found' => $db_user !== null,
                'user_data' => $db_user,
                'user_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_auth0_users}")
            );
        }
        
        error_log('📤 PoolTracker DEBUG: Réponse status: ' . print_r($response, true));
        
        wp_send_json_success($response);
    }
    
    private function get_session_status_text() {
        switch (session_status()) {
            case PHP_SESSION_DISABLED: return 'PHP_SESSION_DISABLED';
            case PHP_SESSION_NONE: return 'PHP_SESSION_NONE';
            case PHP_SESSION_ACTIVE: return 'PHP_SESSION_ACTIVE';
            default: return 'UNKNOWN';
        }
    }
    
    // Page de debug spéciale
    public function render_debug_page($atts) {
        ob_start();
        ?>
        <div style="max-width: 1000px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 10px; font-family: monospace;">
            <h2 style="color: #e74c3c;">🔧 PoolTracker - Debug Session Live</h2>
            
            <div style="margin-bottom: 20px;">
                <button id="refresh-debug" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">🔄 Actualiser</button>
                <button id="test-auth" style="background: #e67e22; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">🧪 Test Auth</button>
                <button id="force-logout" style="background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">🚪 Force Logout</button>
            </div>
            
            <div id="debug-content" style="background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 5px; max-height: 600px; overflow-y: auto;">
                <div style="color: #f39c12;">Chargement du debug...</div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;">
                <strong>💡 Instructions:</strong><br>
                1. Cliquez "🔄 Actualiser" pour voir l'état actuel<br>
                2. Cliquez "🧪 Test Auth" pour ouvrir la page de connexion<br>
                3. Connectez-vous dans le nouvel onglet, puis revenez ici<br>
                4. Cliquez "🔄 Actualiser" pour voir si la session persiste<br>
                5. Si "PoolTracker User ID" reste "ABSENT", le problème est la persistence de session
            </div>
            
            <!-- STATUS RAPIDE PHP/SERVER -->
            <div style="margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 5px;">
                <strong>🖥️ Status Serveur Rapide:</strong><br>
                • PHP Version: <?php echo PHP_VERSION; ?><br>
                • Session Status: <?php echo $this->get_session_status_text(); ?><br>
                • Session ID: <?php echo session_id() ?: 'AUCUN'; ?><br>
                • PoolTracker User ID: <?php echo $_SESSION['pooltracker_user_id'] ?? 'ABSENT'; ?><br>
                • Is Authenticated: <?php echo $this->is_user_authenticated() ? 'OUI' : 'NON'; ?><br>
                • AJAX URL: <?php echo admin_url('admin-ajax.php'); ?>
            </div>
        </div>
        
        <script>
        // Variables locales pour ce debug (indépendantes de poolTracker)
        var debugAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var debugNonce = '<?php echo wp_create_nonce('pooltracker_nonce'); ?>';
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 Debug PoolTracker - Initialisation');
            console.log('AJAX URL:', debugAjaxUrl);
            console.log('Nonce:', debugNonce);
            
            function refreshDebug() {
                document.getElementById('debug-content').innerHTML = '<div style="color: #f39c12;">🔄 Actualisation...</div>';
                
                fetch(debugAjaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        'action': 'pool_debug_session',
                        '_wpnonce': debugNonce
                    })
                })
                .then(function(response) {
                    console.log('Réponse statut:', response.status);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text();
                })
                .then(function(text) {
                    console.log('Réponse brute:', text.substring(0, 200) + '...');
                    
                    try {
                        var data = JSON.parse(text);
                        if (data.success) {
                            displayDebugData(data.data);
                        } else {
                            document.getElementById('debug-content').innerHTML = '<div style="color: #e74c3c;">❌ Erreur serveur: ' + (data.data || 'Inconnue') + '</div>';
                        }
                    } catch (parseError) {
                        console.error('Erreur parsing:', parseError);
                        document.getElementById('debug-content').innerHTML = '<div style="color: #e74c3c;">❌ Erreur parsing JSON: ' + parseError.message + '<br><br>Réponse brute:<br>' + text + '</div>';
                    }
                })
                .catch(function(error) {
                    console.error('Erreur requête:', error);
                    document.getElementById('debug-content').innerHTML = '<div style="color: #e74c3c;">❌ Erreur réseau: ' + error.message + '</div>';
                });
            }
            
            function displayDebugData(data) {
                var html = '';
                html += '<div style="color: #3498db; font-weight: bold; margin-bottom: 15px;">📊 STATUS COMPLET - ' + data.timestamp + '</div>';
                
                // Session
                html += '<div style="color: #2ecc71; font-weight: bold; margin: 15px 0 5px 0;">🔗 SESSION PHP:</div>';
                html += '<div style="margin-left: 20px;">';
                html += '• Status: <span style="color: ' + (data.session.status === 2 ? '#2ecc71' : '#e74c3c') + ';">' + data.session.status_text + '</span><br>';
                html += '• ID: ' + (data.session.id || 'AUCUN') + '<br>';
                html += '• Data Count: ' + data.session.data_count + '<br>';
                html += '• PoolTracker User ID: <span style="color: ' + (data.session.pooltracker_user_id !== 'ABSENT' ? '#2ecc71' : '#e74c3c') + '; font-weight: bold; font-size: 16px;">' + data.session.pooltracker_user_id + '</span><br>';
                html += '• Login Time: ' + data.session.pooltracker_login_time + '<br>';
                html += '</div>';
                
                // Auth Check
                html += '<div style="color: #e67e22; font-weight: bold; margin: 15px 0 5px 0;">🔐 AUTHENTIFICATION:</div>';
                html += '<div style="margin-left: 20px;">';
                html += '• Is Authenticated: <span style="color: ' + (data.auth_check.is_authenticated ? '#2ecc71' : '#e74c3c') + '; font-weight: bold; font-size: 16px;">' + (data.auth_check.is_authenticated ? 'OUI ✅' : 'NON ❌') + '</span><br>';
                html += '• Current User ID: ' + (data.auth_check.current_user_id || 'AUCUN') + '<br>';
                html += '• User Info Available: <span style="color: ' + (data.auth_check.user_info_available ? '#2ecc71' : '#e74c3c') + ';">' + (data.auth_check.user_info_available ? 'OUI' : 'NON') + '</span><br>';
                html += '</div>';
                
                // Database
                if (data.database && Object.keys(data.database).length > 0) {
                    html += '<div style="color: #9b59b6; font-weight: bold; margin: 15px 0 5px 0;">🗃️ BASE DE DONNÉES:</div>';
                    html += '<div style="margin-left: 20px;">';
                    html += '• User Found: <span style="color: ' + (data.database.user_found ? '#2ecc71' : '#e74c3c') + ';">' + (data.database.user_found ? 'OUI' : 'NON') + '</span><br>';
                    html += '• Total Users: ' + data.database.user_count + '<br>';
                    if (data.database.user_data) {
                        html += '• User Email: ' + data.database.user_data.email + '<br>';
                        html += '• User Name: ' + data.database.user_data.name + '<br>';
                        html += '• Provider: ' + data.database.user_data.provider + '<br>';
                        html += '• Last Login: ' + data.database.user_data.last_login + '<br>';
                    }
                    html += '</div>';
                } else {
                    html += '<div style="color: #9b59b6; font-weight: bold; margin: 15px 0 5px 0;">🗃️ BASE DE DONNÉES:</div>';
                    html += '<div style="margin-left: 20px; color: #e74c3c;">❌ Aucune donnée utilisateur (pas connecté)</div>';
                }
                
                // DIAGNOSTIC INSTANTANÉ
                html += '<div style="color: #f39c12; font-weight: bold; margin: 25px 0 10px 0; font-size: 16px;">🎯 DIAGNOSTIC INSTANT:</div>';
                html += '<div style="margin-left: 20px; padding: 15px; background: #34495e; border-radius: 5px;">';
                
                if (data.session.pooltracker_user_id !== 'ABSENT' && data.auth_check.is_authenticated) {
                    html += '<span style="color: #2ecc71; font-weight: bold; font-size: 18px;">✅ TOUT FONCTIONNE !</span><br>';
                    html += '<span style="color: #ecf0f1;">L\'utilisateur est bien connecté et la session persiste.</span>';
                } else if (data.database && data.database.user_count > 0) {
                    html += '<span style="color: #e74c3c; font-weight: bold; font-size: 18px;">❌ PROBLÈME DE SESSION</span><br>';
                    html += '<span style="color: #ecf0f1;">• Auth0 fonctionne (utilisateur en BDD)<br>';
                    html += '• Mais la session PHP ne persiste pas<br>';
                    html += '• Problème côté serveur ou configuration session</span>';
                } else {
                    html += '<span style="color: #f39c12; font-weight: bold; font-size: 18px;">⚠️ PAS ENCORE TESTÉ</span><br>';
                    html += '<span style="color: #ecf0f1;">Connectez-vous d\'abord via l\'onglet "Test Auth"</span>';
                }
                
                html += '</div>';
                
                // Session Data détaillées (collapsible)
                html += '<div style="color: #95a5a6; font-weight: bold; margin: 15px 0 5px 0; cursor: pointer;" onclick="toggleSessionData()">📋 DONNÉES SESSION COMPLÈTES (cliquer pour afficher/masquer)</div>';
                html += '<div id="session-data-details" style="display: none; margin-left: 20px; font-size: 11px; background: #34495e; padding: 10px; border-radius: 3px; white-space: pre-wrap;">';
                html += JSON.stringify(data.session.all_session_data, null, 2);
                html += '</div>';
                
                document.getElementById('debug-content').innerHTML = html;
            }
            
            // Fonction pour toggle les détails de session
            window.toggleSessionData = function() {
                var details = document.getElementById('session-data-details');
                if (details) {
                    details.style.display = details.style.display === 'none' ? 'block' : 'none';
                }
            };
            
            document.getElementById('refresh-debug').addEventListener('click', refreshDebug);
            
            document.getElementById('test-auth').addEventListener('click', function() {
                window.open('/connexion/', '_blank');
            });
            
            document.getElementById('force-logout').addEventListener('click', function() {
                if (confirm('Forcer la déconnexion ?')) {
                    fetch(debugAjaxUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            'action': 'pool_logout',
                            '_wpnonce': debugNonce
                        })
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        alert('Déconnexion: ' + (data.success ? 'Succès' : 'Échec - ' + data.data));
                        refreshDebug();
                    })
                    .catch(function(error) {
                        alert('Erreur déconnexion: ' + error.message);
                    });
                }
            });
            
            // Auto-refresh initial
            refreshDebug();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function get_user_profile($user_id) {
        global $wpdb;
        
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_users} WHERE user_id = %d",
            $user_id
        ));
        
        // Si pas de profil, créer un profil par défaut
        if (!$profile) {
            $this->create_default_profile($user_id);
            return $this->get_user_profile($user_id);
        }
        
        return $profile;
    }
    
    // =====================================
    // ADMIN ET CONFIGURATION
    // =====================================
    
    public function admin_menu() {
        add_menu_page(
            'PoolTracker Auth0',
            'PoolTracker',
            'manage_options',
            'pooltracker-auth0',
            array($this, 'admin_page'),
            'dashicons-swimmer',
            30
        );
        
        add_submenu_page(
            'pooltracker-auth0',
            'Utilisateurs',
            'Utilisateurs',
            'manage_options',
            'pooltracker-users',
            array($this, 'users_admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_auth0_settings')) {
            update_option('pooltracker_auth0_domain', sanitize_text_field($_POST['auth0_domain']));
            update_option('pooltracker_auth0_client_id', sanitize_text_field($_POST['auth0_client_id']));
            update_option('pooltracker_auth0_client_secret', sanitize_text_field($_POST['auth0_client_secret']));
            echo '<div class="notice notice-success"><p><strong>✅ Configuration Auth0 sauvegardée !</strong></p></div>';
        }
        
        // Test de diagnostic
        if (isset($_POST['test_auth']) && wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_auth0_settings')) {
            echo '<div class="notice notice-info"><p><strong>🔍 Test de diagnostic lancé...</strong></p></div>';
            $this->run_diagnostic_test();
        }
        
        $auth0_domain = get_option('pooltracker_auth0_domain', '');
        $auth0_client_id = get_option('pooltracker_auth0_client_id', '');
        $auth0_client_secret = get_option('pooltracker_auth0_client_secret', '');
        ?>
        <div class="wrap">
            <h1>🏊‍♂️ PoolTracker - Configuration Auth0</h1>
            
            <!-- DIAGNOSTIC RAPIDE -->
            <div style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h3>🔧 Diagnostic Rapide</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="text-align: center; padding: 15px; background: <?php echo !empty($auth0_domain) ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
                        <strong>Auth0 Domain</strong><br>
                        <span style="color: <?php echo !empty($auth0_domain) ? '#155724' : '#721c24'; ?>;">
                            <?php echo !empty($auth0_domain) ? '✅ Configuré' : '❌ Manquant'; ?>
                        </span>
                    </div>
                    <div style="text-align: center; padding: 15px; background: <?php echo !empty($auth0_client_id) ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
                        <strong>Client ID</strong><br>
                        <span style="color: <?php echo !empty($auth0_client_id) ? '#155724' : '#721c24'; ?>;">
                            <?php echo !empty($auth0_client_id) ? '✅ Configuré' : '❌ Manquant'; ?>
                        </span>
                    </div>
                    <div style="text-align: center; padding: 15px; background: <?php echo $this->check_tables_status() ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
                        <strong>Tables BDD</strong><br>
                        <span style="color: <?php echo $this->check_tables_status() ? '#155724' : '#721c24'; ?>;">
                            <?php echo $this->check_tables_status() ? '✅ OK' : '❌ Manquantes'; ?>
                        </span>
                    </div>
                    <div style="text-align: center; padding: 15px; background: <?php echo session_status() === PHP_SESSION_ACTIVE ? '#d4edda' : '#f8d7da'; ?>; border-radius: 5px;">
                        <strong>Sessions PHP</strong><br>
                        <span style="color: <?php echo session_status() === PHP_SESSION_ACTIVE ? '#155724' : '#721c24'; ?>;">
                            <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Actives' : '❌ Inactives'; ?>
                        </span>
                    </div>
                </div>
                
                <form method="post" action="" style="display: inline;">
                    <?php wp_nonce_field('pooltracker_auth0_settings'); ?>
                    <input type="hidden" name="test_auth" value="1">
                    <button type="submit" class="button button-secondary">🔍 Lancer Diagnostic Complet</button>
                </form>
            </div>
            
            <!-- URLS CONFIGURATION -->
            <div style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>🎯 Configuration Auth0 OBLIGATOIRE :</h3>
                <p><strong>Allez dans votre Dashboard Auth0 → Applications → Votre App → Settings</strong></p>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;">
                    <strong>Allowed Callback URLs:</strong><br>
                    <code style="color: #e83e8c;"><?php echo home_url('/espace-client/'); ?></code>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;">
                    <strong>Allowed Logout URLs:</strong><br>
                    <code style="color: #e83e8c;"><?php echo home_url('/connexion/'); ?></code>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;">
                    <strong>Allowed Web Origins:</strong><br>
                    <code style="color: #e83e8c;"><?php echo home_url(); ?></code>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace;">
                    <strong>Allowed Origins (CORS):</strong><br>
                    <code style="color: #e83e8c;"><?php echo home_url(); ?></code>
                </div>
                
                <p><strong>⚠️ N'oubliez pas de SAUVEGARDER dans Auth0 après avoir ajouté ces URLs !</strong></p>
            </div>
            
            <!-- CONFIGURATION -->
            <form method="post" action="">
                <?php wp_nonce_field('pooltracker_auth0_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Domaine Auth0</th>
                        <td>
                            <input type="text" name="auth0_domain" value="<?php echo esc_attr($auth0_domain); ?>" class="regular-text" placeholder="votre-domaine.auth0.com">
                            <p class="description">Exemple: <code>votre-tenant.eu.auth0.com</code> (sans https://)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client ID Auth0</th>
                        <td>
                            <input type="text" name="auth0_client_id" value="<?php echo esc_attr($auth0_client_id); ?>" class="regular-text">
                            <p class="description">Client ID de votre application Auth0</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret Auth0</th>
                        <td>
                            <input type="password" name="auth0_client_secret" value="<?php echo esc_attr($auth0_client_secret); ?>" class="regular-text">
                            <p class="description">Client Secret de votre application Auth0 (optionnel pour ce type d'app)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('💾 Sauvegarder la configuration'); ?>
            </form>
            
            <!-- GUIDE DE DÉPANNAGE -->
            <div style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h3>🆘 Guide de Dépannage</h3>
                
                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: bold;">❌ Erreur: "Auth0 ou poolTracker non défini"</summary>
                    <div style="padding: 10px; background: #f9f9f9; margin-top: 10px;">
                        <p><strong>Causes possibles:</strong></p>
                        <ul>
                            <li>Configuration Auth0 incomplète (vérifiez Domain et Client ID)</li>
                            <li>Script Auth0 SDK non chargé (problème de réseau)</li>
                            <li>Conflit avec d'autres plugins JavaScript</li>
                        </ul>
                        <p><strong>Solutions:</strong></p>
                        <ol>
                            <li>Vérifiez que tous les champs ci-dessus sont remplis</li>
                            <li>Testez avec un autre navigateur</li>
                            <li>Vérifiez la console JavaScript (F12)</li>
                        </ol>
                    </div>
                </details>
                
                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: bold;">🔄 Problème: "Reste sur la page de connexion après Google"</summary>
                    <div style="padding: 10px; background: #f9f9f9; margin-top: 10px;">
                        <p><strong>Causes possibles:</strong></p>
                        <ul>
                            <li>URLs de callback mal configurées dans Auth0</li>
                            <li>Connexion Google non activée dans Auth0</li>
                            <li>Problème de parsing du token</li>
                        </ul>
                        <p><strong>Solutions:</strong></p>
                        <ol>
                            <li>Vérifiez les URLs de callback ci-dessus dans Auth0</li>
                            <li>Dans Auth0 → Connections → Social → Google, vérifiez que c'est activé</li>
                            <li>Activez la console de debug sur la page de connexion</li>
                        </ol>
                    </div>
                </details>
                
                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: bold;">📧 Problème: "Création de compte échoue"</summary>
                    <div style="padding: 10px; background: #f9f9f9; margin-top: 10px;">
                        <p><strong>Causes possibles:</strong></p>
                        <ul>
                            <li>Database Connection non configurée dans Auth0</li>
                            <li>Politique de mot de passe trop stricte</li>
                            <li>Email déjà utilisé</li>
                        </ul>
                        <p><strong>Solutions:</strong></p>
                        <ol>
                            <li>Dans Auth0 → Connections → Database, vérifiez "Username-Password-Authentication"</li>
                            <li>Vérifiez les règles de mot de passe dans Auth0</li>
                            <li>Testez avec un email différent</li>
                        </ol>
                    </div>
                </details>
                
            </div>
        </div>
        <?php
    }
    
    private function check_tables_status() {
        global $wpdb;
        $tables_to_check = [
            $this->table_auth0_users,
            $this->table_users,
            $this->table_measurements,
            $this->table_alerts
        ];
        
        foreach ($tables_to_check as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }
        return true;
    }
    
    private function run_diagnostic_test() {
        echo '<div style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace; font-size: 12px;">';
        echo '<strong>🔍 DIAGNOSTIC COMPLET POOLTRACKER</strong><br><br>';
        
        // Test 1: Configuration
        echo '<strong>1. Configuration Auth0:</strong><br>';
        $domain = get_option('pooltracker_auth0_domain', '');
        $client_id = get_option('pooltracker_auth0_client_id', '');
        echo '   - Domain: ' . ($domain ? '✅ ' . $domain : '❌ Manquant') . '<br>';
        echo '   - Client ID: ' . ($client_id ? '✅ ' . substr($client_id, 0, 10) . '...' : '❌ Manquant') . '<br>';
        
        // Test 2: Tables
        echo '<br><strong>2. Tables de base de données:</strong><br>';
        global $wpdb;
        $tables = [
            'auth0_users' => $this->table_auth0_users,
            'users' => $this->table_users,
            'measurements' => $this->table_measurements,
            'alerts' => $this->table_alerts
        ];
        
        foreach ($tables as $name => $table) {
            $exists = ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table);
            echo '   - ' . $name . ': ' . ($exists ? '✅ OK' : '❌ Manquante') . '<br>';
            
            if ($exists && $name === 'auth0_users') {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                echo '     → ' . $count . ' utilisateur(s) enregistré(s)<br>';
            }
        }
        
        // Test 3: Sessions
        echo '<br><strong>3. Sessions PHP:</strong><br>';
        echo '   - Status: ' . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . '<br>';
        echo '   - ID: ' . (session_id() ?: 'Aucun') . '<br>';
        if (isset($_SESSION['pooltracker_user_id'])) {
            echo '   - User connecté: ✅ ID ' . $_SESSION['pooltracker_user_id'] . '<br>';
        } else {
            echo '   - User connecté: ❌ Aucun<br>';
        }
        
        // Test 4: URLs
        echo '<br><strong>4. URLs importantes:</strong><br>';
        echo '   - Home: ' . home_url() . '<br>';
        echo '   - Connexion: ' . home_url('/connexion/') . '<br>';
        echo '   - Espace client: ' . home_url('/espace-client/') . '<br>';
        echo '   - AJAX: ' . admin_url('admin-ajax.php') . '<br>';
        
        // Test 5: Permissions
        echo '<br><strong>5. Permissions et hooks:</strong><br>';
        $hooks_count = 0;
        if (has_action('wp_ajax_pool_auth0_callback')) $hooks_count++;
        if (has_action('wp_ajax_nopriv_pool_auth0_callback')) $hooks_count++;
        if (has_action('wp_ajax_pool_logout')) $hooks_count++;
        echo '   - Hooks AJAX: ' . ($hooks_count >= 3 ? '✅ OK (' . $hooks_count . ' hooks)' : '❌ Manquants') . '<br>';
        
        echo '<br><strong>✅ Diagnostic terminé</strong>';
        echo '</div>';
    }
    
    public function users_admin_page() {
        global $wpdb;
        
        // Statistiques utilisateurs
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_auth0_users}");
        $total_profiles = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_users}");
        $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_measurements}");
        $recent_signups = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_auth0_users} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        
        ?>
        <div class="wrap">
            <h1>👥 Gestion des Utilisateurs PoolTracker</h1>
            
            <!-- Statistiques -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
                <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #3AA6B9; margin: 0; font-size: 32px;"><?php echo $total_users; ?></h2>
                    <p style="margin: 5px 0 0 0; color: #666;">Utilisateurs inscrits</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #27AE60; margin: 0; font-size: 32px;"><?php echo $total_profiles; ?></h2>
                    <p style="margin: 5px 0 0 0; color: #666;">Profils piscine</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #E74C3C; margin: 0; font-size: 32px;"><?php echo $total_tests; ?></h2>
                    <p style="margin: 5px 0 0 0; color: #666;">Tests effectués</p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: #F39C12; margin: 0; font-size: 32px;"><?php echo $recent_signups; ?></h2>
                    <p style="margin: 5px 0 0 0; color: #666;">Nouveaux (7j)</p>
                </div>
            </div>
            
            <!-- Liste des utilisateurs -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2>📋 Derniers utilisateurs</h2>
                <?php
                $users = $wpdb->get_results("
                    SELECT au.*, pu.pool_volume, pu.pool_treatment_type,
                           (SELECT COUNT(*) FROM {$this->table_measurements} WHERE user_id = au.pooltracker_user_id) as test_count
                    FROM {$this->table_auth0_users} au
                    LEFT JOIN {$this->table_users} pu ON au.pooltracker_user_id = pu.user_id
                    ORDER BY au.created_at DESC
                    LIMIT 20
                ");
                
                if ($users) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>Utilisateur</th><th>Provider</th><th>Piscine</th><th>Tests</th><th>Inscription</th>';
                    echo '</tr></thead><tbody>';
                    
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($user->name) . '</strong><br><small>' . esc_html($user->email) . '</small></td>';
                        echo '<td><span style="padding: 2px 8px; background: #E3F2FD; border-radius: 12px; font-size: 11px;">' . esc_html($user->provider) . '</span></td>';
                        echo '<td>' . ($user->pool_volume ? $user->pool_volume . 'm³ (' . $user->pool_treatment_type . ')' : '<em>Non configurée</em>') . '</td>';
                        echo '<td>' . intval($user->test_count) . '</td>';
                        echo '<td>' . date('d/m/Y', strtotime($user->created_at)) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p><em>Aucun utilisateur inscrit pour le moment.</em></p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    // =====================================
    // CRÉATION DES TABLES
    // =====================================
    
    private function maybe_create_missing_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table utilisateurs Auth0
        $table_auth0 = $this->table_auth0_users;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_auth0'") != $table_auth0) {
            $sql = "CREATE TABLE $table_auth0 (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                pooltracker_user_id int(11) NOT NULL,
                auth0_sub varchar(255) NOT NULL,
                email varchar(255) NOT NULL,
                name varchar(255) DEFAULT NULL,
                picture text DEFAULT NULL,
                provider varchar(50) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                last_login datetime DEFAULT NULL,
                is_active tinyint(1) DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY auth0_sub (auth0_sub),
                UNIQUE KEY pooltracker_user_id (pooltracker_user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Table utilisateurs PoolTracker
        $table_users = $this->table_users;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_users'") != $table_users) {
            $sql = "CREATE TABLE $table_users (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id int(11) NOT NULL,
                pool_volume decimal(8,2) DEFAULT NULL,
                pool_treatment_type varchar(50) DEFAULT NULL,
                pool_filtration_type varchar(50) DEFAULT NULL,
                pool_depth_avg decimal(4,2) DEFAULT NULL,
                pool_shape varchar(50) DEFAULT NULL,
                has_cover tinyint(1) DEFAULT 0,
                has_heat_pump tinyint(1) DEFAULT 0,
                filtration_hours int(2) DEFAULT 8,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Table mesures
        $table_measurements = $this->table_measurements;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_measurements'") != $table_measurements) {
            $sql = "CREATE TABLE $table_measurements (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id int(11) NOT NULL,
                ph_value decimal(3,1) DEFAULT NULL,
                chlorine_mg_l decimal(4,2) DEFAULT NULL,
                temperature_c decimal(4,1) DEFAULT NULL,
                alkalinity int(4) DEFAULT NULL,
                hardness int(4) DEFAULT NULL,
                notes text DEFAULT NULL,
                test_date date NOT NULL,
                test_time time DEFAULT NULL,
                weather_condition varchar(50) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX user_id (user_id),
                INDEX test_date (test_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Table alertes
        $table_alerts = $this->table_alerts;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_alerts'") != $table_alerts) {
            $sql = "CREATE TABLE $table_alerts (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id int(11) NOT NULL,
                alert_type varchar(50) NOT NULL,
                alert_category varchar(20) DEFAULT 'info',
                alert_title varchar(255) NOT NULL,
                alert_message text NOT NULL,
                related_measurement_id int(11) DEFAULT NULL,
                is_read tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX user_id (user_id),
                INDEX is_read (is_read)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}

// Initialisation
new PoolUserSystemAuth0();
?>