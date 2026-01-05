<?php
/**
 * Gestionnaire de base de données PoolTracker
 * Création et maintenance des tables
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Créer toutes les tables PoolTracker
 */
function pooltracker_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table utilisateurs Auth0
    $table_auth0_users = $wpdb->prefix . 'pool_auth0_users';
    $sql_auth0 = "CREATE TABLE $table_auth0_users (
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
        UNIQUE KEY pooltracker_user_id (pooltracker_user_id),
        INDEX email (email),
        INDEX provider (provider),
        INDEX created_at (created_at)
    ) $charset_collate;";
    
    // Table profils utilisateurs
    $table_users = $wpdb->prefix . 'pool_users';
    $sql_users = "CREATE TABLE $table_users (
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
        location_region varchar(100) DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        INDEX pool_treatment_type (pool_treatment_type),
        INDEX pool_volume (pool_volume)
    ) $charset_collate;";
    
    // Table mesures
    $table_measurements = $wpdb->prefix . 'pool_measurements';
    $sql_measurements = "CREATE TABLE $table_measurements (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        ph_value decimal(3,1) DEFAULT NULL,
        chlorine_mg_l decimal(4,2) DEFAULT NULL,
        chlorine_total_mg_l decimal(4,2) DEFAULT NULL,
        temperature_c decimal(4,1) DEFAULT NULL,
        alkalinity int(4) DEFAULT NULL,
        hardness int(4) DEFAULT NULL,
        stabilizer int(4) DEFAULT NULL,
        oxygen_mg_l decimal(4,2) DEFAULT NULL,
        copper_mg_l decimal(4,3) DEFAULT NULL,
        iron_mg_l decimal(4,3) DEFAULT NULL,
        notes text DEFAULT NULL,
        test_date date NOT NULL,
        test_time time DEFAULT NULL,
        weather_condition varchar(50) DEFAULT NULL,
        pool_usage varchar(50) DEFAULT NULL,
        test_method varchar(50) DEFAULT 'manual',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX user_id (user_id),
        INDEX test_date (test_date),
        INDEX created_at (created_at),
        INDEX ph_value (ph_value),
        INDEX chlorine_mg_l (chlorine_mg_l)
    ) $charset_collate;";
    
    // Table alertes
    $table_alerts = $wpdb->prefix . 'pool_user_alerts';
    $sql_alerts = "CREATE TABLE $table_alerts (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        alert_type varchar(50) NOT NULL,
        alert_category varchar(20) DEFAULT 'info',
        alert_title varchar(255) NOT NULL,
        alert_message text NOT NULL,
        related_measurement_id int(11) DEFAULT NULL,
        action_required text DEFAULT NULL,
        is_read tinyint(1) DEFAULT 0,
        is_dismissed tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        read_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        INDEX user_id (user_id),
        INDEX alert_type (alert_type),
        INDEX alert_category (alert_category),
        INDEX is_read (is_read),
        INDEX created_at (created_at)
    ) $charset_collate;";
    
    // Table conversations/historique IA
    $table_conversations = $wpdb->prefix . 'pool_user_conversations';
    $sql_conversations = "CREATE TABLE $table_conversations (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        conversation_type varchar(50) DEFAULT 'advice',
        user_message text DEFAULT NULL,
        ai_response text NOT NULL,
        context_data text DEFAULT NULL,
        rating tinyint(1) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX user_id (user_id),
        INDEX conversation_type (conversation_type),
        INDEX created_at (created_at),
        INDEX rating (rating)
    ) $charset_collate;";
    
    // Table log de maintenance
    $table_maintenance = $wpdb->prefix . 'pool_maintenance_log';
    $sql_maintenance = "CREATE TABLE $table_maintenance (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        maintenance_type varchar(100) NOT NULL,
        description text NOT NULL,
        products_used text DEFAULT NULL,
        cost decimal(8,2) DEFAULT NULL,
        maintenance_date date NOT NULL,
        next_maintenance_date date DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX user_id (user_id),
        INDEX maintenance_type (maintenance_type),
        INDEX maintenance_date (maintenance_date),
        INDEX next_maintenance_date (next_maintenance_date)
    ) $charset_collate;";
    
    // Table objectifs utilisateur
    $table_goals = $wpdb->prefix . 'pool_user_goals';
    $sql_goals = "CREATE TABLE $table_goals (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        goal_type varchar(50) NOT NULL,
        target_value decimal(8,2) NOT NULL,
        current_value decimal(8,2) DEFAULT NULL,
        target_date date DEFAULT NULL,
        status varchar(20) DEFAULT 'active',
        description text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX user_id (user_id),
        INDEX goal_type (goal_type),
        INDEX status (status),
        INDEX target_date (target_date)
    ) $charset_collate;";
    
    // Exécuter les requêtes de création
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_auth0);
    dbDelta($sql_users);
    dbDelta($sql_measurements);
    dbDelta($sql_alerts);
    dbDelta($sql_conversations);
    dbDelta($sql_maintenance);
    dbDelta($sql_goals);
    
    // Ajouter la version de la base de données
    update_option('pooltracker_db_version', '2.6.0');
    
    error_log('PoolTracker: Tables créées avec succès');
}

