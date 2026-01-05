<?php
/**
 * Gestionnaire des utilisateurs et profils
 * Logique métier pour les utilisateurs PoolTracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class PoolTracker_User_Manager {
    
    private $table_users;
    private $table_auth0_users;
    private $table_measurements;
    
    public function __construct() {
        global $wpdb;
        
        $this->table_users = $wpdb->prefix . 'pool_users';
        $this->table_auth0_users = $wpdb->prefix . 'pool_auth0_users';
        $this->table_measurements = $wpdb->prefix . 'pool_measurements';
    }
    
    /**
     * Récupérer le profil d'un utilisateur
     */
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
    
    /**
     * Créer un profil par défaut pour un utilisateur
     */
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
    
    /**
     * Mettre à jour le profil d'un utilisateur
     */
    public function update_user_profile($user_id, $profile_data) {
        global $wpdb;
        
        $data = array(
            'pool_volume' => !empty($profile_data['pool_volume']) ? floatval($profile_data['pool_volume']) : null,
            'pool_treatment_type' => sanitize_text_field($profile_data['pool_treatment_type']),
            'pool_filtration_type' => sanitize_text_field($profile_data['pool_filtration_type']),
            'pool_depth_avg' => !empty($profile_data['pool_depth_avg']) ? floatval($profile_data['pool_depth_avg']) : null,
            'pool_shape' => sanitize_text_field($profile_data['pool_shape']),
            'has_cover' => !empty($profile_data['has_cover']) ? 1 : 0,
            'has_heat_pump' => !empty($profile_data['has_heat_pump']) ? 1 : 0,
            'filtration_hours' => !empty($profile_data['filtration_hours']) ? intval($profile_data['filtration_hours']) : 8
        );
        
        $result = $wpdb->update(
            $this->table_users,
            $data,
            array('user_id' => $user_id),
            array('%f', '%s', '%s', '%f', '%s', '%d', '%d', '%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Récupérer les statistiques d'un utilisateur
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        
        // Nombre total de tests
        $total_tests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_measurements} WHERE user_id = %d",
            $user_id
        ));
        
        // Dernier test
        $latest_measurement = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} 
             WHERE user_id = %d 
             ORDER BY test_date DESC, test_time DESC 
             LIMIT 1",
            $user_id
        ));
        
        // Moyennes sur les 30 derniers jours
        $averages = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                AVG(ph_value) as avg_ph,
                AVG(chlorine_mg_l) as avg_chlorine,
                AVG(temperature_c) as avg_temperature
             FROM {$this->table_measurements} 
             WHERE user_id = %d 
             AND test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            $user_id
        ));
        
        // Calcul des jours depuis le dernier test
        $days_since_last_test = null;
        if ($latest_measurement && $latest_measurement->test_date) {
            $last_test_date = new DateTime($latest_measurement->test_date);
            $today = new DateTime();
            $days_since_last_test = $today->diff($last_test_date)->days;
        }
        
        return array(
            'total_tests' => intval($total_tests),
            'current_ph' => $latest_measurement ? $latest_measurement->ph_value : null,
            'current_chlorine' => $latest_measurement ? $latest_measurement->chlorine_mg_l : null,
            'current_temperature' => $latest_measurement ? $latest_measurement->temperature_c : null,
            'last_test_date' => $latest_measurement ? $latest_measurement->test_date : null,
            'days_since_last_test' => $days_since_last_test,
            'avg_ph_30d' => $averages ? round($averages->avg_ph, 1) : null,
            'avg_chlorine_30d' => $averages ? round($averages->avg_chlorine, 1) : null,
            'avg_temperature_30d' => $averages ? round($averages->avg_temperature, 1) : null
        );
    }
    
    /**
     * Récupérer les mesures récentes d'un utilisateur
     */
    public function get_recent_measurements($user_id, $limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_measurements} 
             WHERE user_id = %d 
             ORDER BY test_date DESC, test_time DESC 
             LIMIT %d",
            $user_id, $limit
        ));
    }
    
    /**
     * Vérifier si un utilisateur a des données de piscine
     */
    public function user_has_pool_data($user_id) {
        $profile = $this->get_user_profile($user_id);
        
        return $profile && (
            !empty($profile->pool_volume) ||
            !empty($profile->pool_treatment_type) ||
            !empty($profile->pool_filtration_type)
        );
    }
    
    /**
     * Générer un contexte utilisateur pour l'IA
     */
    public function generate_ai_context($user_id) {
        $profile = $this->get_user_profile($user_id);
        $stats = $this->get_user_stats($user_id);
        $recent_measurements = $this->get_recent_measurements($user_id, 5);
        
        $context = "\n\n🏊‍♂️ PROFIL UTILISATEUR PERSONNALISÉ :\n";
        $context .= "=====================================\n";
        
        // Informations de la piscine
        if ($profile && $profile->pool_volume) {
            $context .= "• Volume piscine : {$profile->pool_volume}m³\n";
        }
        if ($profile && $profile->pool_treatment_type) {
            $context .= "• Type traitement : {$profile->pool_treatment_type}\n";
        }
        if ($profile && $profile->pool_filtration_type) {
            $context .= "• Filtration : {$profile->pool_filtration_type}\n";
        }
        if ($profile && $profile->filtration_hours) {
            $context .= "• Heures filtration/jour : {$profile->filtration_hours}h\n";
        }
        
        // Statistiques
        $context .= "\n📊 STATISTIQUES :\n";
        $context .= "• Total tests : {$stats['total_tests']}\n";
        if ($stats['days_since_last_test'] !== null) {
            $context .= "• Jours depuis dernier test : {$stats['days_since_last_test']}\n";
        }
        if ($stats['avg_ph_30d']) {
            $context .= "• pH moyen (30j) : {$stats['avg_ph_30d']}\n";
        }
        if ($stats['avg_chlorine_30d']) {
            $context .= "• Chlore moyen (30j) : {$stats['avg_chlorine_30d']} mg/L\n";
        }
        
        // Dernières mesures
        if (!empty($recent_measurements)) {
            $context .= "\n📋 DERNIÈRES MESURES :\n";
            foreach ($recent_measurements as $measure) {
                $context .= "• {$measure->test_date} : pH {$measure->ph_value}, Cl {$measure->chlorine_mg_l}mg/L";
                if ($measure->temperature_c) {
                    $context .= ", {$measure->temperature_c}°C";
                }
                if ($measure->notes) {
                    $context .= " - " . substr($measure->notes, 0, 50);
                }
                $context .= "\n";
            }
        }
        
        $context .= "=====================================\n";
        $context .= "Utilise ces données pour des conseils précis et personnalisés.\n";
        
        return $context;
    }
    
    /**
     * Calculer l'indice de santé de la piscine
     */
    public function calculate_pool_health_index($user_id) {
        $recent_measurements = $this->get_recent_measurements($user_id, 5);
        
        if (empty($recent_measurements)) {
            return null;
        }
        
        $total_score = 0;
        $count = 0;
        
        foreach ($recent_measurements as $measurement) {
            $score = 0;
            $factors = 0;
            
            // Score pH (40% du total)
            if ($measurement->ph_value) {
                $ph = floatval($measurement->ph_value);
                if ($ph >= 7.0 && $ph <= 7.4) {
                    $score += 40; // pH parfait
                } elseif ($ph >= 6.8 && $ph <= 7.6) {
                    $score += 30; // pH acceptable
                } elseif ($ph >= 6.5 && $ph <= 8.0) {
                    $score += 20; // pH à surveiller
                } else {
                    $score += 5; // pH problématique
                }
                $factors += 40;
            }
            
            // Score Chlore (40% du total)
            if ($measurement->chlorine_mg_l) {
                $chlorine = floatval($measurement->chlorine_mg_l);
                if ($chlorine >= 0.5 && $chlorine <= 2.0) {
                    $score += 40; // Chlore parfait
                } elseif ($chlorine >= 0.3 && $chlorine <= 2.5) {
                    $score += 30; // Chlore acceptable
                } elseif ($chlorine >= 0.1 && $chlorine <= 3.0) {
                    $score += 20; // Chlore à surveiller
                } else {
                    $score += 5; // Chlore problématique
                }
                $factors += 40;
            }
            
            // Score Température (20% du total)
            if ($measurement->temperature_c) {
                $temp = floatval($measurement->temperature_c);
                if ($temp >= 20 && $temp <= 28) {
                    $score += 20; // Température parfaite
                } elseif ($temp >= 15 && $temp <= 32) {
                    $score += 15; // Température acceptable
                } else {
                    $score += 5; // Température extrême
                }
                $factors += 20;
            }
            
            if ($factors > 0) {
                $total_score += ($score / $factors) * 100;
                $count++;
            }
        }
        
        return $count > 0 ? round($total_score / $count) : null;
    }
    
    /**
     * Obtenir un message basé sur l'indice de santé
     */
    public function get_health_message($health_index) {
        if ($health_index === null) {
            return "Ajoutez des tests pour évaluer la santé de votre piscine";
        }
        
        if ($health_index >= 90) {
            return "🌟 Excellente ! Votre piscine est en parfaite santé";
        } elseif ($health_index >= 75) {
            return "✅ Très bonne ! Quelques ajustements mineurs possibles";
        } elseif ($health_index >= 60) {
            return "⚠️ Correcte, mais nécessite votre attention";
        } elseif ($health_index >= 40) {
            return "🔴 Problématique, intervention recommandée";
        } else {
            return "🚨 Critique ! Action immédiate nécessaire";
        }
    }
    
    /**
     * Récupérer les tendances sur une période
     */
    public function get_user_trends($user_id, $days = 30) {
        global $wpdb;
        
        $measurements = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                test_date,
                AVG(ph_value) as avg_ph,
                AVG(chlorine_mg_l) as avg_chlorine,
                AVG(temperature_c) as avg_temperature,
                COUNT(*) as test_count
             FROM {$this->table_measurements} 
             WHERE user_id = %d 
             AND test_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             GROUP BY test_date
             ORDER BY test_date ASC",
            $user_id, $days
        ));
        
        $trends = array(
            'ph' => array(),
            'chlorine' => array(),
            'temperature' => array(),
            'dates' => array()
        );
        
        foreach ($measurements as $measurement) {
            $trends['dates'][] = $measurement->test_date;
            $trends['ph'][] = $measurement->avg_ph ? round($measurement->avg_ph, 1) : null;
            $trends['chlorine'][] = $measurement->avg_chlorine ? round($measurement->avg_chlorine, 1) : null;
            $trends['temperature'][] = $measurement->avg_temperature ? round($measurement->avg_temperature, 1) : null;
        }
        
        return $trends;
    }
    
    /**
     * Vérifier si l'utilisateur est un utilisateur actif
     */
    public function is_active_user($user_id) {
        global $wpdb;
        
        // Utilisateur actif = au moins 1 test dans les 30 derniers jours
        $recent_tests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_measurements} 
             WHERE user_id = %d 
             AND test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            $user_id
        ));
        
        return $recent_tests > 0;
    }
    
    /**
     * Récupérer la liste des utilisateurs pour l'admin
     */
    public function get_users_list($limit = 20, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                au.*,
                pu.pool_volume,
                pu.pool_treatment_type,
                (SELECT COUNT(*) FROM {$this->table_measurements} WHERE user_id = au.pooltracker_user_id) as test_count,
                (SELECT MAX(test_date) FROM {$this->table_measurements} WHERE user_id = au.pooltracker_user_id) as last_test_date
             FROM {$this->table_auth0_users} au
             LEFT JOIN {$this->table_users} pu ON au.pooltracker_user_id = pu.user_id
             ORDER BY au.created_at DESC
             LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
    
    /**
     * Compter le nombre total d'utilisateurs
     */
    public function count_users() {
        global $wpdb;
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_auth0_users}");
    }
    
    /**
     * Supprimer un utilisateur et toutes ses données
     */
    public function delete_user($user_id) {
        global $wpdb;
        
        // Supprimer dans l'ordre pour éviter les contraintes
        $wpdb->delete($this->table_measurements, array('user_id' => $user_id), array('%d'));
        $wpdb->delete($this->table_users, array('user_id' => $user_id), array('%d'));
        $wpdb->delete($this->table_auth0_users, array('pooltracker_user_id' => $user_id), array('%d'));
        
        return true;
    }
}

// =====================================
// FONCTIONS UTILITAIRES GLOBALES
// =====================================

/**
 * Créer un profil par défaut (fonction globale)
 */
function pooltracker_create_default_profile($user_id) {
    static $user_manager = null;
    
    if ($user_manager === null) {
        $user_manager = new PoolTracker_User_Manager();
    }
    
    return $user_manager->create_default_profile($user_id);
}

/**
 * Récupérer le profil utilisateur (fonction globale)
 */
function pooltracker_get_user_profile($user_id) {
    static $user_manager = null;
    
    if ($user_manager === null) {
        $user_manager = new PoolTracker_User_Manager();
    }
    
    return $user_manager->get_user_profile($user_id);
}

/**
 * Récupérer les stats utilisateur (fonction globale)
 */
function pooltracker_get_user_stats($user_id) {
    static $user_manager = null;
    
    if ($user_manager === null) {
        $user_manager = new PoolTracker_User_Manager();
    }
    
    return $user_manager->get_user_stats($user_id);
}