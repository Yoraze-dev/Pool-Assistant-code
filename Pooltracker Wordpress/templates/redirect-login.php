<?php
/**
 * Template de redirection vers la page de connexion
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div style="text-align: center; padding: 40px;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto;">
        <h3 style="color: #3AA6B9; margin-bottom: 15px;">🔐 Accès à votre espace client</h3>
        <p style="color: #666; margin-bottom: 20px;">Redirection vers la page de connexion...</p>
        <div class="spinner" style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-left: 4px solid #3AA6B9; border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto;"></div>
        
        <div style="margin-top: 20px;">
            <p style="font-size: 14px; color: #999;">
                Si la redirection ne fonctionne pas, 
                <a href="/connexion/" style="color: #3AA6B9; text-decoration: none;">cliquez ici</a>
            </p>
        </div>
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

// Redirection de secours après 3 secondes
setTimeout(function() {
    if (window.location.pathname !== '/connexion/') {
        console.log('🔄 PoolTracker: Redirection de secours');
        window.location.href = '/connexion/';
    }
}, 3000);
</script>