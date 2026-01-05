<?php
/**
 * Gestionnaire des requêtes AJAX
 * Centralise toutes les actions AJAX de PoolTracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class PoolTracker_Ajax_Manager {
    
    private $auth0_manager;
    private $user_manager;
    private $table_measurements;
    private $table_alerts;
    
    public function __construct($auth0_manager, $user_manager) {
        global $wpdb;
        
        $this->auth0_manager = $auth0_manager;
        $this->user_manager = $user_manager;
        $this->table_measurements = $wpdb->prefix . 'pool_measurements';
        $this->table_alerts = $wpdb->prefix . 'pool_user_alerts';
        
        $this->init_hooks();
    }
    
    /**
     * Initialiser les hooks AJAX
     */
    private function init_hooks() {
        // NOUVELLES ACTIONS MANQUANTES (pour compatibilité JavaScript)
        add_action('wp_ajax_pooltracker_get_profile', array($this, 'get_user_dashboard_data'));
        add_action('wp_ajax_pooltracker_test', array($this, 'ajax_test'));
        add_action('wp_ajax_pooltracker_save_measurement', array($this, 'save_measurement'));
        add_action('wp_ajax_pooltracker_get_tests', array($this, 'get_user_tests_paginated'));
        add_action('wp_ajax_pooltracker_update_profile', array($this, 'update_user_profile'));
        add_action('wp_ajax_pooltracker_get_chart_data', array($this, 'get_chart_data'));
        add_action('wp_ajax_pooltracker_get_ai_advice', array($this, 'get_personalized_ai_advice'));
        
        // DEBUG SYSTEM (pas d'authentification requise pour le debug)
        add_action('wp_ajax_pooltracker_debug_system', array($this, 'debug_system_complete'));
        add_action('wp_ajax_nopriv_pooltracker_debug_system', array($this, 'debug_system_complete'));
        
        // ANCIENNES ACTIONS (garder pour compatibilité)
        add_action('wp_ajax_pool_save_measurement', array($this, 'save_measurement'));
        add_action('wp_ajax_pool_get_user_tests', array($this, 'get_user_tests_paginated'));
        add_action('wp_ajax_pool_update_measurement', array($this, 'update_measurement'));
        add_action('wp_ajax_pool_delete_measurement', array($this, 'delete_measurement'));
        add_action('wp_ajax_pool_export_tests', array($this, 'export_tests_csv'));
        
        // Dashboard et données utilisateur
        add_action('wp_ajax_pool_get_user_data', array($this, 'get_user_dashboard_data'));
        add_action('wp_ajax_pool_update_profile', array($this, 'update_user_profile'));
        add_action('wp_ajax_pool_get_chart_data', array($this, 'get_chart_data'));
        
        // IA et conseils
        add_action('wp_ajax_pool_get_ai_advice', array($this, 'get_personalized_ai_advice'));
        
        // Alertes
        add_action('wp_ajax_pool_mark_alert_read', array($this, 'mark_alert_read'));
        
        error_log('PoolTracker: ' . count($this->get_registered_actions()) . ' actions AJAX enregistrées');
    }
    
    /**
     * DEBUG SYSTÈME COMPLET - SANS AUTHENTIFICATION
     */
    public function debug_system_complete() {
        error_log('=== POOLTRACKER DEBUG SYSTÈME COMPLET ===');
        
        $debug_info = array();
        
        // 1. VÉRIFICATIONS BASIQUES
        $debug_info['wordpress'] = array(
            'current_user_id' => get_current_user_id(),
            'is_user_logged_in' => is_user_logged_in(),
            'current_time' => current_time('mysql'),
            'admin_ajax_url' => admin_url('admin-ajax.php')
        );
        
        // 2. SESSIONS
        $debug_info['session'] = array(
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION ?? array(),
            'headers_sent' => headers_sent()
        );
        
        // 3. NONCES
        $debug_info['nonces'] = array(
            'nonce_received' => isset($_POST['nonce']) ? $_POST['nonce'] : 'AUCUN',
            'nonce_wp_received' => isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : 'AUCUN',
            'new_nonce_generated' => wp_create_nonce('pooltracker_nonce')
        );
        
        // 4. AUTH0 MANAGER
        if ($this->auth0_manager) {
            try {
                $debug_info['auth0'] = array(
                    'manager_exists' => true,
                    'is_authenticated' => $this->auth0_manager->is_user_authenticated(),
                    'current_user_id' => method_exists($this->auth0_manager, 'get_current_user_id') 
                        ? $this->auth0_manager->get_current_user_id() 
                        : 'MÉTHODE INEXISTANTE',
                    'auth0_domain' => get_option('pooltracker_auth0_domain', 'NON CONFIGURÉ'),
                    'auth0_client_id' => get_option('pooltracker_auth0_client_id', 'NON CONFIGURÉ')
                );
            } catch (Exception $e) {
                $debug_info['auth0'] = array(
                    'manager_exists' => true,
                    'error' => $e->getMessage()
                );
            }
        } else {
            $debug_info['auth0'] = array(
                'manager_exists' => false,
                'error' => 'Auth0 Manager non initialisé'
            );
        }
        
        // 5. BASE DE DONNÉES
        global $wpdb;
        $debug_info['database'] = array();
        
        try {
            $tables_to_check = array(
                'pool_users' => $wpdb->prefix . 'pool_users',
                'pool_auth0_users' => $wpdb->prefix . 'pool_auth0_users',
                'pool_measurements' => $wpdb->prefix . 'pool_measurements'
            );
            
            foreach ($tables_to_check as $name => $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
                $debug_info['database'][$name] = array(
                    'table_name' => $table,
                    'exists' => $exists,
                    'count' => $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0
                );
            }
        } catch (Exception $e) {
            $debug_info['database']['error'] = $e->getMessage();
        }
        
        // 6. ACTIONS AJAX ENREGISTRÉES
        global $wp_filter;
        $ajax_actions = array();
        
        if (isset($wp_filter['wp_ajax_pooltracker_get_profile'])) {
            $ajax_actions['pooltracker_get_profile'] = 'ENREGISTRÉE';
        } else {
            $ajax_actions['pooltracker_get_profile'] = 'NON ENREGISTRÉE';
        }
        
        if (isset($wp_filter['wp_ajax_pooltracker_test'])) {
            $ajax_actions['pooltracker_test'] = 'ENREGISTRÉE';
        } else {
            $ajax_actions['pooltracker_test'] = 'NON ENREGISTRÉE';
        }
        
        $debug_info['ajax_actions'] = $ajax_actions;
        
        // 7. POST DATA
        $debug_info['request'] = array(
            'method' => $_SERVER['REQUEST_METHOD'],
            'action' => $_POST['action'] ?? 'AUCUNE',
            'post_data' => $_POST,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        );
        
        // LOG TOUT
        error_log('POOLTRACKER DEBUG COMPLET:');
        foreach ($debug_info as $section => $data) {
            error_log("[$section]: " . print_r($data, true));
        }
        
        // RÉPONSE AJAX
        wp_send_json_success(array(
            'message' => 'Debug système complet',
            'debug_data' => $debug_info,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Action de test AJAX
     */
    public function ajax_test() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '');
        
        if (!wp_verify_nonce($nonce, 'pooltracker_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Test AJAX réussi !',
            'timestamp' => current_time('mysql'),
            'user_authenticated' => $this->auth0_manager->is_user_authenticated(),
            'current_user_id' => $this->get_current_user_id()
        ));
    }
    
    /**
     * Debug : lister les actions enregistrées
     */
    private function get_registered_actions() {
        return array(
            'pooltracker_get_profile',
            'pooltracker_test', 
            'pooltracker_save_measurement',
            'pooltracker_get_tests',
            'pooltracker_update_profile',
            'pooltracker_get_chart_data',
            'pooltracker_get_ai_advice',
            'pooltracker_debug_system',
            'pool_save_measurement',
            'pool_get_user_tests',
            'pool_update_measurement',
            'pool_delete_measurement',
            'pool_export_tests',
            'pool_get_user_data',
            'pool_update_profile',
            'pool_get_chart_data',
            'pool_get_ai_advice',
            'pool_mark_alert_read'
        );
    }
    
    /**
     * Vérifier l'authentification pour une requête AJAX
     */
    private function check_auth() {
        // Vérifier le nonce (peut être 'nonce' ou '_wpnonce')
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '');
        
        if (!wp_verify_nonce($nonce, 'pooltracker_nonce')) {
            error_log('PoolTracker AJAX: Nonce invalide - ' . $nonce);
            wp_send_json_error('Accès non autorisé - Nonce invalide');
            return false;
        }
        
        // BYPASS POUR LES ADMINS WORDPRESS
        if (current_user_can('manage_options')) {
            error_log('PoolTracker AJAX: Admin WordPress - bypass Auth0');
            return true;
        }
        
        // VÉRIFICATION AUTH0 NORMALE
        if (!$this->auth0_manager->is_user_authenticated()) {
            error_log('PoolTracker AJAX: Utilisateur non authentifié');
            wp_send_json_error('Accès non autorisé - Non connecté');
            return false;
        }
        
        error_log('PoolTracker AJAX: Authentification OK');
        return true;
    }
    
    /**
     * Récupérer l'ID utilisateur (Auth0 ou WordPress admin)
     */
    private function get_current_user_id() {
        // Si admin WordPress, utiliser l'ID WordPress
        if (current_user_can('manage_options')) {
            return get_current_user_id();
        }
        
        // Sinon utiliser Auth0
        return $this->auth0_manager->get_current_user_id();
    }
    
    // =====================================
    // GESTION DES MESURES
    // =====================================
    
    /**
     * Sauvegarder une nouvelle mesure
     */
    public function save_measurement() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
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
    
    /**
     * Récupérer les tests utilisateur avec pagination
     */
    public function get_user_tests_paginated() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
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
    
    /**
     * Mettre à jour une mesure
     */
    public function update_measurement() {
        if (!$this->check_auth()) return;
        
        global $wpdb;
        $measurement_id = intval($_POST['measurement_id']);
        $user_id = $this->get_current_user_id();
        
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
    
    /**
     * Supprimer une mesure
     */
    public function delete_measurement() {
        if (!$this->check_auth()) return;
        
        global $wpdb;
        $measurement_id = intval($_POST['measurement_id']);
        $user_id = $this->get_current_user_id();
        
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
    
    /**
     * Exporter les tests en CSV
     */
    public function export_tests_csv() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
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
    
    // =====================================
    // DASHBOARD ET DONNÉES UTILISATEUR
    // =====================================
    
    /**
     * Récupérer les données du dashboard utilisateur
     */
    public function get_user_dashboard_data() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
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
            'alerts' => $active_alerts,
            'user_id' => $user_id,
            'is_admin' => current_user_can('manage_options')
        ));
    }
    
    /**
     * Mettre à jour le profil utilisateur
     */
    public function update_user_profile() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
        global $wpdb;
        
        $table_users = $wpdb->prefix . 'pool_users';
        
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
            $table_users,
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
    
    /**
     * Récupérer les données pour les graphiques
     */
    public function get_chart_data() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
        $days = intval($_POST['days']) ?: 30;
        
        $measurements = $this->get_user_measurements($user_id, $days);
        
        wp_send_json_success(array(
            'measurements' => $measurements,
            'period' => $days
        ));
    }
    
    /**
     * Récupérer les mesures d'un utilisateur
     */
    private function get_user_measurements($user_id, $days_limit = 30) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} 
             WHERE user_id = %d 
             AND test_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             ORDER BY test_date DESC, test_time DESC",
            $user_id, $days_limit
        ));
    }
    
    // =====================================
    // IA ET CONSEILS
    // =====================================
    
    /**
     * Générer un conseil IA personnalisé
     */
    public function get_personalized_ai_advice() {
        if (!$this->check_auth()) return;
        
        $user_id = $this->get_current_user_id();
        
        // Analyser les dernières données pour un conseil personnalisé
        $recent_measurements = $this->get_user_measurements($user_id, 7);
        $advice = $this->generate_personalized_advice($recent_measurements);
        
        wp_send_json_success(array(
            'advice' => $advice
        ));
    }
    
    /**
     * Générer un conseil personnalisé basé sur les mesures
     */
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
    
    // =====================================
    // GESTION DES ALERTES
    // =====================================
    
    /**
     * Marquer une alerte comme lue
     */
    public function mark_alert_read() {
        if (!$this->check_auth()) return;
        
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
    
    /**
     * Récupérer les alertes d'un utilisateur
     */
    private function get_user_alerts($user_id, $unread_only = false) {
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
    
    /**
     * Générer des alertes automatiques
     */
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
}