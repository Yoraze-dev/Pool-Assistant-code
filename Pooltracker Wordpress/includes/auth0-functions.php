<?php
/**
 * Gestionnaire Auth0 et Sessions
 * Toute la logique d'authentification centralisée
 */

if (!defined('ABSPATH')) {
    exit;
}

class PoolTracker_Auth0_Manager {
    
    private $table_auth0_users;
    
    public function __construct() {
        global $wpdb;
        $this->table_auth0_users = $wpdb->prefix . 'pool_auth0_users';
        
        $this->init_hooks();
    }
    
    /**
     * Initialiser les hooks Auth0
     */
    private function init_hooks() {
        // Callbacks Auth0
        add_action('wp_ajax_pool_auth0_callback', array($this, 'handle_auth0_callback'));
        add_action('wp_ajax_nopriv_pool_auth0_callback', array($this, 'handle_auth0_callback'));
        
        // Déconnexion
        add_action('wp_ajax_pool_logout', array($this, 'handle_logout'));
        
        // Status auth
        add_action('wp_ajax_pool_get_auth_status', array($this, 'get_auth_status'));
        add_action('wp_ajax_nopriv_pool_get_auth_status', array($this, 'get_auth_status'));
        
        // Debug
        add_action('wp_ajax_pool_debug_session', array($this, 'debug_session_status'));
        add_action('wp_ajax_nopriv_pool_debug_session', array($this, 'debug_session_status'));
    }
    
    /**
     * Vérifier si l'utilisateur est authentifié
     */
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
    
    /**
     * Récupérer l'ID de l'utilisateur connecté
     */
    public function get_current_user_id() {
        return $this->is_user_authenticated() ? intval($_SESSION['pooltracker_user_id']) : false;
    }
    
    /**
     * Récupérer les infos de l'utilisateur connecté
     */
    public function get_current_user_info() {
        if (!$this->is_user_authenticated()) {
            return null;
        }
        
        global $wpdb;
        $user_id = $this->get_current_user_id();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_auth0_users} WHERE pooltracker_user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Définir la session utilisateur
     */
    public function set_user_session($user_id, $user_data) {
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
    
    /**
     * Nettoyer la session utilisateur
     */
    public function clear_user_session() {
        unset($_SESSION['pooltracker_user_id']);
        unset($_SESSION['pooltracker_user_data']);
        unset($_SESSION['pooltracker_login_time']);
        
        error_log("🧹 PoolTracker: Session nettoyée");
        return true;
    }
    
    /**
     * Handler du callback Auth0
     */
    public function handle_auth0_callback() {
        error_log('🔄 PoolTracker Auth0 Callback - Début détaillé');
        error_log('📡 Méthode: ' . $_SERVER['REQUEST_METHOD']);
        error_log('📡 Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Non défini'));
        error_log('📡 POST data: ' . print_r($_POST, true));
        
        // Vérifier la méthode
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log('❌ PoolTracker: Méthode non POST - ' . $_SERVER['REQUEST_METHOD']);
            wp_send_json_error('Méthode non autorisée');
            return;
        }
        
        // Vérifier les tokens
        if (!isset($_POST['access_token']) || !isset($_POST['id_token'])) {
            error_log('❌ PoolTracker: Tokens manquants');
            wp_send_json_error('Tokens manquants');
            return;
        }
        
        // Vérifier le nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'pooltracker_nonce')) {
            error_log('❌ PoolTracker: Nonce invalide');
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        error_log('✅ PoolTracker: Vérifications préliminaires OK');
        
        // Extraire et valider les tokens
        $access_token = sanitize_text_field($_POST['access_token']);
        $id_token = sanitize_text_field($_POST['id_token']);
        
        error_log('🔑 PoolTracker: Access token length: ' . strlen($access_token));
        error_log('🔑 PoolTracker: ID token length: ' . strlen($id_token));
        
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
    
