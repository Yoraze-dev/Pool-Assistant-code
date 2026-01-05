<?php
/**
 * Template de la page de connexion PoolTracker
 * Affiche le formulaire Auth0 avec options sociales
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth0_domain = get_option('pooltracker_auth0_domain', '');
$auth0_client_id = get_option('pooltracker_auth0_client_id', '');

// Si la configuration Auth0 est manquante
if (empty($auth0_domain) || empty($auth0_client_id)) {
    ?>
    <div style="padding: 40px; text-align: center; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px;">
        <h3>⚙️ Configuration manquante</h3>
        <p>Les paramètres Auth0 ne sont pas configurés. Contactez l'administrateur.</p>
    </div>
    <?php
    return;
}
?>

<div id="pooltracker-auth" style="max-width: 500px; margin: 50px auto; padding: 30px; background: #f8f9fa; border-radius: 15px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    
    <!-- LOGO -->
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
    outline: none;
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