<?php
/**
 * Interface de gestion des tests utilisateur
 * Affichage, filtrage, pagination, export
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tests-container">
    <h2>📋 Gestion de mes tests</h2>
    
    <!-- Filtres et recherche -->
    <div class="tests-filters">
        <div class="filters-row">
            <div class="filter-group">
                <input type="text" id="search-tests" placeholder="🔍 Rechercher dans mes tests...">
            </div>
            <div class="filter-group">
                <label for="date-from">Du :</label>
                <input type="date" id="date-from" placeholder="Du">
            </div>
            <div class="filter-group">
                <label for="date-to">Au :</label>
                <input type="date" id="date-to" placeholder="Au">
            </div>
            <div class="filter-group">
                <button id="filter-tests" class="btn-primary">Filtrer</button>
                <button id="reset-filters" class="btn-secondary">Reset</button>
            </div>
        </div>
        
        <div class="actions-row">
            <div class="per-page-group">
                <label for="tests-per-page">Affichage :</label>
                <select id="tests-per-page">
                    <option value="10">10 par page</option>
                    <option value="25">25 par page</option>
                    <option value="50">50 par page</option>
                    <option value="100">100 par page</option>
                </select>
            </div>
            <div class="export-group">
                <button id="export-csv" class="btn-export">📥 Export CSV</button>
                <span id="tests-count" class="tests-count">0 tests au total</span>
            </div>
        </div>
    </div>
    
    <!-- Statistiques rapides sur la période filtrée -->
    <div id="tests-stats" class="tests-stats">
        <div class="stat-card">
            <div class="stat-value" id="avg-ph">-</div>
            <div class="stat-label">pH moyen</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="avg-chlorine">-</div>
            <div class="stat-label">Chlore moyen</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="avg-temperature">-</div>
            <div class="stat-label">Température moyenne</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="tests-period">-</div>
            <div class="stat-label">Tests période</div>
        </div>
    </div>
    
    <!-- Loading -->
    <div id="tests-loading" class="tests-loading">
        <div class="spinner"></div>
        <p>Chargement de vos tests...</p>
    </div>
    
    <!-- Table des tests -->
    <div id="tests-table-container" class="tests-table-container">
        <table class="tests-table">
            <thead>
                <tr>
                    <th onclick="sortTests('test_date')" style="cursor: pointer;">Date <span id="sort-date">↕️</span></th>
                    <th onclick="sortTests('test_time')" style="cursor: pointer;">Heure <span id="sort-time">↕️</span></th>
                    <th onclick="sortTests('ph_value')" style="cursor: pointer;">pH <span id="sort-ph">↕️</span></th>
                    <th onclick="sortTests('chlorine_mg_l')" style="cursor: pointer;">Chlore <span id="sort-chlorine">↕️</span></th>
                    <th onclick="sortTests('temperature_c')" style="cursor: pointer;">Temp. <span id="sort-temp">↕️</span></th>
                    <th>TAC</th>
                    <th>Météo</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tests-tbody">
                <!-- Rempli dynamiquement -->
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div id="tests-pagination" class="pagination">
        <!-- Rempli dynamiquement -->
    </div>
    
    <!-- Message si pas de tests -->
    <div id="no-tests" class="no-tests" style="display: none;">
        <div class="no-tests-content">
            <div class="no-tests-icon">📝</div>
            <h3>Aucun test enregistré</h3>
            <p>Commencez par ajouter votre premier test d'eau pour suivre l'évolution de votre piscine !</p>
            <button class="btn-primary" onclick="switchTab('measurement')">
                ➕ Ajouter un test
            </button>
        </div>
    </div>
</div>

<!-- Modal d'édition de test -->
<div id="edit-test-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier le test</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="edit-test-form">
                <input type="hidden" id="edit-test-id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-ph">pH</label>
                        <input type="number" id="edit-ph" min="6.0" max="8.5" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="edit-chlorine">Chlore (mg/L)</label>
                        <input type="number" id="edit-chlorine" min="0" max="5" step="0.1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-temperature">Température (°C)</label>
                        <input type="number" id="edit-temperature" min="5" max="40" step="0.5">
                    </div>
                    <div class="form-group">
                        <label for="edit-alkalinity">TAC</label>
                        <input type="number" id="edit-alkalinity" min="50" max="300">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-notes">Notes</label>
                    <textarea id="edit-notes" rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Variables globales pour la gestion des tests
var testsData = {
    currentPage: 1,
    perPage: 10,
    total: 0,
    filters: {
        search: '',
        dateFrom: '',
        dateTo: ''
    },
    sortBy: 'test_date',
    sortOrder: 'desc'
};

// Initialisation des événements
document.addEventListener('DOMContentLoaded', function() {
    setupTestsEvents();
});

function setupTestsEvents() {
    // Filtres
    var searchInput = document.getElementById('search-tests');
    var dateFrom = document.getElementById('date-from');
    var dateTo = document.getElementById('date-to');
    var filterBtn = document.getElementById('filter-tests');
    var resetBtn = document.getElementById('reset-filters');
    var perPageSelect = document.getElementById('tests-per-page');
    var exportBtn = document.getElementById('export-csv');
    
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            testsData.filters.search = searchInput ? searchInput.value : '';
            testsData.filters.dateFrom = dateFrom ? dateFrom.value : '';
            testsData.filters.dateTo = dateTo ? dateTo.value : '';
            testsData.currentPage = 1;
            loadUserTestsFiltered();
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            testsData.filters = { search: '', dateFrom: '', dateTo: '' };
            testsData.currentPage = 1;
            loadUserTestsFiltered();
        });
    }
    
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            testsData.perPage = parseInt(this.value);
            testsData.currentPage = 1;
            loadUserTestsFiltered();
        });
    }
    
    if (exportBtn) {
        exportBtn.addEventListener('click', exportTestsCSV);
    }
    
    // Recherche en temps réel (debounced)
    if (searchInput) {
        var searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (filterBtn) filterBtn.click();
            }, 500);
        });
    }
    
    // Formulaire d'édition
    var editForm = document.getElementById('edit-test-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveEditedTest();
        });
    }
}

function loadUserTestsFiltered() {
    var loadingDiv = document.getElementById('tests-loading');
    var tableContainer = document.getElementById('tests-table-container');
    var noTestsDiv = document.getElementById('no-tests');
    
    if (loadingDiv) loadingDiv.style.display = 'block';
    if (tableContainer) tableContainer.style.display = 'none';
    if (noTestsDiv) noTestsDiv.style.display = 'none';
    
    var requestData = {
        'action': 'pool_get_user_tests',
        '_wpnonce': poolTracker.nonce,
        'page': testsData.currentPage,
        'per_page': testsData.perPage,
        'search': testsData.filters.search,
        'date_from': testsData.filters.dateFrom,
        'date_to': testsData.filters.dateTo,
        'sort_by': testsData.sortBy,
        'sort_order': testsData.sortOrder
    };
    
    fetch(poolTracker.ajax_url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (loadingDiv) loadingDiv.style.display = 'none';
        
        if (data.success && data.data.tests && data.data.tests.length > 0) {
            testsData.total = data.data.total;
            displayTestsTable(data.data.tests);
            displayPagination(data.data);
            calculateAndDisplayStats(data.data.tests);
            
            if (tableContainer) tableContainer.style.display = 'block';
            if (noTestsDiv) noTestsDiv.style.display = 'none';
            
            // Mettre à jour le compteur
            var testsCount = document.getElementById('tests-count');
            if (testsCount) {
                testsCount.textContent = data.data.total + ' test' + (data.data.total > 1 ? 's' : '') + ' au total';
            }
        } else {
            if (tableContainer) tableContainer.style.display = 'none';
            if (noTestsDiv) noTestsDiv.style.display = 'block';
            
            var testsCount = document.getElementById('tests-count');
            if (testsCount) testsCount.textContent = '0 tests';
        }
    })
    .catch(error => {
        console.error('Erreur chargement tests:', error);
        if (loadingDiv) loadingDiv.style.display = 'none';
        if (tableContainer) tableContainer.style.display = 'none';
        if (noTestsDiv) noTestsDiv.style.display = 'block';
    });
}

function displayTestsTable(tests) {
    var tbody = document.getElementById('tests-tbody');
    if (!tbody) return;
    
    var html = '';
    
    tests.forEach(function(test) {
        html += '<tr>';
        html += '<td><strong>' + test.test_date + '</strong></td>';
        html += '<td>' + (test.test_time || '-') + '</td>';
        html += '<td class="' + getValueClass('ph', test.ph_value) + '">' + (test.ph_value || '-') + '</td>';
        html += '<td class="' + getValueClass('chlorine', test.chlorine_mg_l) + '">' + (test.chlorine_mg_l || '-') + '</td>';
        html += '<td>' + (test.temperature_c ? test.temperature_c + '°C' : '-') + '</td>';
        html += '<td>' + (test.alkalinity || '-') + '</td>';
        html += '<td>' + formatWeather(test.weather_condition) + '</td>';
        html += '<td class="notes-cell">' + formatNotes(test.notes) + '</td>';
        html += '<td>';
        html += '<div class="test-actions">';
        html += '<button class="action-btn edit" onclick="editTest(' + test.id + ')" title="Modifier">✏️</button>';
        html += '<button class="action-btn delete" onclick="deleteTest(' + test.id + ')" title="Supprimer">🗑️</button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
    });
    
    tbody.innerHTML = html;
}

function getValueClass(type, value) {
    if (!value) return '';
    
    if (type === 'ph') {
        var ph = parseFloat(value);
        if (ph >= 7.0 && ph <= 7.4) return 'value-good';
        if (ph >= 6.8 && ph <= 7.6) return 'value-warning';
        return 'value-bad';
    } else if (type === 'chlorine') {
        var cl = parseFloat(value);
        if (cl >= 0.5 && cl <= 2.0) return 'value-good';
        if (cl >= 0.3 && cl <= 2.5) return 'value-warning';
        return 'value-bad';
    }
    
    return '';
}

function formatWeather(weather) {
    if (!weather) return '-';
    
    var weatherIcons = {
        'soleil': '☀️',
        'nuageux': '☁️',
        'pluie': '🌧️',
        'orage': '⛈️',
        'forte_chaleur': '🔥',
        'vent': '💨'
    };
    
    return weatherIcons[weather] || weather;
}

function formatNotes(notes) {
    if (!notes) return '-';
    return notes.length > 30 ? notes.substring(0, 30) + '...' : notes;
}

function displayPagination(data) {
    var paginationDiv = document.getElementById('tests-pagination');
    if (!paginationDiv) return;
    
    var totalPages = data.total_pages;
    var currentPage = data.page;
    
    if (totalPages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }
    
    var html = '';
    
    // Bouton précédent
    if (currentPage > 1) {
        html += '<button class="page-btn" onclick="goToPage(' + (currentPage - 1) + ')">‹ Précédent</button>';
    }
    
    // Pages
    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        html += '<button class="page-btn" onclick="goToPage(1)">1</button>';
        if (startPage > 2) html += '<span class="page-dots">...</span>';
    }
    
    for (var i = startPage; i <= endPage; i++) {
        var activeClass = i === currentPage ? ' active' : '';
        html += '<button class="page-btn' + activeClass + '" onclick="goToPage(' + i + ')">' + i + '</button>';
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span class="page-dots">...</span>';
        html += '<button class="page-btn" onclick="goToPage(' + totalPages + ')">' + totalPages + '</button>';
    }
    
    // Bouton suivant
    if (currentPage < totalPages) {
        html += '<button class="page-btn" onclick="goToPage(' + (currentPage + 1) + ')">Suivant ›</button>';
    }
    
    paginationDiv.innerHTML = html;
}

function calculateAndDisplayStats(tests) {
    if (!tests || tests.length === 0) {
        updateElement('avg-ph', '-');
        updateElement('avg-chlorine', '-');
        updateElement('avg-temperature', '-');
        updateElement('tests-period', '0');
        return;
    }
    
    var phSum = 0, phCount = 0;
    var chlorineSum = 0, chlorineCount = 0;
    var tempSum = 0, tempCount = 0;
    
    tests.forEach(function(test) {
        if (test.ph_value) {
            phSum += parseFloat(test.ph_value);
            phCount++;
        }
        if (test.chlorine_mg_l) {
            chlorineSum += parseFloat(test.chlorine_mg_l);
            chlorineCount++;
        }
        if (test.temperature_c) {
            tempSum += parseFloat(test.temperature_c);
            tempCount++;
        }
    });
    
    updateElement('avg-ph', phCount > 0 ? (phSum / phCount).toFixed(1) : '-');
    updateElement('avg-chlorine', chlorineCount > 0 ? (chlorineSum / chlorineCount).toFixed(1) : '-');
    updateElement('avg-temperature', tempCount > 0 ? (tempSum / tempCount).toFixed(1) + '°C' : '-');
    updateElement('tests-period', tests.length);
}

function goToPage(page) {
    testsData.currentPage = page;
    loadUserTestsFiltered();
}

function sortTests(column) {
    if (testsData.sortBy === column) {
        testsData.sortOrder = testsData.sortOrder === 'asc' ? 'desc' : 'asc';
    } else {
        testsData.sortBy = column;
        testsData.sortOrder = 'desc';
    }
    
    // Mettre à jour les indicateurs visuels
    document.querySelectorAll('[id^="sort-"]').forEach(function(el) {
        el.textContent = '↕️';
    });
    
    var sortIndicator = document.getElementById('sort-' + column.replace('_', '-'));
    if (sortIndicator) {
        sortIndicator.textContent = testsData.sortOrder === 'asc' ? '↗️' : '↘️';
    }
    
    loadUserTestsFiltered();
}

function editTest(testId) {
    // Récupérer les données du test
    fetch(poolTracker.ajax_url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            'action': 'pool_get_measurement',
            '_wpnonce': poolTracker.nonce,
            'measurement_id': testId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            var test = data.data;
            
            // Remplir le formulaire modal
            document.getElementById('edit-test-id').value = testId;
            document.getElementById('edit-ph').value = test.ph_value || '';
            document.getElementById('edit-chlorine').value = test.chlorine_mg_l || '';
            document.getElementById('edit-temperature').value = test.temperature_c || '';
            document.getElementById('edit-alkalinity').value = test.alkalinity || '';
            document.getElementById('edit-notes').value = test.notes || '';
            
            // Afficher la modal
            document.getElementById('edit-test-modal').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erreur chargement test:', error);
        alert('Erreur lors du chargement du test');
    });
}

function closeEditModal() {
    document.getElementById('edit-test-modal').style.display = 'none';
}

function saveEditedTest() {
    var testId = document.getElementById('edit-test-id').value;
    
    var formData = new FormData();
    formData.append('action', 'pool_update_measurement');
    formData.append('_wpnonce', poolTracker.nonce);
    formData.append('measurement_id', testId);
    formData.append('ph_value', document.getElementById('edit-ph').value);
    formData.append('chlorine_mg_l', document.getElementById('edit-chlorine').value);
    formData.append('temperature_c', document.getElementById('edit-temperature').value);
    formData.append('alkalinity', document.getElementById('edit-alkalinity').value);
    formData.append('notes', document.getElementById('edit-notes').value);
    
    fetch(poolTracker.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Test modifié avec succès !');
            closeEditModal();
            loadUserTestsFiltered();
        } else {
            alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        alert('❌ Erreur de communication : ' + error.message);
    });
}

function deleteTest(testId) {
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
            loadUserTestsFiltered();
            // Recharger aussi le dashboard si on est dessus
            if (typeof loadUserData === 'function') {
                loadUserData();
            }
        } else {
            alert('❌ Erreur : ' + (data.data || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        alert('❌ Erreur : ' + error.message);
    });
}

function exportTestsCSV() {
    var exportBtn = document.getElementById('export-csv');
    if (exportBtn) {
        exportBtn.textContent = '📥 Export en cours...';
        exportBtn.disabled = true;
    }
    
    // Créer un formulaire temporaire pour l'export
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = poolTracker.ajax_url;
    form.target = '_blank';
    
    var fields = {
        'action': 'pool_export_tests',
        '_wpnonce': poolTracker.nonce,
        'search': testsData.filters.search,
        'date_from': testsData.filters.dateFrom,
        'date_to': testsData.filters.dateTo
    };
    
    for (var key in fields) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Restaurer le bouton
    setTimeout(function() {
        if (exportBtn) {
            exportBtn.textContent = '📥 Export CSV';
            exportBtn.disabled = false;
        }
    }, 2000);
}

function updateElement(id, content) {
    var element = document.getElementById(id);
    if (element) {
        element.textContent = content;
    }
}
</script>

<style>
.tests-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(58, 166, 185, 0.2);
}

.tests-filters {
    background: rgba(58, 166, 185, 0.1);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.filters-row, .actions-row {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.actions-row {
    justify-content: space-between;
    margin-bottom: 0;
}

.filter-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-group label {
    font-size: 14px;
    color: #2997AA;
    font-weight: 500;
}

.filter-group input, .filter-group select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.filter-group input:focus, .filter-group select:focus {
    outline: none;
    border-color: #3AA6B9;
}

#search-tests {
    min-width: 250px;
}

.tests-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid rgba(58, 166, 185, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-card .stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #3AA6B9;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    font-size: 12px;
    color: #666;
}

.tests-loading {
    text-align: center;
    padding: 40px;
    display: none;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(58, 166, 185, 0.1);
    border-left: 4px solid #3AA6B9;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.tests-table-container {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    margin-bottom: 20px;
}

.tests-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.tests-table th {
    background: linear-gradient(135deg, #3AA6B9, #2997AA);
    color: white;
    padding: 12px 8px;
    text-align: left;
    font-weight: 600;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
}

.tests-table th:hover {
    background: linear-gradient(135deg, #2997AA, #3AA6B9);
}

.tests-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: middle;
}

.tests-table tr:hover {
    background: rgba(58, 166, 185, 0.05);
}

.value-good {
    color: #27AE60;
    font-weight: 600;
}

.value-warning {
    color: #F39C12;
    font-weight: 600;
}

.value-bad {
    color: #E74C3C;
    font-weight: 600;
}

.notes-cell {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.test-actions {
    display: flex;
    gap: 5px;
}

.action-btn {
    background: transparent;
    border: 1px solid #ddd;
    padding: 5px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
}

.action-btn.edit {
    color: #3AA6B9;
    border-color: #3AA6B9;
}

.action-btn.edit:hover {
    background: #3AA6B9;
    color: white;
}

.action-btn.delete {
    color: #dc3545;
    border-color: #dc3545;
}

.action-btn.delete:hover {
    background: #dc3545;
    color: white;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.page-btn {
    background: white;
    border: 1px solid #ddd;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
    font-size: 14px;
}

.page-btn:hover, .page-btn.active {
    background: #3AA6B9;
    color: white;
    border-color: #3AA6B9;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-dots {
    color: #666;
    padding: 0 5px;
}

.no-tests {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-tests-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-tests-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.no-tests h3 {
    color: #2997AA;
    margin-bottom: 15px;
    font-size: 24px;
}

.no-tests p {
    margin-bottom: 25px;
    line-height: 1.6;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-export {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-export:hover {
    background: #218838;
    transform: translateY(-1px);
}

.tests-count {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
    margin: 0;
    color: #3AA6B9;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .tests-container {
        padding: 20px;
    }
    
    .filters-row, .actions-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .filter-group {
        justify-content: stretch;
    }
    
    .filter-group input, .filter-group select {
        flex: 1;
    }
    
    #search-tests {
        min-width: auto;
    }
    
    .tests-stats {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .tests-table-container {
        font-size: 12px;
    }
    
    .tests-table th, .tests-table td {
        padding: 8px 4px;
    }
    
    .test-actions {
        flex-direction: column;
        gap: 3px;
    }
    
    .action-btn {
        font-size: 10px;
        padding: 4px 6px;
    }
    
    .pagination {
        gap: 5px;
    }
    
    .page-btn {
        padding: 6px 8px;
        font-size: 12px;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    
    .modal-body .form-row {
        grid-template-columns: 1fr;
    }
}
</style>