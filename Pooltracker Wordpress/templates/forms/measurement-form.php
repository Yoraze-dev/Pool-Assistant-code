<?php
/**
 * Formulaire d'ajout de mesure/test d'eau
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="measurement-container">
    <h2>📝 Nouveau test d'eau</h2>
    
    <form id="measurement-form" class="measurement-form">
        <div class="form-row">
            <div class="form-group">
                <label for="test-date">Date du test</label>
                <input type="date" id="test-date" name="test_date" required>
            </div>
            <div class="form-group">
                <label for="test-time">Heure</label>
                <input type="time" id="test-time" name="test_time">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="ph-value">pH</label>
                <input type="number" id="ph-value" name="ph_value" 
                       min="6.0" max="8.5" step="0.1" placeholder="7.2">
                <small>Valeur idéale : 7.0 - 7.4</small>
            </div>
            <div class="form-group">
                <label for="chlorine-value">Chlore libre (mg/L)</label>
                <input type="number" id="chlorine-value" name="chlorine_mg_l" 
                       min="0" max="5" step="0.1" placeholder="1.0">
                <small>Valeur idéale : 0.5 - 2.0</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="temperature-value">Température (°C)</label>
                <input type="number" id="temperature-value" name="temperature_c" 
                       min="5" max="40" step="0.5" placeholder="25.0">
            </div>
            <div class="form-group">
                <label for="weather-condition">Météo</label>
                <select id="weather-condition" name="weather_condition">
                    <option value="">-- Optionnel --</option>
                    <option value="soleil">Ensoleillé</option>
                    <option value="nuageux">Nuageux</option>
                    <option value="pluie">Pluie</option>
                    <option value="orage">Orage</option>
                    <option value="forte_chaleur">Forte chaleur</option>
                    <option value="vent">Venteux</option>
                </select>
            </div>
        </div>
        
        <!-- Mesures avancées (collapsible) -->
        <div class="advanced-measurements">
            <h4 style="cursor: pointer; color: #3AA6B9; margin: 20px 0 10px 0;" onclick="toggleAdvanced()">
                🔬 Mesures avancées (optionnel) <span id="advanced-toggle">▼</span>
            </h4>
            <div id="advanced-fields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="alkalinity">TAC (ppm)</label>
                        <input type="number" id="alkalinity" name="alkalinity" 
                               min="50" max="300" placeholder="100">
                        <small>Valeur recommandée : 80-120 ppm</small>
                    </div>
                    <div class="form-group">
                        <label for="hardness">Dureté (°f)</label>
                        <input type="number" id="hardness" name="hardness" 
                               min="5" max="50" placeholder="15">
                        <small>Valeur recommandée : 10-25 °f</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="chlorine-total">Chlore total (mg/L)</label>
                        <input type="number" id="chlorine-total" name="chlorine_total_mg_l" 
                               min="0" max="10" step="0.1" placeholder="1.2">
                        <small>Différence avec chlore libre = chloramines</small>
                    </div>
                    <div class="form-group">
                        <label for="stabilizer">Stabilisant (ppm)</label>
                        <input type="number" id="stabilizer" name="stabilizer" 
                               min="0" max="100" placeholder="30">
                        <small>Valeur recommandée : 20-50 ppm</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">📝 Notes et observations</label>
            <textarea id="notes" name="notes" rows="3" 
                      placeholder="Observations, produits ajoutés, état de la piscine..."></textarea>
        </div>
        
        <!-- Suggestions contextuelles -->
        <div id="measurement-suggestions" class="suggestions-box" style="display: none;">
            <h4>Suggestions automatiques</h4>
            <div id="suggestions-content"></div>
        </div>
        
        <button type="submit" class="btn-primary btn-large" id="save-measurement">
            Enregistrer le test
        </button>
    </form>
</div>

<script>
function toggleAdvanced() {
    var fields = document.getElementById('advanced-fields');
    var toggle = document.getElementById('advanced-toggle');
    
    if (fields.style.display === 'none') {
        fields.style.display = 'block';
        toggle.textContent = '▲';
    } else {
        fields.style.display = 'none';
        toggle.textContent = '▼';
    }
}

// Suggestions automatiques basées sur les valeurs saisies
document.addEventListener('DOMContentLoaded', function() {
    var phInput = document.getElementById('ph-value');
    var chlorineInput = document.getElementById('chlorine-value');
    var tempInput = document.getElementById('temperature-value');
    
    function updateSuggestions() {
        var suggestions = [];
        var ph = parseFloat(phInput.value);
        var chlorine = parseFloat(chlorineInput.value);
        var temp = parseFloat(tempInput.value);
        
        // Suggestions pH
        if (!isNaN(ph)) {
            if (ph < 7.0) {
                suggestions.push('🔴 pH trop bas → Ajoutez du pH+ (carbonate de sodium)');
            } else if (ph > 7.4) {
                suggestions.push('🔴 pH trop haut → Ajoutez du pH- (bisulfate de sodium)');
            } else {
                suggestions.push('✅ pH optimal !');
            }
        }
        
        // Suggestions chlore
        if (!isNaN(chlorine)) {
            if (chlorine < 0.5) {
                suggestions.push('🔴 Chlore insuffisant → Traitement choc recommandé');
            } else if (chlorine > 2.0) {
                suggestions.push('⚠️ Chlore élevé → Réduisez le dosage et attendez');
            } else {
                suggestions.push('✅ Taux de chlore correct !');
            }
        }
        
        // Suggestions température
        if (!isNaN(temp)) {
            if (temp > 28) {
                suggestions.push('🌡️ Température élevée → Surveillez l\'évaporation du chlore');
            } else if (temp < 15) {
                suggestions.push('🌡️ Température basse → Ajustez le temps de filtration');
            }
        }
        
        // Afficher les suggestions
        var suggestionsBox = document.getElementById('measurement-suggestions');
        var suggestionsContent = document.getElementById('suggestions-content');
        
        if (suggestions.length > 0) {
            suggestionsContent.innerHTML = suggestions.map(s => '<div class="suggestion-item">' + s + '</div>').join('');
            suggestionsBox.style.display = 'block';
        } else {
            suggestionsBox.style.display = 'none';
        }
    }
    
    // Écouter les changements
    if (phInput) phInput.addEventListener('input', updateSuggestions);
    if (chlorineInput) chlorineInput.addEventListener('input', updateSuggestions);
    if (tempInput) tempInput.addEventListener('input', updateSuggestions);
});
</script>

<style>
.measurement-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(58, 166, 185, 0.2);
    max-width: 600px;
    margin: 0 auto;
}

.measurement-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.measurement-form .form-group {
    margin-bottom: 20px;
}

.measurement-form .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #2997AA;
}

.measurement-form .form-group input, 
.measurement-form .form-group select, 
.measurement-form .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.measurement-form .form-group input:focus, 
.measurement-form .form-group select:focus, 
.measurement-form .form-group textarea:focus {
    outline: none;
    border-color: #3AA6B9;
}

.measurement-form .form-group small {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.advanced-measurements {
    border-top: 1px solid #e0e0e0;
    padding-top: 20px;
    margin-top: 20px;
}

.suggestions-box {
    background: rgba(58, 166, 185, 0.1);
    border: 1px solid #3AA6B9;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
}

.suggestions-box h4 {
    margin: 0 0 10px 0;
    color: #2997AA;
}

.suggestion-item {
    padding: 5px 0;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #3AA6B9, #2997AA);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 500;
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(58, 166, 185, 0.3);
    color: white;
    text-decoration: none;
}

.btn-large {
    padding: 15px 30px;
    font-size: 16px;
    width: 100%;
}

@media (max-width: 768px) {
    .measurement-form .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .measurement-container {
        padding: 20px;
        margin: 0 10px;
    }
}
</style>