/**
 * Vérifier si les tables existent
 */
function pooltracker_check_tables() {
    global $wpdb;
    
    $tables = array(
        'pool_auth0_users',
        'pool_users', 
        'pool_measurements',
        'pool_user_alerts',
        'pool_user_conversations',
        'pool_maintenance_log',
        'pool_user_goals'
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return false;
        }
    }
    
    return true;
}

/**
 * Mettre à jour la structure des tables si nécessaire
 */
function pooltracker_update_database() {
    $current_version = get_option('pooltracker_db_version', '0.0.0');
    
    if (version_compare($current_version, '2.6.0', '<')) {
        pooltracker_upgrade_to_2_6_0();
    }
}

/**
 * Mise à jour vers la version 2.6.0
 */
function pooltracker_upgrade_to_2_6_0() {
    global $wpdb;
    
    error_log('PoolTracker: Mise à jour BDD vers 2.6.0');
    
    // Ajouter des colonnes manquantes si nécessaire
    $table_measurements = $wpdb->prefix . 'pool_measurements';
    
    // Vérifier et ajouter des colonnes à la table measurements
    $columns_to_add = array(
        'chlorine_total_mg_l' => 'ADD COLUMN chlorine_total_mg_l decimal(4,2) DEFAULT NULL AFTER chlorine_mg_l',
        'stabilizer' => 'ADD COLUMN stabilizer int(4) DEFAULT NULL AFTER hardness',
        'oxygen_mg_l' => 'ADD COLUMN oxygen_mg_l decimal(4,2) DEFAULT NULL AFTER stabilizer',
        'copper_mg_l' => 'ADD COLUMN copper_mg_l decimal(4,3) DEFAULT NULL AFTER oxygen_mg_l',
        'iron_mg_l' => 'ADD COLUMN iron_mg_l decimal(4,3) DEFAULT NULL AFTER copper_mg_l',
        'pool_usage' => 'ADD COLUMN pool_usage varchar(50) DEFAULT NULL AFTER weather_condition',
        'test_method' => 'ADD COLUMN test_method varchar(50) DEFAULT \'manual\' AFTER pool_usage',
        'updated_at' => 'ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
    );
    
    foreach ($columns_to_add as $column => $sql) {
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_measurements LIKE '$column'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_measurements $sql");
            error_log("PoolTracker: Colonne $column ajoutée à $table_measurements");
        }
    }
    
    // Ajouter des index pour optimiser les performances
    $indexes_to_add = array(
        'idx_ph_value' => 'ADD INDEX idx_ph_value (ph_value)',
        'idx_chlorine_mg_l' => 'ADD INDEX idx_chlorine_mg_l (chlorine_mg_l)'
    );
    
    foreach ($indexes_to_add as $index_name => $sql) {
        $index_exists = $wpdb->get_results("SHOW INDEX FROM $table_measurements WHERE Key_name = '$index_name'");
        if (empty($index_exists)) {
            $wpdb->query("ALTER TABLE $table_measurements $sql");
            error_log("PoolTracker: Index $index_name ajouté à $table_measurements");
        }
    }
    
    // Mettre à jour la version
    update_option('pooltracker_db_version', '2.6.0');
    
    error_log('PoolTracker: Mise à jour BDD vers 2.6.0 terminée');
}

