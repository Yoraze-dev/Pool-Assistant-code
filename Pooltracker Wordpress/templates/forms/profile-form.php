<?php
/**
 * Formulaire de configuration du profil piscine
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="profile-container">
    <h2>⚙️ Configuration de ma piscine</h2>
    
    <div class="profile-intro">
        <p>Configurez les caractéristiques de votre piscine pour recevoir des conseils personnalisés et des dosages précis.</p>
    </div>
    
    <form id="profile-form" class="profile-form">
        
        <!-- Section Dimensions -->
        <div class="form-section">
            <h3>📏 Dimensions et caractéristiques</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="pool-volume">Volume d'eau (m³) *</label>
                    <input type="number" id="pool-volume" name="pool_volume" 
                           min="5" max="500" step="0.5" placeholder="32.5">
                    <small>Volume nécessaire pour calculer les dosages</small>
                </div>
                <div class="form-group">
                    <label for="pool-shape">🏊‍♂️ Forme de la piscine</label>
                    <select id="pool-shape" name="pool_shape">
                        <option value="">-- Sélectionner --</option>
                        <option value="rectangulaire">Rectangulaire</option>
                        <option value="carree">Carrée</option>
                        <option value="ronde">Ronde / Ovale</option>
                        <option value="haricot">Haricot</option>
                        <option value="forme_libre">Forme libre</option>
                        <option value="couloir_nage">Couloir de nage</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="pool-depth">Profondeur moyenne (m)</label>
                    <input type="number" id="pool-depth" name="pool_depth_avg" 
                           min="0.5" max="5" step="0.1" placeholder="1.5">
                    <small>Profondeur moyenne de votre bassin</small>
                </div>
                <div class="form-group">
                    <label for="pool-location">Région</label>
                    <select id="pool-location" name="location_region">
                        <option value="">-- Sélectionner --</option>
                        <option value="nord">Nord (climat tempéré)</option>
                        <option value="ouest">Ouest (climat océanique)</option>
                        <option value="est">Est (climat continental)</option>
                        <option value="sud_ouest">Sud-Ouest (climat océanique)</option>
                        <option value="sud_est">Sud-Est (climat méditerranéen)</option>
                        <option value="montagne">Montagne (climat alpin)</option>
                        <option value="outre_mer">Outre-mer (climat tropical)</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Section Traitement -->
        <div class="form-section">
            <h3>🧪 Système de traitement</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="treatment-type">Type de traitement principal *</label>
                    <select id="treatment-type" name="pool_treatment_type" onchange="updateTreatmentInfo()">
                        <option value="">-- Sélectionner --</option>
                        <option value="chlore">Chlore (pastilles/granulés)</option>
                        <option value="brome">Brome</option>
                        <option value="sel">Électrolyse au sel</option>
                        <option value="oxygene">Oxygène actif</option>
                        <option value="uv">UV + Chlore</option>
                        <option value="ozone">Ozone + Chlore</option>
                        <option value="naturel">Traitement naturel</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filtration-type">Type de filtration</label>
                    <select id="filtration-type" name="pool_filtration_type">
                        <option value="">-- Sélectionner --</option>
                        <option value="sable">Sable</option>
                        <option value="verre">Verre recyclé</option>
                        <option value="zeolite">Zéolite</option>
                        <option value="cartouche">Cartouche</option>
                        <option value="terre_diatomee">Terre de diatomée</option>
                        <option value="membranes">Membranes</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="filtration-hours">Heures de filtration/jour</label>
                    <input type="number" id="filtration-hours" name="filtration_hours" 
                           min="4" max="24" placeholder="8">
                    <small>Généralement température ÷ 2</small>
                </div>
                <div class="form-group">
                    <label for="chlorinator-type">⚡ Type de dosage</label>
                    <select id="chlorinator-type" name="chlorinator_type">
                        <option value="">-- Sélectionner --</option>
                        <option value="manuel">Manuel</option>
                        <option value="flotteur">Diffuseur flottant</option>
                        <option value="skimmer">Pastilles dans skimmer</option>
                        <option value="automatique">Dosage automatique</option>
                        <option value="regulateur">Régulateur pH/Cl</option>
                    </select>
                </div>
            </div>
            
            <!-- Info traitement dynamique -->
            <div id="treatment-info" class="treatment-info" style="display: none;">
                <!-- Rempli dynamiquement -->
            </div>
        </div>
        
        <!-- Section Équipements -->
        <div class="form-section">
            <h3>Équipements et accessoires</h3>
            
            <div class="form-row">
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="has-cover" name="has_cover" value="1">
                        Bâche / Volet roulant
                    </label>
                    <small>Réduit l'évaporation et la consommation de produits</small>
                </div>
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="has-heat-pump" name="has_heat_pump" value="1">
                        Chauffage (PAC, réchauffeur...)
                    </label>
                    <small>Température plus élevée = plus de traitement</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="has-spa" name="has_spa" value="1">
                        Spa / Jacuzzi intégré
                    </label>
                    <small>Eau plus chaude, besoins spécifiques</small>
                </div>
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="has-counter-current" name="has_counter_current" value="1">
                        Nage à contre-courant
                    </label>
                    <small>Brassage intense, filtration renforcée</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="has-waterfall" name="has_waterfall" value="1">
                        Cascade / Fontaine
                    </label>
                    <small>Oxygénation de l'eau</small>
                </div>
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="has-lights" name="has_lights" value="1">
                        Éclairage LED
                    </label>
                    <small>Peut affecter certains traitements</small>
                </div>
            </div>
        </div>
        
        <!-- Section Usage -->
        <div class="form-section">
            <h3>🏊‍♀️ Usage et fréquentation</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="usage-frequency">Fréquence d'utilisation</label>
                    <select id="usage-frequency" name="usage_frequency">
                        <option value="">-- Sélectionner --</option>
                        <option value="quotidien">Quotidienne (famille nombreuse)</option>
                        <option value="regulier">Régulière (3-4 fois/semaine)</option>
                        <option value="weekend">Week-end et vacances</option>
                        <option value="occasionnel">Occasionnelle</option>
                        <option value="saisonnier">Saisonnière uniquement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="swimmer-count">Nombre de baigneurs habituels</label>
                    <select id="swimmer-count" name="swimmer_count">
                        <option value="">-- Sélectionner --</option>
                        <option value="1-2">1-2 personnes</option>
                        <option value="3-4">3-4 personnes</option>
                        <option value="5-8">5-8 personnes</option>
                        <option value="9+">Plus de 8 personnes</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="children-use" name="children_use" value="1">
                        Utilisation par des enfants
                    </label>
                    <small>Surveillance renforcée des paramètres</small>
                </div>
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="pets-access" name="pets_access" value="1">
                        Accès possible aux animaux
                    </label>
                    <small>Risque de contamination organique</small>
                </div>
            </div>
        </div>
        
        <!-- Section Notes -->
        <div class="form-section">
            <h3>Informations complémentaires</h3>
            
            <div class="form-group">
                <label for="profile-notes">Notes personnelles</label>
                <textarea id="profile-notes" name="notes" rows="4" 
                          placeholder="Particularités de votre piscine, problèmes récurrents, objectifs spécifiques..."></textarea>
            </div>
        </div>
        
        <!-- Bouton de sauvegarde -->
        <div class="form-actions">
            <button type="submit" class="btn-primary btn-large" id="save-profile">
                Sauvegarder la configuration
            </button>
            
            <div class="profile-help">
                <p><strong>Astuce :</strong> Plus votre profil est précis, plus les conseils et dosages seront adaptés à votre situation.</p>
            </div>
        </div>
    </form>
    
    <!-- Calculateur de volume -->
    <div class="volume-calculator" style="margin-top: 30px;">
        <h3>Calculateur de volume</h3>
        <p>Vous ne connaissez pas le volume exact de votre piscine ? Utilisez notre calculateur :</p>
        
        <div class="calculator-tabs">
            <button class="calc-tab active" data-shape="rectangle">Rectangle</button>
            <button class="calc-tab" data-shape="round">Ronde</button>
            <button class="calc-tab" data-shape="oval">Ovale</button>
        </div>
        
        <div id="calc-rectangle" class="calc-form active">
            <div class="form-row">
                <div class="form-group">
                    <label>Longueur (m)</label>
                    <input type="number" id="rect-length" step="0.1" placeholder="8">
                </div>
                <div class="form-group">
                    <label>Largeur (m)</label>
                    <input type="number" id="rect-width" step="0.1" placeholder="4">
                </div>
                <div class="form-group">
                    <label>Profondeur moy. (m)</label>
                    <input type="number" id="rect-depth" step="0.1" placeholder="1.5">
                </div>
            </div>
        </div>
        
        <div id="calc-round" class="calc-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Diamètre (m)</label>
                    <input type="number" id="round-diameter" step="0.1" placeholder="5">
                </div>
                <div class="form-group">
                    <label>Profondeur moy. (m)</label>
                    <input type="number" id="round-depth" step="0.1" placeholder="1.2">
                </div>
            </div>
        </div>
        
        <div id="calc-oval" class="calc-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Longueur (m)</label>
                    <input type="number" id="oval-length" step="0.1" placeholder="6">
                </div>
                <div class="form-group">
                    <label>Largeur (m)</label>
                    <input type="number" id="oval-width" step="0.1" placeholder="3">
                </div>
                <div class="form-group">
                    <label>Profondeur moy. (m)</label>
                    <input type="number" id="oval-depth" step="0.1" placeholder="1.4">
                </div>
            </div>
        </div>
        
        <button class="btn-secondary" onclick="calculateVolume()">Calculer le volume</button>
        <div id="volume-result" class="volume-result"></div>
    </div>
    
</div>

<script>
// Chargement du profil existant
document.addEventListener('DOMContentLoaded', function() {
    loadExistingProfile();
    setupVolumeCalculator();
});

function loadExistingProfile() {
    // Cette fonction sera appelée pour charger le profil existant
    if (typeof poolTracker === 'undefined') return;
    
    fetch(poolTracker.ajax_url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            'action': 'pool_get_user_profile',
            '_wpnonce': poolTracker.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            fillProfileForm(data.data);
        }
    })
    .catch(error => {
        console.log('Pas de profil existant, nouveau profil');
    });
}

function fillProfileForm(profile) {
    // Remplir les champs avec les données existantes
    Object.keys(profile).forEach(function(key) {
        var input = document.querySelector('[name="' + key + '"]');
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = profile[key] == 1;
            } else {
                input.value = profile[key] || '';
            }
        }
    });
    
    // Mettre à jour les infos de traitement
    updateTreatmentInfo();
}

function updateTreatmentInfo() {
    var treatmentType = document.getElementById('treatment-type').value;
    var infoDiv = document.getElementById('treatment-info');
    
    if (!treatmentType) {
        infoDiv.style.display = 'none';
        return;
    }
    
    var info = getTreatmentInfo(treatmentType);
    infoDiv.innerHTML = 
        '<div class="treatment-details">' +
        '<h4>' + info.title + '</h4>' +
        '<p>' + info.description + '</p>' +
        '<div class="treatment-tips">' +
        '<strong>💡 Conseils :</strong>' +
        '<ul>' + info.tips.map(tip => '<li>' + tip + '</li>').join('') + '</ul>' +
        '</div>' +
        '</div>';
    infoDiv.style.display = 'block';
}

function getTreatmentInfo(type) {
    var treatments = {
        'chlore': {
            title: 'Traitement au Chlore',
            description: 'Le traitement le plus répandu, efficace et économique.',
            tips: [
                'Maintenez le pH entre 7.0 et 7.4 pour optimiser l\'efficacité',
                'Chlore choc hebdomadaire en cas de forte fréquentation',
                'Attention à la stabilisation (acide cyanurique)'
            ]
        },
        'brome': {
            title: 'Traitement au Brome',
            description: 'Alternative au chlore, plus doux pour la peau et les yeux.',
            tips: [
                'pH optimal plus élevé : 7.2 à 7.8',
                'Efficace même à haute température',
                'Régénération possible avec oxygène actif'
            ]
        },
        'sel': {
            title: 'Électrolyse au Sel',
            description: 'Production de chlore naturel par électrolyse du sel.',
            tips: [
                'Taux de sel : 3 à 5 g/L selon l\'électrolyseur',
                'Contrôlez régulièrement les électrodes',
                'pH tend à monter, surveillance renforcée'
            ]
        },
        'oxygene': {
            title: 'Oxygène Actif',
            description: 'Traitement doux sans chlore, idéal pour les peaux sensibles.',
            tips: [
                'Traitement choc plus fréquent nécessaire',
                'Excellente filtration indispensable',
                'Coût plus élevé que le chlore'
            ]
        }
    };
    
    return treatments[type] || { title: '', description: '', tips: [] };
}

function setupVolumeCalculator() {
    // Gestion des onglets du calculateur
    var calcTabs = document.querySelectorAll('.calc-tab');
    calcTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelector('.calc-tab.active').classList.remove('active');
            document.querySelector('.calc-form.active').classList.remove('active');
            
            this.classList.add('active');
            document.getElementById('calc-' + this.dataset.shape).classList.add('active');
        });
    });
    
    // Auto-calcul en temps réel
    var calcInputs = document.querySelectorAll('.calc-form input');
    calcInputs.forEach(function(input) {
        input.addEventListener('input', calculateVolume);
    });
}

function calculateVolume() {
    var activeCalc = document.querySelector('.calc-form.active').id;
    var volume = 0;
    
    if (activeCalc === 'calc-rectangle') {
        var length = parseFloat(document.getElementById('rect-length').value) || 0;
        var width = parseFloat(document.getElementById('rect-width').value) || 0;
        var depth = parseFloat(document.getElementById('rect-depth').value) || 0;
        volume = length * width * depth;
    } else if (activeCalc === 'calc-round') {
        var diameter = parseFloat(document.getElementById('round-diameter').value) || 0;
        var depth = parseFloat(document.getElementById('round-depth').value) || 0;
        var radius = diameter / 2;
        volume = Math.PI * radius * radius * depth;
    } else if (activeCalc === 'calc-oval') {
        var length = parseFloat(document.getElementById('oval-length').value) || 0;
        var width = parseFloat(document.getElementById('oval-width').value) || 0;
        var depth = parseFloat(document.getElementById('oval-depth').value) || 0;
        volume = Math.PI * (length / 2) * (width / 2) * depth;
    }
    
    var resultDiv = document.getElementById('volume-result');
    if (volume > 0) {
        resultDiv.innerHTML = 
            '<div class="volume-calculated">' +
            '<strong>Volume calculé : ' + volume.toFixed(1) + ' m³</strong>' +
            '<button class="btn-link" onclick="useCalculatedVolume(' + volume.toFixed(1) + ')">Utiliser cette valeur</button>' +
            '</div>';
        resultDiv.style.display = 'block';
    } else {
        resultDiv.style.display = 'none';
    }
}

function useCalculatedVolume(volume) {
    document.getElementById('pool-volume').value = volume;
    document.getElementById('volume-result').innerHTML = 
        '<div class="volume-applied">✅ Volume appliqué à votre profil</div>';
    
    setTimeout(function() {
        document.getElementById('volume-result').style.display = 'none';
    }, 3000);
}
</script>

<style>
.profile-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(58, 166, 185, 0.2);
    max-width: 800px;
    margin: 0 auto;
}

.profile-intro {
    background: rgba(58, 166, 185, 0.1);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    color: #2997AA;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(58, 166, 185, 0.2);
}

.profile-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.profile-form .form-group {
    margin-bottom: 20px;
}

.profile-form .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #2997AA;
}

.profile-form .form-group input, 
.profile-form .form-group select, 
.profile-form .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.profile-form .form-group input:focus, 
.profile-form .form-group select:focus, 
.profile-form .form-group textarea:focus {
    outline: none;
    border-color: #3AA6B9;
}

.profile-form .form-group small {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
    margin-bottom: 0;
}

.treatment-info {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.treatment-details h4 {
    color: #3AA6B9;
    margin-bottom: 10px;
}

.treatment-tips {
    margin-top: 15px;
}

.treatment-tips ul {
    margin: 10px 0 0 20px;
    padding: 0;
}

.treatment-tips li {
    margin-bottom: 5px;
    font-size: 14px;
}

.form-actions {
    text-align: center;
    margin-top: 30px;
}

.profile-help {
    margin-top: 20px;
    padding: 15px;
    background: rgba(39, 174, 96, 0.1);
    border-radius: 8px;
    font-size: 14px;
}

.volume-calculator {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e0e0e0;
}

.volume-calculator h3 {
    color: #2997AA;
    margin-bottom: 15px;
}

.calculator-tabs {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.calc-tab {
    background: white;
    border: 2px solid #e0e0e0;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.calc-tab.active {
    background: #3AA6B9;
    color: white;
    border-color: #3AA6B9;
}

.calc-form {
    display: none;
    margin: 20px 0;
}

.calc-form.active {
    display: block;
}

.volume-result {
    margin-top: 15px;
    display: none;
}

.volume-calculated {
    background: rgba(58, 166, 185, 0.1);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.volume-applied {
    background: rgba(39, 174, 96, 0.1);
    color: #27AE60;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    font-weight: 500;
}

.btn-link {
    background: transparent;
    border: none;
    color: #3AA6B9;
    text-decoration: underline;
    cursor: pointer;
    margin-left: 15px;
    font-size: 14px;
}

.btn-link:hover {
    color: #2997AA;
}

@media (max-width: 768px) {
    .profile-container {
        padding: 20px;
        margin: 0 10px;
    }
    
    .profile-form .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .calculator-tabs {
        flex-direction: column;
    }
    
    .calc-tab {
        text-align: center;
    }
}
</style>