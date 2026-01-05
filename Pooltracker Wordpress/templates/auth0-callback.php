<?php
/**
 * Template de gestion du callback Auth0
 * Traite la réponse d'Auth0 et connecte l'utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}
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
                            
                            callbackLog('Rechargement de la page...', 'info');
                            
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