/**
 * Nettoyer les données anciennes
 */
function pooltracker_cleanup_old_data() {
    global $wpdb;
    
    // Supprimer les alertes lues de plus de 30 jours
    $table_alerts = $wpdb->prefix . 'pool_user_alerts';
    $deleted_alerts = $wpdb->query(
        "DELETE FROM $table_alerts 
         WHERE is_read = 1 
         AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    // Supprimer les conversations de plus de 90 jours
    $table_conversations = $wpdb->prefix . 'pool_user_conversations';
    $deleted_conversations = $wpdb->query(
        "DELETE FROM $table_conversations 
         WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
    );
    
    if ($deleted_alerts || $deleted_conversations) {
        error_log("PoolTracker: Nettoyage effectué - $deleted_alerts alertes, $deleted_conversations conversations supprimées");
    }
}

/**
 * Obtenir les statistiques globales de la base de données
 */
function pooltracker_get_db_stats() {
    global $wpdb;
    
    $stats = array();
    
    // Compter les utilisateurs
    $table_auth0 = $wpdb->prefix . 'pool_auth0_users';
    $stats['total_users'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_auth0");
    $stats['active_users'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_auth0 WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Compter les tests
    $table_measurements = $wpdb->prefix . 'pool_measurements';
    $stats['total_tests'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_measurements");
    $stats['tests_this_month'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_measurements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Compter les alertes
    $table_alerts = $wpdb->prefix . 'pool_user_alerts';
    $stats['total_alerts'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_alerts");
    $stats['unread_alerts'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_alerts WHERE is_read = 0");
    
    // Compter les profils configurés
    $table_users = $wpdb->prefix . 'pool_users';
    $stats['configured_profiles'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_users WHERE pool_volume IS NOT NULL");
    
    return $stats;
}

/**
 * Optimiser les tables
 */
function pooltracker_optimize_tables() {
    global $wpdb;
    
    $tables = array(
        'pool_auth0_users',
        'pool_users',
        'pool_measurements', 
        'pool_user_alerts',
        'pool_user_conversations',
        'pool_maintenance_log',
        'pool_user_goals'
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $wpdb->query("OPTIMIZE TABLE $table_name");
    }
    
    error_log('PoolTracker: Tables optimisées');
}

/**
 * Sauvegarder les données utilisateur
 */
function pooltracker_backup_user_data($user_id) {
    global $wpdb;
    
    $backup_data = array();
    
    // Profil utilisateur
    $table_users = $wpdb->prefix . 'pool_users';
    $backup_data['profile'] = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_users WHERE user_id = %d",
        $user_id
    ));
    
    // Mesures
    $table_measurements = $wpdb->prefix . 'pool_measurements';
    $backup_data['measurements'] = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_measurements WHERE user_id = %d ORDER BY test_date DESC",
        $user_id
    ));
    
    // Alertes
    $table_alerts = $wpdb->prefix . 'pool_user_alerts';
    $backup_data['alerts'] = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_alerts WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));
    
    // Maintenance
    $table_maintenance = $wpdb->prefix . 'pool_maintenance_log';
    $backup_data['maintenance'] = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_maintenance WHERE user_id = %d ORDER BY maintenance_date DESC",
        $user_id
    ));
    
    return $backup_data;
}

/**
 * Créer les tables lors de l'activation si elles n'existent pas
 */
function pooltracker_maybe_create_tables() {
    if (!pooltracker_check_tables()) {
        pooltracker_create_tables();
    } else {
        pooltracker_update_database();
    }
}

// Hook pour exécuter le nettoyage quotidien
if (wp_next_scheduled('pooltracker_daily_cleanup') === false) {
    wp_schedule_event(time(), 'daily', 'pooltracker_daily_cleanup');
}

add_action('pooltracker_daily_cleanup', 'pooltracker_cleanup_old_data');

// Hook pour optimiser les tables hebdomadairement  
if (wp_next_scheduled('pooltracker_weekly_optimize') === false) {
    wp_schedule_event(time(), 'weekly', 'pooltracker_weekly_optimize');
}

add_action('pooltracker_weekly_optimize', 'pooltracker_optimize_tables');