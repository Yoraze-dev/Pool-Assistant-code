<?php
/**
 * Template de debug PoolTracker
 * Page de diagnostic et test de session
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div style="max-width: 1000px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 10px; font-family: monospace;">
    <h2 style="color: #e74c3c;">🔧 PoolTracker - Debug Session Live</h2>
    
    <div style="margin-bottom: 20px;">
        <button id="refresh-debug" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">🔄 Actualiser</button>
        <button id="test-auth" style="background: #e67e22; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">🧪 Test Auth</button>
        <button id="force-logout" style="background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">🚪 Force Logout</button>
        <button id="clear-session" style="background: #f39c12; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">🧹 Clear Session</button>
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
        • Session Status: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : (session_status() === PHP_SESSION_NONE ? 'NONE' : 'DISABLED'); ?><br>
        • Session ID: <?php echo session_id() ?: 'AUCUN'; ?><br>
        • PoolTracker User ID: <?php echo $_SESSION['pooltracker_user_id'] ?? 'ABSENT'; ?><br>
        • Is Authenticated: <?php echo pooltracker_is_user_authenticated() ? 'OUI' : 'NON'; ?><br>
        • AJAX URL: <?php echo admin_url('admin-ajax.php'); ?><br>
        • Current Time: <?php echo current_time('mysql'); ?><br>
        • WordPress Version: <?php echo get_bloginfo('version'); ?>
    </div>
    
    <!-- Tests de connectivité -->
    <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
        <strong>🌐 Tests de connectivité:</strong><br>
        <button id="test-ajax" style="background: #2196f3; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin: 5px;">Test AJAX</button>
        <button id="test-nonce" style="background: #9c27b0; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin: 5px;">Test Nonce</button>
        <button id="test-db" style="background: #4caf50; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin: 5px;">Test DB</button>
        <div id="connectivity-results" style="margin-top: 10px; font-family: monospace; font-size: 12px;"></div>
    </div>
    
    <!-- Configuration Auth0 -->
    <div style="margin-top: 20px; padding: 15px; background: #fce4ec; border-radius: 5px;">
        <strong>🔐 Configuration Auth0:</strong><br>
        • Domain: <?php echo get_option('pooltracker_auth0_domain', 'NON CONFIGURÉ'); ?><br>
        • Client ID: <?php echo get_option('pooltracker_auth0_client_id', 'NON CONFIGURÉ') ? substr(get_option('pooltracker_auth0_client_id'), 0, 10) . '...' : 'NON CONFIGURÉ'; ?><br>
        • Callback URL: <?php echo home_url('/espace-client/'); ?><br>
        • Login URL: <?php echo home_url('/connexion/'); ?>
    </div>
    
    <!-- Logs récents -->
    <div style="margin-top: 20px; padding: 15px; background: #f3e5f5; border-radius: 5px;">
        <strong>📝 Actions rapides:</strong><br>
        <button id="simulate-login" style="background: #673ab7; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin: 5px;">Simuler connexion</button>
        <button id="check-tables" style="background: #ff9800; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin: 5px;">Vérifier tables</button>
        <button id="export-debug" style="background: #795548; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin: 5px;">Export debug</button>
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
    
    // Event listeners
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
    
    document.getElementById('clear-session').addEventListener('click', function() {
        if (confirm('Nettoyer complètement la session ?')) {
            sessionStorage.clear();
            localStorage.clear();
            alert('Session storage nettoyé');
            refreshDebug();
        }
    });
    
    // Tests de connectivité
    document.getElementById('test-ajax').addEventListener('click', function() {
        testConnectivity('AJAX');
    });
    
    document.getElementById('test-nonce').addEventListener('click', function() {
        testConnectivity('Nonce');
    });
    
    document.getElementById('test-db').addEventListener('click', function() {
        testConnectivity('Database');
    });
    
    function testConnectivity(type) {
        var resultsDiv = document.getElementById('connectivity-results');
        resultsDiv.innerHTML += '<div>Test ' + type + ' en cours...</div>';
        
        var testPromise;
        
        if (type === 'AJAX') {
            testPromise = fetch(debugAjaxUrl, { method: 'POST' });
        } else if (type === 'Nonce') {
            testPromise = fetch(debugAjaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    'action': 'pool_get_auth_status',
                    '_wpnonce': debugNonce
                })
            });
        } else if (type === 'Database') {
            testPromise = fetch(debugAjaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    'action': 'pool_debug_session',
                    '_wpnonce': debugNonce
                })
            });
        }
        
        testPromise
            .then(response => {
                resultsDiv.innerHTML += '<div style="color: green;">✅ ' + type + ' : ' + response.status + ' ' + response.statusText + '</div>';
            })
            .catch(error => {
                resultsDiv.innerHTML += '<div style="color: red;">❌ ' + type + ' : ' + error.message + '</div>';
            });
    }
    
    // Auto-refresh initial
    refreshDebug();
});
</script>