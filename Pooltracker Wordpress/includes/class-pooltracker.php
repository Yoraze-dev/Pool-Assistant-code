<?php
/**
 * Classe principale PoolTracker
 * Orchestration et coordination des modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class PoolTracker {
    
    private $auth0_manager;
    private $user_manager;
    private $ajax_manager;
    
    public function __construct() {
        $this->init_hooks();
        $this->init_managers();
    }
    
    /**
     * Initialisation des hooks WordPress principaux
     */
    private function init_hooks() {
        // Hook très tôt pour les sessions
        add_action('plugins_loaded', array($this, 'init_system'), 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // NOUVEAU : Template personnalisé
        add_filter('page_template', array($this, 'custom_page_template'));
        
        // Shortcodes (garder pour compatibilité)
        // add_shortcode('pooltracker_main', array($this, 'render_main_page')); ← COMMENTÉ
        add_shortcode('pooltracker_login', array($this, 'render_login_page'));
        add_shortcode('pooltracker_debug', array($this, 'render_debug_page'));
    }
    
    /**
     * Utiliser un template personnalisé pour nos pages
     */
    public function custom_page_template($template) {
        global $post;
        
        // DEBUG TEMPORAIRE
        error_log('🔍 POOLTRACKER DEBUG: Hook page_template appelé');
        error_log('🔍 Post name: ' . ($post ? $post->post_name : 'NULL'));
        error_log('🔍 Template demandé: ' . $template);
        
        // Si on n'a pas de post, retourner le template par défaut
        if (!$post) {
            error_log('🔍 Pas de post, template par défaut');
            return $template;
        }
        
        // Page Espace Client
        if ($post->post_name === 'espace-client') {
            error_log('MATCH: Page espace-client détectée !');
            $custom_template = POOLTRACKER_PATH . 'templates/dashboard-page.php';
            error_log('Chemin template: ' . $custom_template);
            error_log('Fichier existe: ' . (file_exists($custom_template) ? 'OUI' : 'NON'));
            
            if (file_exists($custom_template)) {
                error_log('✅ PoolTracker: Utilisation template dashboard');
                return $custom_template;
            } else {
                error_log('❌ PoolTracker: Template dashboard inexistant');
            }
        }
        
        // Page Connexion
        if ($post->post_name === 'connexion') {
            error_log('MATCH: Page connexion détectée !');
            $custom_template = POOLTRACKER_PATH . 'templates/login-page.php';
            error_log('Chemin template: ' . $custom_template);
            error_log('Fichier existe: ' . (file_exists($custom_template) ? 'OUI' : 'NON'));
            
            if (file_exists($custom_template)) {
                error_log('✅ PoolTracker: Utilisation template login');
                return $custom_template;
            } else {
                error_log('❌ PoolTracker: Template login inexistant');
            }
        }
        
        error_log('🔍 Aucun match, template par défaut: ' . $template);
        return $template;
    }
    
    /**
     * Initialisation des managers
     */
    private function init_managers() {
        // Les managers seront initialisés après que WordPress soit prêt
        add_action('init', array($this, 'setup_managers'), 10);
    }
    
    public function setup_managers() {
        // Éviter les duplications
        if ($this->auth0_manager !== null) {
            return;
        }
        
        error_log('PoolTracker: Début setup_managers');
        
        // Vérifier que les classes existent
        if (!class_exists('PoolTracker_Auth0_Manager')) {
            error_log('ERREUR: PoolTracker_Auth0_Manager non trouvée');
            return;
        }
        
        if (!class_exists('PoolTracker_Ajax_Manager')) {
            error_log('ERREUR: PoolTracker_Ajax_Manager non trouvée');
            return;
        }
        
        error_log('PoolTracker: Classes trouvées, instanciation...');
        
        try {
            $this->auth0_manager = new PoolTracker_Auth0_Manager();
            error_log('PoolTracker: Auth0 Manager créé');
            
            // User Manager optionnel pour l'instant
            if (class_exists('PoolTracker_User_Manager')) {
                $this->user_manager = new PoolTracker_User_Manager();
                error_log('PoolTracker: User Manager créé');
            } else {
                error_log('PoolTracker: User Manager non trouvé, utilisation de null');
                $this->user_manager = null;
            }
            
            $this->ajax_manager = new PoolTracker_Ajax_Manager($this->auth0_manager, $this->user_manager);
            error_log('PoolTracker: AJAX Manager créé - Handlers AJAX enregistrés');
            
            error_log('PoolTracker: Tous les managers initialisés avec succès !');
            
        } catch (Exception $e) {
            error_log('ERREUR setup_managers: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialisation du système
     */
    public function init_system() {
        // DEBUG CONSTANTES
        error_log('🔍 POOLTRACKER_PATH: ' . (defined('POOLTRACKER_PATH') ? POOLTRACKER_PATH : 'NON DÉFINIE'));
        error_log('🔍 POOLTRACKER_URL: ' . (defined('POOLTRACKER_URL') ? POOLTRACKER_URL : 'NON DÉFINIE'));
        error_log('🔍 POOLTRACKER_VERSION: ' . (defined('POOLTRACKER_VERSION') ? POOLTRACKER_VERSION : 'NON DÉFINIE'));
        
        // Démarrer les sessions SEULEMENT si pas déjà fait et si les headers ne sont pas envoyés
        if (!session_id() && !headers_sent()) {
            session_start();
            error_log('PoolTracker: Session démarrée');
        } elseif (headers_sent()) {
            error_log('PoolTracker: Headers déjà envoyés, session non démarrée');
        } else {
            error_log('PoolTracker: Session déjà active: ' . session_id());
        }
        
        // Vérifier et créer les tables si nécessaire
        $this->maybe_create_tables();
    }
    
    /**
     * Chargement des assets (CSS/JS)
     */
    public function enqueue_assets() {
        // Charger seulement sur les pages PoolTracker
        if ($this->is_pooltracker_page()) {
            // Chart.js pour les graphiques
            wp_enqueue_script(
                'chart-js', 
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                array(), 
                '3.9.1', 
                true
            );
            
            // CSS PoolTracker
            wp_enqueue_style(
                'pooltracker-styles',
                POOLTRACKER_URL . 'assets/pooltracker.css',
                array(),
                POOLTRACKER_VERSION
            );
            
            // JavaScript PoolTracker
            wp_enqueue_script(
                'pooltracker-js',
                POOLTRACKER_URL . 'assets/pooltracker.js',
                array('chart-js'),
                POOLTRACKER_VERSION,
                true
            );
            
            // Variables JavaScript
            wp_localize_script('pooltracker-js', 'poolTracker', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pooltracker_nonce'),
                'auth0_domain' => get_option('pooltracker_auth0_domain', ''),
                'auth0_client_id' => get_option('pooltracker_auth0_client_id', ''),
                'login_url' => home_url('/connexion/'),
                'dashboard_url' => home_url('/espace-client/'),
                'is_logged_in' => pooltracker_is_user_authenticated()
            ));
        }
    }
    
    /**
     * Vérifier si on est sur une page PoolTracker
     */
    private function is_pooltracker_page() {
        return is_page('connexion') || 
               is_page('espace-client') || 
               is_page('debug-pooltracker') || 
               get_query_var('pooltracker');
    }
    
    /**
     * Menu admin
     */
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
    
    /**
     * Shortcode principal - /espace-client/
     */
    public function render_main_page($atts) {
        // Détecter si c'est un callback Auth0
        $is_auth0_callback = $this->detect_auth0_callback();
        
        if ($is_auth0_callback) {
            return $this->render_auth0_callback_handler();
        }
        
        // Si utilisateur connecté, afficher le dashboard
        if (pooltracker_is_user_authenticated()) {
            return $this->load_template('dashboard');
        }
        
        // Sinon rediriger vers la connexion
        return $this->render_redirect_to_login();
    }
    
    /**
     * Shortcode login - /connexion/
     */
    public function render_login_page($atts) {
        // Si déjà connecté, rediriger vers dashboard
        if (pooltracker_is_user_authenticated()) {
            return $this->render_redirect_to_dashboard();
        }
        
        return $this->load_template('login');
    }
    
    /**
     * Shortcode debug
     */
    public function render_debug_page($atts) {
        return $this->load_template('debug');
    }
    
    /**
     * Charger un template
     */
    private function load_template($template_name, $vars = array()) {
        $template_path = POOLTRACKER_PATH . 'templates/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return '<div class="pooltracker-error">Template non trouvé: ' . $template_name . '</div>';
        }
        
        // Extraire les variables pour le template
        extract($vars);
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Détecter un callback Auth0
     */
    private function detect_auth0_callback() {
        return isset($_GET['auth0_callback']) || 
               (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'auth0.com') !== false) ||
               strpos($_SERVER['REQUEST_URI'], '#') !== false;
    }
    
    /**
     * Rendu du handler de callback Auth0
     */
    private function render_auth0_callback_handler() {
        return $this->load_template('auth0-callback');
    }
    
    /**
     * Redirection vers login
     */
    private function render_redirect_to_login() {
        return $this->load_template('redirect-login');
    }
    
    /**
     * Redirection vers dashboard
     */
    private function render_redirect_to_dashboard() {
        return $this->load_template('redirect-dashboard');
    }
    
    /**
     * Vérifier et créer les tables
     */
    private function maybe_create_tables() {
        // Vérifier si les tables existent
        global $wpdb;
        $table_auth0 = $wpdb->prefix . 'pool_auth0_users';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_auth0'") != $table_auth0) {
            pooltracker_create_tables();
        }
    }
    
    /**
     * Page d'administration principale
     */
    public function admin_page() {
        include POOLTRACKER_PATH . 'admin/admin-dashboard.php';
    }
    
    /**
     * Page de gestion des utilisateurs
     */
    public function users_admin_page() {
        include POOLTRACKER_PATH . 'admin/users-management.php';
    }
    
    /**
     * Getters pour les managers
     */
    public function get_auth0_manager() {
        return $this->auth0_manager;
    }
    
    public function get_user_manager() {
        return $this->user_manager;
    }
    
    public function get_ajax_manager() {
        return $this->ajax_manager;
    }
}