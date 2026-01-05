/**
 * PoolTracker JavaScript - Logique client principale
 * Version: 2.6.0
 */

(function() {
    'use strict';
    
    // =====================================
    // VARIABLES GLOBALES
    // =====================================
    
    window.PoolTrackerApp = {
        // État de l'application
        state: {
            currentTab: 'dashboard',
            isLoading: false,
            user: null,
            charts: {},
            data: {}
        },
        
        // Configuration
        config: {
            chartPeriod: 30,
            showIdealZones: true,
            showTrendLines: false,
            testsPerPage: 10,
            autoRefreshInterval: 300000 // 5 minutes
        },
        
        // Utilitaires
        utils: {},
        
        // Gestionnaires
        handlers: {},
        
        // API
        api: {}
    };
    
    const app = window.PoolTrackerApp;
    
    // =====================================
    // UTILITAIRES
    // =====================================
    
    app.utils = {
        /**
         * Afficher/masquer un élément
         */
        toggleElement: function(element, show) {
            if (typeof element === 'string') {
                element = document.getElementById(element);
            }
            if (element) {
                element.style.display = show ? 'block' : 'none';
            }
        },
        
        /**
         * Mettre à jour le contenu d'un élément
         */
        updateElement: function(id, content) {
            const element = document.getElementById(id);
            if (element) {
                if (typeof content === 'string') {
                    element.textContent = content;
                } else {
                    element.innerHTML = content;
                }
            }
        },
        
        /**
         * Ajouter une classe CSS à un élément
         */
        addClass: function(element, className) {
            if (typeof element === 'string') {
                element = document.getElementById(element);
            }
            if (element && !element.classList.contains(className)) {
                element.classList.add(className);
            }
        },
        
        /**
         * Supprimer une classe CSS d'un élément
         */
        removeClass: function(element, className) {
            if (typeof element === 'string') {
                element = document.getElementById(element);
            }
            if (element) {
                element.classList.remove(className);
            }
        },
        
        /**
         * Formater une date pour l'affichage
         */
        formatDate: function(dateString, options = {}) {
            const date = new Date(dateString);
            const defaultOptions = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            return date.toLocaleDateString('fr-FR', { ...defaultOptions, ...options });
        },
        
        /**
         * Formater un nombre pour l'affichage
         */
        formatNumber: function(number, decimals = 1) {
            if (number === null || number === undefined || isNaN(number)) {
                return '-';
            }
            return parseFloat(number).toFixed(decimals);
        },
        
        /**
         * Debounce une fonction
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Afficher une notification
         */
        showNotification: function(message, type = 'info') {
            // Créer la notification si elle n'existe pas
            let notification = document.getElementById('pooltracker-notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'pooltracker-notification';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 9999;
                    transform: translateX(400px);
                    transition: transform 0.3s ease;
                    max-width: 400px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                `;
                document.body.appendChild(notification);
            }
            
            // Couleurs selon le type
            const colors = {
                success: '#27AE60',
                error: '#E74C3C',
                warning: '#F39C12',
                info: '#3AA6B9'
            };
            
            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;
            
            // Afficher
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Masquer après 3 secondes
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
            }, 3000);
        },
        
        /**
         * Valider les valeurs de test
         */
        validateTestValues: function(values) {
            const errors = [];
            
            if (values.ph && (values.ph < 6.0 || values.ph > 8.5)) {
                errors.push('pH doit être entre 6.0 et 8.5');
            }
            
            if (values.chlorine && (values.chlorine < 0 || values.chlorine > 10)) {
                errors.push('Chlore doit être entre 0 et 10 mg/L');
            }
            
            if (values.temperature && (values.temperature < 5 || values.temperature > 50)) {
                errors.push('Température doit être entre 5 et 50°C');
            }
            
            if (values.alkalinity && (values.alkalinity < 20 || values.alkalinity > 500)) {
                errors.push('TAC doit être entre 20 et 500 ppm');
            }
            
            return errors;
        },
        
        /**
         * Analyser la qualité d'une valeur
         */
        analyzeValue: function(type, value) {
            if (!value) return 'unknown';
            
            const ranges = {
                ph: {
                    good: [7.0, 7.4],
                    acceptable: [6.8, 7.6],
                    critical: [6.0, 8.5]
                },
                chlorine: {
                    good: [0.5, 2.0],
                    acceptable: [0.3, 2.5],
                    critical: [0.1, 5.0]
                },
                temperature: {
                    good: [22, 28],
                    acceptable: [18, 32],
                    critical: [10, 40]
                },
                alkalinity: {
                    good: [80, 120],
                    acceptable: [60, 150],
                    critical: [40, 200]
                }
            };
            
            const range = ranges[type];
            if (!range) return 'unknown';
            
            const val = parseFloat(value);
            
            if (val >= range.good[0] && val <= range.good[1]) return 'good';
            if (val >= range.acceptable[0] && val <= range.acceptable[1]) return 'acceptable';
            if (val >= range.critical[0] && val <= range.critical[1]) return 'critical';
            
            return 'bad';
        }
    };
    
    // =====================================
    // API ET REQUÊTES
    // =====================================
    
    app.api = {
        /**
         * Requête AJAX générique
         */
        request: function(action, data = {}, options = {}) {
            if (typeof poolTracker === 'undefined') {
                console.error('PoolTracker: poolTracker global non défini');
                return Promise.reject(new Error('Configuration manquante'));
            }
            
            const requestData = {
                action: action,
                _wpnonce: poolTracker.nonce,
                ...data
            };
            
            const defaultOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(requestData)
            };
            
            return fetch(poolTracker.ajax_url, { ...defaultOptions, ...options })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.data || 'Erreur serveur inconnue');
                    }
                    return data.data;
                });
        },
        
        /**
         * Charger les données utilisateur
         */
        loadUserData: function() {
            return this.request('pool_get_user_data');
        },
        
        /**
         * Sauvegarder une mesure
         */
        saveMeasurement: function(formData) {
            return this.request('pool_save_measurement', formData);
        },
        
        /**
         * Charger les données de graphiques
         */
        loadChartData: function(days = 30) {
            return this.request('pool_get_chart_data', { days: days });
        },
        
        /**
         * Charger les tests utilisateur
         */
        loadUserTests: function(params = {}) {
            const defaultParams = {
                page: 1,
                per_page: 10
            };
            return this.request('pool_get_user_tests', { ...defaultParams, ...params });
        },
        
        /**
         * Mettre à jour le profil
         */
        updateProfile: function(profileData) {
            return this.request('pool_update_profile', profileData);
        },
        
        /**
         * Supprimer une mesure
         */
        deleteMeasurement: function(measurementId) {
            return this.request('pool_delete_measurement', { measurement_id: measurementId });
        },
        
        /**
         * Obtenir des conseils IA
         */
        getAIAdvice: function() {
            return this.request('pool_get_ai_advice');
        },
        
        /**
         * Déconnexion
         */
        logout: function() {
            return this.request('pool_logout');
        }
    };
    
    // =====================================
    // GESTIONNAIRES D'ÉVÉNEMENTS
    // =====================================
    
    app.handlers = {
        /**
         * Navigation entre onglets
         */
        tabNavigation: function() {
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.dataset.tab;
                    app.switchTab(tabName);
                });
            });
        },
        
        /**
         * Déconnexion
         */
        logout: function() {
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                        this.textContent = 'Déconnexion...';
                        this.disabled = true;
                        
                        app.api.logout()
                            .then(data => {
                                window.location.href = data.redirect_to || '/connexion/';
                            })
                            .catch(error => {
                                app.utils.showNotification('Erreur de déconnexion', 'error');
                                this.textContent = 'Déconnexion';
                                this.disabled = false;
                            });
                    }
                });
            }
        },
        
        /**
         * Formulaires
         */
        forms: function() {
            // Formulaire de mesure
            const measurementForm = document.getElementById('measurement-form');
            if (measurementForm) {
                measurementForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    app.saveMeasurement();
                });
            }
            
            // Formulaire de profil
            const profileForm = document.getElementById('profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    app.saveProfile();
                });
            }
        },
        
        /**
         * Contrôles des graphiques
         */
        chartControls: function() {
            const periodBtns = document.querySelectorAll('.period-btn');
            periodBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelector('.period-btn.active')?.classList.remove('active');
                    this.classList.add('active');
                    app.config.chartPeriod = parseInt(this.dataset.period);
                    app.loadChartData();
                });
            });
            
            // Options d'affichage des graphiques
            const idealZonesCheckbox = document.getElementById('show-ideal-zones');
            if (idealZonesCheckbox) {
                idealZonesCheckbox.addEventListener('change', function() {
                    app.config.showIdealZones = this.checked;
                    app.updateChartsDisplay();
                });
            }
            
            const trendLinesCheckbox = document.getElementById('show-trend-lines');
            if (trendLinesCheckbox) {
                trendLinesCheckbox.addEventListener('change', function() {
                    app.config.showTrendLines = this.checked;
                    app.updateChartsDisplay();
                });
            }
        },
        
        /**
         * Recherche et filtres
         */
        searchAndFilters: function() {
            const searchInput = document.getElementById('search-tests');
            if (searchInput) {
                const debouncedSearch = app.utils.debounce(() => {
                    app.loadUserTests({ search: searchInput.value });
                }, 500);
                
                searchInput.addEventListener('input', debouncedSearch);
            }
            
            // Autres filtres
            const filterBtn = document.getElementById('filter-tests');
            if (filterBtn) {
                filterBtn.addEventListener('click', app.applyFilters);
            }
            
            const resetBtn = document.getElementById('reset-filters');
            if (resetBtn) {
                resetBtn.addEventListener('click', app.resetFilters);
            }
        },
        
        /**
         * Auto-refresh des données
         */
        autoRefresh: function() {
            if (app.config.autoRefreshInterval > 0) {
                setInterval(() => {
                    if (app.state.currentTab === 'dashboard') {
                        app.loadUserData();
                    }
                }, app.config.autoRefreshInterval);
            }
        }
    };
    
    // =====================================
    // FONCTIONS PRINCIPALES
    // =====================================
    
    /**
     * Initialisation de l'application
     */
    app.init = function() {
        console.log('🚀 PoolTracker App - Initialisation v2.6.0');
        
        // Vérifier la configuration
        if (typeof poolTracker === 'undefined') {
            console.error('❌ Configuration PoolTracker manquante');
            return;
        }
        
        // Initialiser les gestionnaires d'événements
        this.handlers.tabNavigation();
        this.handlers.logout();
        this.handlers.forms();
        this.handlers.chartControls();
        this.handlers.searchAndFilters();
        this.handlers.autoRefresh();
        
        // Charger les données initiales
        this.loadUserData();
        
        // Définir les dates par défaut
        this.setDefaultDates();
        
        console.log('✅ PoolTracker App initialisé');
    };
    
    /**
     * Changer d'onglet
     */
    app.switchTab = function(tabName) {
        // Mettre à jour la navigation
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');
        
        // Mettre à jour le contenu
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabName}`)?.classList.add('active');
        
        this.state.currentTab = tabName;
        
        // Actions spécifiques par onglet
        switch (tabName) {
            case 'charts':
                setTimeout(() => this.loadChartData(), 100);
                break;
            case 'profile':
                this.loadProfile();
                break;
            case 'tests':
                this.loadUserTests();
                break;
        }
    };
    
    /**
     * Charger les données utilisateur
     */
    app.loadUserData = function() {
        if (this.state.isLoading) return;
        
        this.state.isLoading = true;
        
        this.api.loadUserData()
            .then(data => {
                this.updateDashboard(data);
                this.state.data.user = data;
                
                // Charger conseil IA
                this.loadAIAdvice();
            })
            .catch(error => {
                console.error('Erreur chargement données:', error);
                this.setDefaultDashboard();
            })
            .finally(() => {
                this.state.isLoading = false;
            });
    };
    
    /**
     * Mettre à jour le dashboard
     */
    app.updateDashboard = function(data) {
        const stats = data.stats || {};
        
        // Statistiques rapides
        this.utils.updateElement('total-tests', stats.total_tests || '0');
        this.utils.updateElement('current-ph', stats.current_ph || '-');
        this.utils.updateElement('current-chlorine', stats.current_chlorine || '-');
        
        // Jours depuis le dernier test
        if (stats.last_test_date) {
            const daysSince = Math.floor((new Date() - new Date(stats.last_test_date)) / (1000 * 60 * 60 * 24));
            this.utils.updateElement('days-since-test', daysSince);
        } else {
            this.utils.updateElement('days-since-test', '-');
        }
        
        // Tests récents
        this.displayRecentTests(data.recent_measurements || []);
        
        // Alertes
        this.displayAlerts(data.alerts || []);
    };
    
    /**
     * Afficher les tests récents
     */
    app.displayRecentTests = function(tests) {
        const container = document.getElementById('recent-tests-list');
        if (!container) return;
        
        if (tests.length === 0) {
            container.innerHTML = '<div class="no-data">Aucun test enregistré</div>';
            return;
        }
        
        const html = tests.map(test => {
            const quality = this.utils.analyzeValue('ph', test.ph_value);
            const qualityClass = quality === 'good' ? 'value-good' : 
                               quality === 'acceptable' ? 'value-warning' : 'value-bad';
            
            return `
                <div class="recent-test-item">
                    <strong>${this.utils.formatDate(test.test_date)}</strong> - 
                    pH: <span class="${qualityClass}">${test.ph_value || '-'}</span>, 
                    Cl: ${test.chlorine_mg_l || '-'} mg/L
                    ${test.temperature_c ? `, ${test.temperature_c}°C` : ''}
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
    };
    
    /**
     * Afficher les alertes
     */
    app.displayAlerts = function(alerts) {
        const container = document.getElementById('alerts-list');
        if (!container) return;
        
        if (alerts.length === 0) {
            container.innerHTML = '<div class="no-alerts">✅ Aucune alerte</div>';
            return;
        }
        
        const html = alerts.map(alert => {
            const alertClass = alert.alert_category === 'urgent' ? 'alert-urgent' : 'alert-warning';
            return `
                <div class="alert-item ${alertClass}">
                    <strong>${alert.alert_title}</strong><br>
                    <small>${alert.alert_message}</small>
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
    };
    
    /**
     * Charger les conseils IA
     */
    app.loadAIAdvice = function() {
        const container = document.getElementById('daily-ai-advice');
        if (!container) return;
        
        container.innerHTML = '<div class="ai-loading">💭 Génération de votre conseil personnalisé...</div>';
        
        this.api.getAIAdvice()
            .then(data => {
                container.innerHTML = data.advice;
            })
            .catch(error => {
                container.innerHTML = '💡 Conseil : Maintenez une routine de tests réguliers pour une piscine parfaite !';
            });
    };
    
    /**
     * Sauvegarder une mesure
     */
    app.saveMeasurement = function() {
        const form = document.getElementById('measurement-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const values = Object.fromEntries(formData);
        
        // Validation
        const errors = this.utils.validateTestValues(values);
        if (errors.length > 0) {
            this.utils.showNotification('Erreurs: ' + errors.join(', '), 'error');
            return;
        }
        
        const submitBtn = document.getElementById('save-measurement');
        if (submitBtn) {
            submitBtn.textContent = 'Enregistrement...';
            submitBtn.disabled = true;
        }
        
        // Convertir FormData en objet
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        this.api.saveMeasurement(data)
            .then(result => {
                this.utils.showNotification('✅ Test enregistré avec succès !', 'success');
                form.reset();
                this.setDefaultDates();
                this.loadUserData();
                this.switchTab('dashboard');
            })
            .catch(error => {
                this.utils.showNotification('❌ Erreur : ' + error.message, 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.textContent = '💾 Enregistrer le test';
                    submitBtn.disabled = false;
                }
            });
    };
    
    /**
     * Charger les données de graphiques
     */
    app.loadChartData = function() {
        this.api.loadChartData(this.config.chartPeriod)
            .then(data => {
                if (data.measurements && data.measurements.length > 0) {
                    this.createCharts(data.measurements);
                    this.utils.toggleElement('charts-grid', true);
                    this.utils.toggleElement('no-chart-data', false);
                } else {
                    this.utils.toggleElement('charts-grid', false);
                    this.utils.toggleElement('no-chart-data', true);
                }
            })
            .catch(error => {
                console.error('Erreur chargement graphiques:', error);
                this.utils.toggleElement('charts-grid', false);
                this.utils.toggleElement('no-chart-data', true);
            });
    };
    
    /**
     * Créer les graphiques
     */
    app.createCharts = function(measurements) {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js non disponible');
            return;
        }
        
        // Préparer les données
        const labels = [];
        const phData = [];
        const chlorineData = [];
        const tempData = [];
        
        measurements.sort((a, b) => new Date(a.test_date) - new Date(b.test_date));
        
        measurements.forEach(m => {
            labels.push(this.utils.formatDate(m.test_date, { month: 'short', day: 'numeric' }));
            phData.push(m.ph_value ? parseFloat(m.ph_value) : null);
            chlorineData.push(m.chlorine_mg_l ? parseFloat(m.chlorine_mg_l) : null);
            tempData.push(m.temperature_c ? parseFloat(m.temperature_c) : null);
        });
        
        // Créer les graphiques
        this.createChart('ph-chart', 'pH', labels, phData, '#3AA6B9', 6.0, 8.5);
        this.createChart('chlorine-chart', 'Chlore (mg/L)', labels, chlorineData, '#27AE60', 0, 5);
        this.createChart('temperature-chart', 'Température (°C)', labels, tempData, '#E74C3C', 10, 35);
        
        // Mettre à jour les valeurs actuelles
        if (measurements.length > 0) {
            const latest = measurements[measurements.length - 1];
            this.utils.updateElement('current-ph-display', latest.ph_value || '-');
            this.utils.updateElement('current-chlorine-display', latest.chlorine_mg_l || '-');
            this.utils.updateElement('current-temp-display', latest.temperature_c ? latest.temperature_c + '°C' : '-');
        }
    };
    
    /**
     * Créer un graphique individuel
     */
    app.createChart = function(canvasId, label, labels, data, color, minY, maxY) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;
        
        // Détruire le graphique existant
        if (this.state.charts[canvasId]) {
            this.state.charts[canvasId].destroy();
        }
        
        this.state.charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '20',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: minY,
                        max: maxY,
                        grid: { color: 'rgba(0,0,0,0.1)' }
                    },
                    x: {
                        grid: { color: 'rgba(0,0,0,0.1)' }
                    }
                }
            }
        });
    };
    
    /**
     * Définir les dates par défaut
     */
    app.setDefaultDates = function() {
        const today = new Date().toISOString().split('T')[0];
        const now = new Date().toTimeString().split(' ')[0].substring(0, 5);
        
        const dateInput = document.getElementById('test-date');
        const timeInput = document.getElementById('test-time');
        
        if (dateInput) dateInput.value = today;
        if (timeInput) timeInput.value = now;
    };
    
    /**
     * Affichage par défaut en cas d'erreur
     */
    app.setDefaultDashboard = function() {
        this.utils.updateElement('total-tests', '0');
        this.utils.updateElement('current-ph', '-');
        this.utils.updateElement('current-chlorine', '-');
        this.utils.updateElement('days-since-test', '-');
        
        const recentTestsList = document.getElementById('recent-tests-list');
        if (recentTestsList) {
            recentTestsList.innerHTML = '<div class="no-data">Aucun test enregistré</div>';
        }
        
        const alertsList = document.getElementById('alerts-list');
        if (alertsList) {
            alertsList.innerHTML = '<div class="no-alerts">✅ Aucune alerte</div>';
        }
        
        const aiAdvice = document.getElementById('daily-ai-advice');
        if (aiAdvice) {
            aiAdvice.innerHTML = '💡 Bienvenue dans PoolTracker ! Commencez par configurer votre piscine dans l\'onglet "Ma piscine", puis ajoutez votre premier test.';
        }
    };
    
    // =====================================
    // FONCTIONS GLOBALES POUR COMPATIBILITÉ
    // =====================================
    
    // Fonction globale pour changer d'onglet (compatibilité avec templates)
    window.switchTab = function(tabName) {
        app.switchTab(tabName);
    };
    
    // Fonction globale pour supprimer un test (compatibilité avec templates)
    window.deleteTest = function(testId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce test ?')) return;
        
        app.api.deleteMeasurement(testId)
            .then(() => {
                app.utils.showNotification('✅ Test supprimé', 'success');
                app.loadUserTests();
                app.loadUserData();
            })
            .catch(error => {
                app.utils.showNotification('❌ Erreur : ' + error.message, 'error');
            });
    };
    
    // =====================================
    // INITIALISATION AUTOMATIQUE
    // =====================================
    
    // Initialiser l'application quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => app.init());
    } else {
        app.init();
    }
    
})();