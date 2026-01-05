<?php
/**
 * Template des graphiques PoolTracker
 * Affichage des tendances et évolutions
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="charts-container">
    <h2>📈 Évolution de votre piscine</h2>
    
    <!-- Contrôles de période -->
    <div class="chart-controls">
        <div class="period-buttons">
            <button class="period-btn active" data-period="7">7 jours</button>
            <button class="period-btn" data-period="30">30 jours</button>
            <button class="period-btn" data-period="90">90 jours</button>
            <button class="period-btn" data-period="365">1 an</button>
        </div>
        
        <div class="chart-options">
            <label class="chart-option">
                <input type="checkbox" id="show-ideal-zones" checked>
                <span>Afficher les zones idéales</span>
            </label>
            <label class="chart-option">
                <input type="checkbox" id="show-trend-lines">
                <span>Lignes de tendance</span>
            </label>
        </div>
    </div>
    
    <!-- Message si pas de données -->
    <div id="no-chart-data" class="no-chart-data" style="display: none;">
        <div class="no-data-content">
            <div class="no-data-icon">📊</div>
            <h3>Pas encore de données</h3>
            <p>Ajoutez quelques tests d'eau pour voir l'évolution de votre piscine en graphiques !</p>
            <button class="btn-primary" onclick="switchTab('measurement')">
                ➕ Ajouter un test
            </button>
        </div>
    </div>
    
    <!-- Grille des graphiques -->
    <div id="charts-grid" class="charts-grid">
        
        <!-- Graphique pH -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>🧪 Évolution du pH</h3>
                <div class="chart-info">
                    <span class="current-value" id="current-ph-display">-</span>
                    <small>Dernière mesure</small>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="ph-chart" width="400" height="250"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item ideal">
                    <span class="legend-color"></span>
                    <span>Zone idéale (7.0-7.4)</span>
                </div>
                <div class="legend-item acceptable">
                    <span class="legend-color"></span>
                    <span>Zone acceptable (6.8-7.6)</span>
                </div>
            </div>
        </div>
        
        <!-- Graphique Chlore -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>💧 Évolution du Chlore</h3>
                <div class="chart-info">
                    <span class="current-value" id="current-chlorine-display">-</span>
                    <small>Dernière mesure (mg/L)</small>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chlorine-chart" width="400" height="250"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item ideal">
                    <span class="legend-color"></span>
                    <span>Zone idéale (0.5-2.0)</span>
                </div>
                <div class="legend-item low">
                    <span class="legend-color"></span>
                    <span>Trop bas (&lt;0.5)</span>
                </div>
                <div class="legend-item high">
                    <span class="legend-color"></span>
                    <span>Trop haut (&gt;2.0)</span>
                </div>
            </div>
        </div>
        
        <!-- Graphique Température -->
        <div class="chart-card full-width">
            <div class="chart-header">
                <h3>🌡️ Évolution de la Température</h3>
                <div class="chart-info">
                    <span class="current-value" id="current-temp-display">-</span>
                    <small>Dernière mesure (°C)</small>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="temperature-chart" width="800" height="250"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item optimal">
                    <span class="legend-color"></span>
                    <span>Température optimale (22-28°C)</span>
                </div>
            </div>
        </div>
        
        <!-- Graphique TAC (si données disponibles) -->
        <div class="chart-card" id="tac-chart-card" style="display: none;">
            <div class="chart-header">
                <h3>🔬 Évolution du TAC</h3>
                <div class="chart-info">
                    <span class="current-value" id="current-tac-display">-</span>
                    <small>Dernière mesure (ppm)</small>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="tac-chart" width="400" height="250"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item ideal">
                    <span class="legend-color"></span>
                    <span>Zone idéale (80-120 ppm)</span>
                </div>
            </div>
        </div>
        
        <!-- Graphique combiné pH/Chlore -->
        <div class="chart-card" id="combined-chart-card">
            <div class="chart-header">
                <h3>⚖️ Balance pH / Chlore</h3>
                <div class="chart-info">
                    <span class="current-value" id="balance-score">-</span>
                    <small>Score d'équilibre</small>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="combined-chart" width="400" height="250"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item ph-line">
                    <span class="legend-color"></span>
                    <span>pH</span>
                </div>
                <div class="legend-item chlorine-line">
                    <span class="legend-color"></span>
                    <span>Chlore</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques de la période -->
    <div class="period-stats">
        <h3>📊 Statistiques de la période</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🧪</div>
                <div class="stat-content">
                    <div class="stat-value" id="ph-avg">-</div>
                    <div class="stat-label">pH moyen</div>
                    <div class="stat-trend" id="ph-trend"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💧</div>
                <div class="stat-content">
                    <div class="stat-value" id="chlorine-avg">-</div>
                    <div class="stat-label">Chlore moyen</div>
                    <div class="stat-trend" id="chlorine-trend"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🌡️</div>
                <div class="stat-content">
                    <div class="stat-value" id="temp-avg">-</div>
                    <div class="stat-label">Temp. moyenne</div>
                    <div class="stat-trend" id="temp-trend"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-value" id="stability-score">-</div>
                    <div class="stat-label">Stabilité</div>
                    <div class="stat-trend" id="stability-trend"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Conseils basés sur les tendances -->
    <div class="trend-insights">
        <h3>💡 Analyses et conseils</h3>
        <div id="chart-insights" class="insights-content">
            <div class="insight-placeholder">
                Ajoutez plus de mesures pour obtenir des analyses de tendances personnalisées.
            </div>
        </div>
    </div>
    
</div>

<script>
// Variables globales pour les graphiques
var chartInstances = {};
var currentChartPeriod = 30;
var chartData = null;
var showIdealZones = true;
var showTrendLines = false;

// Initialisation des graphiques
document.addEventListener('DOMContentLoaded', function() {
    setupChartControls();
});

function setupChartControls() {
    // Boutons de période
    var periodBtns = document.querySelectorAll('.period-btn');
    periodBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Mettre à jour l'état actif
            document.querySelector('.period-btn.active').classList.remove('active');
            this.classList.add('active');
            
            currentChartPeriod = parseInt(this.dataset.period);
            loadChartData();
        });
    });
    
    // Options d'affichage
    var idealZonesCheckbox = document.getElementById('show-ideal-zones');
    var trendLinesCheckbox = document.getElementById('show-trend-lines');
    
    if (idealZonesCheckbox) {
        idealZonesCheckbox.addEventListener('change', function() {
            showIdealZones = this.checked;
            updateChartsDisplay();
        });
    }
    
    if (trendLinesCheckbox) {
        trendLinesCheckbox.addEventListener('change', function() {
            showTrendLines = this.checked;
            updateChartsDisplay();
        });
    }
}

function loadChartData() {
    if (typeof poolTracker === 'undefined') {
        console.error('PoolTracker non défini pour les graphiques');
        return;
    }
    
    // Afficher le loading
    showChartsLoading(true);
    
    fetch(poolTracker.ajax_url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            'action': 'pool_get_chart_data',
            '_wpnonce': poolTracker.nonce,
            'days': currentChartPeriod
        })
    })
    .then(response => response.json())
    .then(data => {
        showChartsLoading(false);
        
        if (data.success && data.data.measurements && data.data.measurements.length > 0) {
            chartData = data.data.measurements;
            createAllCharts();
            calculatePeriodStats();
            generateInsights();
            
            document.getElementById('charts-grid').style.display = 'grid';
            document.getElementById('no-chart-data').style.display = 'none';
        } else {
            document.getElementById('charts-grid').style.display = 'none';
            document.getElementById('no-chart-data').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erreur chargement données graphiques:', error);
        showChartsLoading(false);
        document.getElementById('charts-grid').style.display = 'none';
        document.getElementById('no-chart-data').style.display = 'block';
    });
}

function showChartsLoading(show) {
    var chartsGrid = document.getElementById('charts-grid');
    if (show) {
        chartsGrid.style.opacity = '0.5';
        chartsGrid.style.pointerEvents = 'none';
    } else {
        chartsGrid.style.opacity = '1';
        chartsGrid.style.pointerEvents = 'auto';
    }
}

function createAllCharts() {
    if (!chartData || chartData.length === 0) return;
    
    // Préparer les données
    var labels = [];
    var phData = [];
    var chlorineData = [];
    var tempData = [];
    var tacData = [];
    
    // Trier par date croissante
    var sortedData = chartData.sort(function(a, b) {
        return new Date(a.test_date) - new Date(b.test_date);
    });
    
    sortedData.forEach(function(measurement) {
        labels.push(formatDateLabel(measurement.test_date));
        phData.push(measurement.ph_value ? parseFloat(measurement.ph_value) : null);
        chlorineData.push(measurement.chlorine_mg_l ? parseFloat(measurement.chlorine_mg_l) : null);
        tempData.push(measurement.temperature_c ? parseFloat(measurement.temperature_c) : null);
        tacData.push(measurement.alkalinity ? parseInt(measurement.alkalinity) : null);
    });
    
    // Créer les graphiques individuels
    createpHChart(labels, phData);
    createChlorineChart(labels, chlorineData);
    createTemperatureChart(labels, tempData);
    createCombinedChart(labels, phData, chlorineData);
    
    // Graphique TAC si données disponibles
    var hasTacData = tacData.some(function(value) { return value !== null; });
    if (hasTacData) {
        createTacChart(labels, tacData);
        document.getElementById('tac-chart-card').style.display = 'block';
    } else {
        document.getElementById('tac-chart-card').style.display = 'none';
    }
    
    // Mettre à jour les valeurs actuelles
    updateCurrentValues();
}

function formatDateLabel(dateString) {
    var date = new Date(dateString);
    var today = new Date();
    var diffTime = Math.abs(today - date);
    var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays <= 1) return 'Aujourd\'hui';
    if (diffDays <= 2) return 'Hier';
    if (currentChartPeriod <= 7) return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
    if (currentChartPeriod <= 30) return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: '2-digit' });
}

function createpHChart(labels, data) {
    var ctx = document.getElementById('ph-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    destroyChart('ph-chart');
    
    var datasets = [{
        label: 'pH',
        data: data,
        borderColor: '#3AA6B9',
        backgroundColor: 'rgba(58, 166, 185, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6
    }];
    
    // Zones idéales
    if (showIdealZones) {
        datasets.push({
            label: 'Zone idéale min',
            data: labels.map(() => 7.0),
            borderColor: 'rgba(39, 174, 96, 0.3)',
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false
        });
        
        datasets.push({
            label: 'Zone idéale max',
            data: labels.map(() => 7.4),
            borderColor: 'rgba(39, 174, 96, 0.3)',
            backgroundColor: 'rgba(39, 174, 96, 0.1)',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: '-1'
        });
    }
    
    chartInstances['ph-chart'] = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: getChartOptions('pH', 6.0, 8.5)
    });
}

function createChlorineChart(labels, data) {
    var ctx = document.getElementById('chlorine-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    destroyChart('chlorine-chart');
    
    var datasets = [{
        label: 'Chlore (mg/L)',
        data: data,
        borderColor: '#27AE60',
        backgroundColor: 'rgba(39, 174, 96, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6
    }];
    
    // Zones idéales
    if (showIdealZones) {
        datasets.push({
            label: 'Zone idéale min',
            data: labels.map(() => 0.5),
            borderColor: 'rgba(52, 152, 219, 0.3)',
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false
        });
        
        datasets.push({
            label: 'Zone idéale max',
            data: labels.map(() => 2.0),
            borderColor: 'rgba(52, 152, 219, 0.3)',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: '-1'
        });
    }
    
    chartInstances['chlorine-chart'] = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: getChartOptions('Chlore (mg/L)', 0, 5)
    });
}

function createTemperatureChart(labels, data) {
    var ctx = document.getElementById('temperature-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    destroyChart('temperature-chart');
    
    var datasets = [{
        label: 'Température (°C)',
        data: data,
        borderColor: '#E74C3C',
        backgroundColor: 'rgba(231, 76, 60, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6
    }];
    
    chartInstances['temperature-chart'] = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: getChartOptions('Température (°C)', 10, 35)
    });
}

function createTacChart(labels, data) {
    var ctx = document.getElementById('tac-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    destroyChart('tac-chart');
    
    var datasets = [{
        label: 'TAC (ppm)',
        data: data,
        borderColor: '#9B59B6',
        backgroundColor: 'rgba(155, 89, 182, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6
    }];
    
    if (showIdealZones) {
        datasets.push({
            label: 'Zone idéale min',
            data: labels.map(() => 80),
            borderColor: 'rgba(243, 156, 18, 0.3)',
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false
        });
        
        datasets.push({
            label: 'Zone idéale max',
            data: labels.map(() => 120),
            borderColor: 'rgba(243, 156, 18, 0.3)',
            backgroundColor: 'rgba(243, 156, 18, 0.1)',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: '-1'
        });
    }
    
    chartInstances['tac-chart'] = new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: getChartOptions('TAC (ppm)', 50, 200)
    });
}

function createCombinedChart(labels, phData, chlorineData) {
    var ctx = document.getElementById('combined-chart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    destroyChart('combined-chart');
    
    chartInstances['combined-chart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'pH',
                data: phData,
                borderColor: '#3AA6B9',
                backgroundColor: 'transparent',
                yAxisID: 'y',
                tension: 0.4
            }, {
                label: 'Chlore (mg/L)',
                data: chlorineData,
                borderColor: '#27AE60',
                backgroundColor: 'transparent',
                yAxisID: 'y1',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'pH' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Chlore (mg/L)' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

function getChartOptions(title, minY, maxY) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: { display: false }
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
        },
        elements: {
            point: {
                hoverBackgroundColor: '#fff',
                hoverBorderWidth: 2
            }
        }
    };
}

function destroyChart(chartId) {
    if (chartInstances[chartId]) {
        chartInstances[chartId].destroy();
        delete chartInstances[chartId];
    }
}

function updateChartsDisplay() {
    if (chartData) {
        createAllCharts();
    }
}

function updateCurrentValues() {
    if (!chartData || chartData.length === 0) return;
    
    var latest = chartData[chartData.length - 1];
    
    updateElement('current-ph-display', latest.ph_value || '-');
    updateElement('current-chlorine-display', latest.chlorine_mg_l || '-');
    updateElement('current-temp-display', latest.temperature_c ? latest.temperature_c + '°C' : '-');
    updateElement('current-tac-display', latest.alkalinity || '-');
}

function calculatePeriodStats() {
    if (!chartData || chartData.length === 0) return;
    
    var phValues = chartData.filter(d => d.ph_value).map(d => parseFloat(d.ph_value));
    var chlorineValues = chartData.filter(d => d.chlorine_mg_l).map(d => parseFloat(d.chlorine_mg_l));
    var tempValues = chartData.filter(d => d.temperature_c).map(d => parseFloat(d.temperature_c));
    
    // Moyennes
    updateElement('ph-avg', phValues.length ? (phValues.reduce((a, b) => a + b) / phValues.length).toFixed(1) : '-');
    updateElement('chlorine-avg', chlorineValues.length ? (chlorineValues.reduce((a, b) => a + b) / chlorineValues.length).toFixed(1) : '-');
    updateElement('temp-avg', tempValues.length ? (tempValues.reduce((a, b) => a + b) / tempValues.length).toFixed(1) + '°C' : '-');
    
    // Score de stabilité (basé sur la variance)
    var stabilityScore = calculateStabilityScore(phValues, chlorineValues);
    updateElement('stability-score', stabilityScore + '%');
}

function calculateStabilityScore(phValues, chlorineValues) {
    if (phValues.length < 2 || chlorineValues.length < 2) return '-';
    
    // Calculer les variances
    var phVariance = calculateVariance(phValues);
    var chlorineVariance = calculateVariance(chlorineValues);
    
    // Score basé sur la stabilité (plus la variance est faible, plus le score est élevé)
    var phStability = Math.max(0, 100 - (phVariance * 1000));
    var chlorineStability = Math.max(0, 100 - (chlorineVariance * 500));
    
    return Math.round((phStability + chlorineStability) / 2);
}

function calculateVariance(values) {
    var mean = values.reduce((a, b) => a + b) / values.length;
    var squaredDiffs = values.map(value => Math.pow(value - mean, 2));
    return squaredDiffs.reduce((a, b) => a + b) / values.length;
}

function generateInsights() {
    var insights = [];
    
    if (!chartData || chartData.length < 3) {
        document.getElementById('chart-insights').innerHTML = 
            '<div class="insight-placeholder">Ajoutez plus de mesures pour obtenir des analyses de tendances personnalisées.</div>';
        return;
    }
    
    // Analyser les tendances pH
    var phTrend = analyzeTrend('ph_value');
    if (phTrend.trend !== 'stable') {
        var icon = phTrend.trend === 'increasing' ? '📈' : '📉';
        var direction = phTrend.trend === 'increasing' ? 'augmentation' : 'diminution';
        insights.push(`${icon} <strong>pH</strong> : Tendance à l'${direction} sur la période (${phTrend.strength})`);
    }
    
    // Analyser les tendances chlore
    var chlorineTrend = analyzeTrend('chlorine_mg_l');
    if (chlorineTrend.trend !== 'stable') {
        var icon = chlorineTrend.trend === 'increasing' ? '📈' : '📉';
        var direction = chlorineTrend.trend === 'increasing' ? 'augmentation' : 'diminution';
        insights.push(`${icon} <strong>Chlore</strong> : Tendance à l'${direction} sur la période (${chlorineTrend.strength})`);
    }
    
    // Recommandations
    var latest = chartData[chartData.length - 1];
    if (latest.ph_value && latest.ph_value < 7.0) {
        insights.push('💡 <strong>Recommandation</strong> : Votre pH est bas, ajoutez du pH+ pour optimiser l\'efficacité du chlore');
    } else if (latest.ph_value && latest.ph_value > 7.4) {
        insights.push('💡 <strong>Recommandation</strong> : Votre pH est élevé, ajoutez du pH- pour éviter les dépôts calcaires');
    }
    
    if (insights.length === 0) {
        insights.push('✅ <strong>Excellente stabilité</strong> : Vos paramètres sont bien équilibrés, continuez ainsi !');
    }
    
    document.getElementById('chart-insights').innerHTML = 
        '<div class="insights-list">' + 
        insights.map(insight => '<div class="insight-item">' + insight + '</div>').join('') + 
        '</div>';
}

function analyzeTrend(parameter) {
    var values = chartData.filter(d => d[parameter]).map(d => parseFloat(d[parameter]));
    if (values.length < 3) return { trend: 'stable', strength: 'insuffisant' };
    
    // Calcul simple de la tendance (régression linéaire basique)
    var n = values.length;
    var sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;
    
    for (var i = 0; i < n; i++) {
        sumX += i;
        sumY += values[i];
        sumXY += i * values[i];
        sumXX += i * i;
    }
    
    var slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    var strength = Math.abs(slope) > 0.1 ? 'forte' : Math.abs(slope) > 0.05 ? 'modérée' : 'faible';
    
    if (Math.abs(slope) < 0.02) return { trend: 'stable', strength: strength };
    return { 
        trend: slope > 0 ? 'increasing' : 'decreasing', 
        strength: strength 
    };
}

function updateElement(id, content) {
    var element = document.getElementById(id);
    if (element) {
        element.textContent = content;
    }
}

// Auto-chargement des données si on arrive sur l'onglet
if (typeof loadChartData === 'function') {
    // Cette fonction sera appelée depuis le dashboard principal
}
</script>