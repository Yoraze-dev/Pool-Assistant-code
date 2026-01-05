<?php
/**
 * Template de redirection vers le dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div style="text-align: center; padding: 40px;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto;">
        <h3 style="color: #3AA6B9; margin-bottom: 15px;">✅ Déjà connecté</h3>
        <p style="color: #666; margin-bottom: 20px;">Redirection vers votre espace client...</p>
        <div class="spinner" style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-left: 4px solid #3AA6B9; border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto;"></div>
        
        <div style="margin-top: 20px;">
            <p style="font-size: 14px; color: #999;">
                Si la redirection ne fonctionne pas, 
                <a href="/espace-client/" style="color: #3AA6B9; text-decoration: none;">cliquez ici</a>
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
console.log('🔄 PoolTracker: Redirection automatique vers /espace-client/');

// Redirection immédiate
setTimeout(function() {
    window.location.href = '/espace-client/';
}, 500);

// Redirection de secours après 3 secondes
setTimeout(function() {
    if (window.location.pathname !== '/espace-client/') {
        console.log('🔄 PoolTracker: Redirection de secours');
        window.location.href = '/espace-client/';
    }
}, 3000);
</script>