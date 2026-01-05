<?php
/**
 * Template du dashboard principal PoolTracker
 * Interface complète une fois l'utilisateur connecté
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les infos utilisateur
$user_info = pooltracker_get_current_user_info();
$user_name = $user_info ? $user_info->name : 'Utilisateur';
?>

<!-- INTERFACE POOLTRACKER COMPLÈTE -->
<div id="pooltracker-app">
    
    <!-- HEADER -->
    <div class="pooltracker-header">
        <div class="header-content">
            <h1>Votre Espace PoolTracker</h1>
            <div class="user-info">
                Bonjour <strong><?php echo esc_html($user_name); ?></strong> !
                <button id="logout-btn" class="logout-link">Déconnexion</button>
            </div>
        </div>
    </div>
    
    <!-- NAVIGATION ONGLETS -->
    <nav class="pooltracker-nav">
        <button class="nav-tab active" data-tab="dashboard">📊 Tableau de bord</button>
        <button class="nav-tab" data-tab="measurement">📝 Nouveau test</button>
        <button class="nav-tab" data-tab="tests">📋 Mes tests</button>
        <button class="nav-tab" data-tab="charts">📈 Graphiques</button>
        <button class="nav-tab" data-tab="profile">⚙️ Ma piscine</button>
    </nav>
    
    <!-- CHARGEMENT -->
    <div id="pooltracker-loading" class="loading-container">
        <div class="spinner"></div>
        <p>Chargement de vos données...</p>
    </div>
    
    <!-- CONTENU ONGLETS -->
    <div class="pooltracker-content">
        
        <!-- ONGLET 1: DASHBOARD -->
        <div id="tab-dashboard" class="tab-content active">
            <div class="dashboard-grid">
                
                <!-- Widget conseil IA -->
                <div class="widget-card ai-advice">
                    <h3>🤖 Conseil Pool Assistant</h3>
                    <div id="daily-ai-advice">
                        <div class="ai-loading">💭 Génération de votre conseil personnalisé...</div>
                    </div>
                </div>
                
                <!-- Widget statistiques rapides -->
                <div class="widget-card quick-stats">
                    <h3>📊 Résumé rapide</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number" id="total-tests">-</div>
                            <div class="stat-label">Tests réalisés</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="current-ph">-</div>
                            <div class="stat-label">pH actuel</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="current-chlorine">-</div>
                            <div class="stat-label">Chlore (mg/L)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="days-since-test">-</div>
                            <div class="stat-label">Jours depuis test</div>
                        </div>
                    </div>
                </div>
                
                <!-- Widget derniers tests -->
                <div class="widget-card recent-tests">
                    <h3>📋 Derniers tests</h3>
                    <div id="recent-tests-list">
                        <div class="no-data">Aucun test enregistré</div>
                    </div>
                    <button class="btn-primary" onclick="switchTab('measurement')">
                        ➕ Nouveau test
                    </button>
                </div>
                
                <!-- Widget alertes -->
                <div class="widget-card alerts-widget">
                    <h3>🔔 Alertes</h3>
                    <div id="alerts-list">
                        <div class="no-alerts">✅ Aucune alerte</div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ONGLET 2: NOUVEAU TEST -->
        <div id="tab-measurement" class="tab-content">
            <?php include POOLTRACKER_PATH . 'templates/forms/measurement-form.php'; ?>
        </div>
        
        <!-- ONGLET 3: MES TESTS -->
        <div id="tab-tests" class="tab-content">
            <?php include POOLTRACKER_PATH . 'templates/forms/tests-management.php'; ?>
        </div>
        
        <!-- ONGLET 4: GRAPHIQUES -->
        <div id="tab-charts" class="tab-content">
            <?php include POOLTRACKER_PATH . 'templates/charts.php'; ?>
        </div>
        
        <!-- ONGLET 5: PROFIL -->
        <div id="tab-profile" class="tab-content">
            <?php include POOLTRACKER_PATH . 'templates/forms/profile-form.php'; ?>
        </div>
        
    </div>
    
</div>

<!-- JAVASCRIPT POUR LE DASHBOARD -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 PoolTracker v2.6.0 - Dashboard Principal');
    
    // Vérifier que poolTracker est disponible
    if (typeof poolTracker === 'undefined') {
        console.error('❌ poolTracker non défini');
        return;
    }
    
    // Variables globales
    var currentTab = 'dashboard';
    var poolData = {};
    var charts = {};
    var currentPeriod = 30;
    var testsData = {
        currentPage: 1,
        perPage: 10,
        total: 0,
        filters: {
            search: '',
            dateFrom: '',
            dateTo: ''
        }
    };
    
    // Initialisation de l'app
    initApp();
    
    function initApp() {
        setupTabNavigation();
        setupLogoutButton();
        loadUserData();
        setupForms();
        setupCharts();
        setupTestsManagement();
        
        // Définir la date d'aujourd'hui par défaut
        var today = new Date().toISOString().split('T')[0];
        var now = new Date().toTimeString().split(' ')[0].substring(0,5);
        var dateInput = document.getElementById('test-date');
        var timeInput = document.getElementById('test-time');
        if (dateInput) dateInput.value = today;
        if (timeInput) timeInput.value = now;
    }
    
    // Gestion déconnexion
    function setupLogoutButton() {
        var logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                    this.textContent = 'Déconnexion...';
                    this.disabled = true;
                    
                    fetch(poolTracker.ajax_url, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            'action': 'pool_logout',
                            '_wpnonce': poolTracker.nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = data.data.redirect_to || '/connexion/';
                        } else {
                            alert('Erreur de déconnexion');
                            this.textContent = 'Déconnexion';
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Erreur déconnexion:', error);
                        window.location.href = '/connexion/';
                    });
                }
            });
        }
    }
    
    // Navigation onglets
    function setupTabNavigation() {
        var tabs = document.querySelectorAll('.nav-tab');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].addEventListener('click', function() {
                var tabName = this.dataset.tab;
                switchTab(tabName);
            });
        }
    }
    
    window.switchTab = function(tabName) {
        // Mettre à jour navigation
        var allTabs = document.querySelectorAll('.nav-tab');
        for (var i = 0; i < allTabs.length; i++) {
            allTabs[i].classList.remove('active');
        }
        var activeTab = document.querySelector('[data-tab="' + tabName + '"]');
        if (activeTab) activeTab.classList.add('active');
        
        // Mettre à jour contenu
        var allContent = document.querySelectorAll('.tab-content');
        for (var i = 0; i < allContent.length; i++) {
            allContent[i].classList.remove('active');
        }
        var activeContent = document.getElementById('tab-' + tabName);
        if (activeContent) activeContent.classList.add('active');
        
        currentTab = tabName;
        
        // Actions spécifiques par onglet
        if (tabName === 'charts') {
            setTimeout(function() {
                loadChartData();
            }, 100);
        } else if (tabName === 'profile') {
            loadProfile();
        } else if (tabName === 'tests') {
            loadUserTests();
        }
    }
    
    // Chargement des données utilisateur
    function loadUserData() {
        console.log('📊 Chargement des données utilisateur...');
        
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                'action': 'pool_get_user_data',
                '_wpnonce': poolTracker.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var stats = data.data.stats;
                updateElement('total-tests', stats.total_tests || '0');
                updateElement('current-ph', stats.current_ph || '-');
                updateElement('current-chlorine', stats.current_chlorine || '-');
                
                if (stats.last_test_date) {
                    var daysSince = Math.floor((new Date() - new Date(stats.last_test_date)) / (1000 * 60 * 60 * 24));
                    updateElement('days-since-test', daysSince);
                } else {
                    updateElement('days-since-test', '-');
                }
                
                // Afficher les derniers tests
                displayRecentTests(data.data.recent_measurements);
                
                // Afficher les alertes
                displayAlerts(data.data.alerts);
                
                // Charger conseil IA
                loadAIAdvice();
            }
        })
        .catch(error => {
            console.error('Erreur chargement données:', error);
            // Affichage par défaut en cas d'erreur
            updateElement('total-tests', '0');
            updateElement('current-ph', '-');
            updateElement('current-chlorine', '-');
            updateElement('days-since-test', '-');
            
            updateElement('daily-ai-advice', '💡 Bienvenue dans PoolTracker ! Commencez par configurer votre piscine dans l\'onglet "Ma piscine", puis ajoutez votre premier test.');
        });
    }
    
    function updateElement(id, content) {
        var element = document.getElementById(id);
        if (element) {
            element.textContent = content;
        }
    }
    
    function displayRecentTests(tests) {
        var container = document.getElementById('recent-tests-list');
        if (!container) return;
        
        if (!tests || tests.length === 0) {
            container.innerHTML = '<div class="no-data">Aucun test enregistré</div>';
            return;
        }
        
        var html = '';
        tests.forEach(function(test) {
            html += '<div class="recent-test-item">';
            html += '<strong>' + test.test_date + '</strong> - ';
            html += 'pH: ' + (test.ph_value || '-') + ', ';
            html += 'Cl: ' + (test.chlorine_mg_l || '-') + ' mg/L';
            if (test.temperature_c) {
                html += ', ' + test.temperature_c + '°C';
            }
            html += '</div>';
        });
        container.innerHTML = html;
    }
    
    function displayAlerts(alerts) {
        var container = document.getElementById('alerts-list');
        if (!container) return;
        
        if (!alerts || alerts.length === 0) {
            container.innerHTML = '<div class="no-alerts">✅ Aucune alerte</div>';
            return;
        }
        
        var html = '';
        alerts.forEach(function(alert) {
            var alertClass = alert.alert_category === 'urgent' ? 'alert-urgent' : 'alert-warning';
            html += '<div class="alert-item ' + alertClass + '">';
            html += '<strong>' + alert.alert_title + '</strong><br>';
            html += '<small>' + alert.alert_message + '</small>';
            html += '</div>';
        });
        container.innerHTML = html;
    }
    
    function loadAIAdvice() {
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                'action': 'pool_get_ai_advice',
                '_wpnonce': poolTracker.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateElement('daily-ai-advice', data.data.advice);
            }
        })
        .catch(error => {
            updateElement('daily-ai-advice', '💡 Conseil : Maintenez une routine de tests réguliers pour une piscine parfaite !');
        });
    }
    
    // Configuration des formulaires
    function setupForms() {
        // Formulaire de mesure
        var measurementForm = document.getElementById('measurement-form');
        if (measurementForm) {
            measurementForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveMeasurement();
            });
        }
        
        // Formulaire de profil
        var profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveProfile();
            });
        }
    }
    
    function saveMeasurement() {
        var form = document.getElementById('measurement-form');
        if (!form) return;
        
        var formData = new FormData(form);
        formData.append('action', 'pool_save_measurement');
        formData.append('_wpnonce', poolTracker.nonce);
        
        var submitBtn = document.getElementById('save-measurement');
        if (submitBtn) {
            submitBtn.textContent = 'Enregistrement...';
            submitBtn.disabled = true;
        }
        
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Test enregistré avec succès !');
                form.reset();
                
                // Recharger les données
                loadUserData();
                
                // Retour au dashboard
                switchTab('dashboard');
            } else {
                alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            alert('❌ Erreur de communication : ' + error.message);
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.textContent = '💾 Enregistrer le test';
                submitBtn.disabled = false;
            }
        });
    }
    
    function saveProfile() {
        var form = document.getElementById('profile-form');
        if (!form) return;
        
        var formData = new FormData(form);
        formData.append('action', 'pool_update_profile');
        formData.append('_wpnonce', poolTracker.nonce);
        
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Profil mis à jour avec succès !');
            } else {
                alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            alert('❌ Erreur de communication : ' + error.message);
        });
    }
    
    // Configuration des graphiques
    function setupCharts() {
        var periodBtns = document.querySelectorAll('.period-btn');
        periodBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var activePeriodBtn = document.querySelector('.period-btn.active');
                if (activePeriodBtn) activePeriodBtn.classList.remove('active');
                this.classList.add('active');
                currentPeriod = parseInt(this.dataset.period);
                loadChartData();
            });
        });
    }
    
    function loadChartData() {
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                'action': 'pool_get_chart_data',
                '_wpnonce': poolTracker.nonce,
                'days': currentPeriod
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.measurements) {
                createCharts(data.data.measurements);
            } else {
                createEmptyCharts();
            }
        })
        .catch(error => {
            console.error('Erreur chargement graphiques:', error);
            createEmptyCharts();
        });
    }
    
    function createCharts(measurements) {
        // Préparer les données
        var labels = [];
        var phData = [];
        var chlorineData = [];
        var tempData = [];
        
        measurements.reverse().forEach(function(m) {
            labels.push(m.test_date);
            phData.push(m.ph_value || null);
            chlorineData.push(m.chlorine_mg_l || null);
            tempData.push(m.temperature_c || null);
        });
        
        // Graphique pH
        createChart('ph-chart', 'pH', labels, phData, '#3AA6B9');
        
        // Graphique Chlore
        createChart('chlorine-chart', 'Chlore (mg/L)', labels, chlorineData, '#27AE60');
        
        // Graphique Température
        createChart('temperature-chart', 'Température (°C)', labels, tempData, '#E74C3C');
    }
    
    function createEmptyCharts() {
        var emptyLabels = ['Pas de données'];
        var emptyData = [0];
        
        createChart('ph-chart', 'pH', emptyLabels, emptyData, '#3AA6B9');
        createChart('chlorine-chart', 'Chlore (mg/L)', emptyLabels, emptyData, '#27AE60');
        createChart('temperature-chart', 'Température (°C)', emptyLabels, emptyData, '#E74C3C');
    }
    
    function createChart(canvasId, label, labels, data, color) {
        var ctx = document.getElementById(canvasId);
        if (!ctx || typeof Chart === 'undefined') return;
        
        // Détruire le graphique existant
        if (charts[canvasId]) {
            charts[canvasId].destroy();
        }
        
        charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
    
    function loadProfile() {
        console.log('⚙️ Chargement profil...');
    }
    
    function setupTestsManagement() {
        console.log('📋 Configuration gestion des tests...');
    }
    
    function loadUserTests() {
        console.log('📋 Chargement tests utilisateur...');
        
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                'action': 'pool_get_user_tests',
                '_wpnonce': poolTracker.nonce,
                'page': 1,
                'per_page': 10
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.tests && data.data.tests.length > 0) {
                    displayTestsTable(data.data.tests);
                    var noTests = document.getElementById('no-tests');
                    var testsContainer = document.getElementById('tests-table-container');
                    if (noTests) noTests.style.display = 'none';
                    if (testsContainer) testsContainer.style.display = 'block';
                } else {
                    var noTests = document.getElementById('no-tests');
                    var testsContainer = document.getElementById('tests-table-container');
                    if (noTests) noTests.style.display = 'block';
                    if (testsContainer) testsContainer.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Erreur chargement tests:', error);
            var noTests = document.getElementById('no-tests');
            var testsContainer = document.getElementById('tests-table-container');
            if (noTests) noTests.style.display = 'block';
            if (testsContainer) testsContainer.style.display = 'none';
        });
    }
    
    function displayTestsTable(tests) {
        var tbody = document.getElementById('tests-tbody');
        if (!tbody) return;
        
        var html = '';
        
        tests.forEach(function(test) {
            html += '<tr>';
            html += '<td>' + test.test_date + '</td>';
            html += '<td>' + (test.test_time || '-') + '</td>';
            html += '<td>' + (test.ph_value || '-') + '</td>';
            html += '<td>' + (test.chlorine_mg_l || '-') + '</td>';
            html += '<td>' + (test.temperature_c || '-') + '</td>';
            html += '<td>' + (test.alkalinity || '-') + '</td>';
            html += '<td>' + (test.weather_condition || '-') + '</td>';
            html += '<td>';
            html += '<div class="test-actions">';
            html += '<button class="action-btn delete" onclick="deleteTest(' + test.id + ')">🗑️</button>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        });
        
        tbody.innerHTML = html;
    }
    
    window.deleteTest = function(testId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce test ?')) return;
        
        fetch(poolTracker.ajax_url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                'action': 'pool_delete_measurement',
                '_wpnonce': poolTracker.nonce,
                'measurement_id': testId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Test supprimé');
                loadUserTests();
                loadUserData(); // Recharger aussi le dashboard
            } else {
                alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            alert('❌ Erreur : ' + error.message);
        });
    }
});
</script>
<script>
// Test debug détaillé
console.log('🔍 poolTracker object:', window.poolTracker);

if (window.poolTracker) {
    console.log('Action test simple...');
    
    jQuery.ajax({
        url: window.poolTracker.ajax_url,
        type: 'POST',
        data: {
            action: 'pooltracker_test',
            nonce: window.poolTracker.nonce
        },
        success: function(response) {
            console.log('✅ pooltracker_test réussi:', response);
        },
        error: function(xhr, status, error) {
            console.log('❌ pooltracker_test échoué:');
            console.log('Status:', xhr.status);
            console.log('Response:', xhr.responseText);
            console.log('Ready State:', xhr.readyState);
        }
    });
} else {
    console.log('❌ poolTracker object missing!');
}
</script>