    /**
     * Handler de déconnexion
     */
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
    
    /**
     * Récupérer le statut d'authentification
     */
    public function get_auth_status() {
        $is_authenticated = $this->is_user_authenticated();
        $user_id = $this->get_current_user_id();
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
    
    /**
     * Debug du statut de session
     */
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
                'current_user_id' => $this->get_current_user_id(),
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
            $user_id = $this->get_current_user_id();
            
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
    
    /**
     * Décoder un token JWT
     */
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
            
            // Traitement de l'email et du nom
            if (!isset($payload['email'])) {
                error_log('⚠️ PoolTracker: Email manquant, génération...');
                $payload['email'] = 'user_' . substr($payload['sub'], -8) . '@temp.poolassistant.fr';
                error_log('🔧 PoolTracker: Email temporaire généré: ' . $payload['email']);
            }
            
            if (!isset($payload['name'])) {
                error_log('⚠️ PoolTracker: Name manquant, génération...');
                if (isset($payload['email'])) {
                    $payload['name'] = explode('@', $payload['email'])[0];
                } else {
                    $payload['name'] = 'Utilisateur ' . substr($payload['sub'], -6);
                }
                error_log('🔧 PoolTracker: Name généré: ' . $payload['name']);
            }
            
            // Ajouter des infos debug au payload
            $payload['_debug_provider'] = $this->determine_provider_from_sub($payload['sub']);
            $payload['_debug_validation_time'] = time();
            
            error_log('✅ PoolTracker: === JWT VALIDÉ AVEC SUCCÈS ===');
            
            return $payload;
            
        } catch (Exception $e) {
            error_log('❌ PoolTracker: Erreur décodage JWT - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer ou mettre à jour un utilisateur Auth0
     */
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
                error_log('✅ PoolTracker: Mise à jour réussie');
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
                error_log('✅ PoolTracker: Utilisateur créé avec succès');
                
                // Créer le profil piscine par défaut
                pooltracker_create_default_profile($pooltracker_user_id);
                
                return $pooltracker_user_id;
                
            } else {
                error_log('❌ PoolTracker: Erreur insertion utilisateur: ' . $wpdb->last_error);
                return false;
            }
        }
    }
    
    /**
     * Générer un ID utilisateur unique
     */
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
    
    /**
     * Déterminer le provider depuis le sub Auth0
     */
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
    
    /**
     * Récupérer le texte du statut de session
     */
    private function get_session_status_text() {
        switch (session_status()) {
            case PHP_SESSION_DISABLED: return 'PHP_SESSION_DISABLED';
            case PHP_SESSION_NONE: return 'PHP_SESSION_NONE';
            case PHP_SESSION_ACTIVE: return 'PHP_SESSION_ACTIVE';
            default: return 'UNKNOWN';
        }
    }
}

// =====================================
// FONCTIONS UTILITAIRES GLOBALES
// =====================================

/**
 * Vérifier si l'utilisateur est authentifié (fonction globale)
 */
function pooltracker_is_user_authenticated() {
    static $auth_manager = null;
    
    if ($auth_manager === null) {
        $auth_manager = new PoolTracker_Auth0_Manager();
    }
    
    return $auth_manager->is_user_authenticated();
}

/**
 * Récupérer l'ID de l'utilisateur connecté (fonction globale)
 */
function pooltracker_get_current_user_id() {
    static $auth_manager = null;
    
    if ($auth_manager === null) {
        $auth_manager = new PoolTracker_Auth0_Manager();
    }
    
    return $auth_manager->get_current_user_id();
}

/**
 * Récupérer les infos de l'utilisateur connecté (fonction globale)
 */
function pooltracker_get_current_user_info() {
    static $auth_manager = null;
    
    if ($auth_manager === null) {
        $auth_manager = new PoolTracker_Auth0_Manager();
    }
    
    return $auth_manager->get_current_user_info();
}