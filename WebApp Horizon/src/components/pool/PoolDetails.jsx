import React from 'react';
import { motion } from 'framer-motion';
import { Info, Edit, Save, Sun, Moon, TreeDeciduous, Wind, Shield, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";

const poolTypes = ["Enterrée", "Semi-enterrée", "Hors sol"];
const materialTypes = {
  "Enterrée": ["Liner", "Béton", "Coque"],
  "Semi-enterrée": ["Liner", "Béton", "Bois"],
  "Hors sol": ["Liner"]
};
const filterSystems = ["Sable", "Cartouche", "Diatomée", "Chaussette", "Balles filtrantes"];
const poolShapes = ["Rectangulaire", "Ovale", "Ronde", "Forme libre"];
const mainTreatments = ["Chlore", "Brome", "Sel", "Oxygène actif"];
const environments = ["Ensoleillé", "Ombragé / Arbres", "Venteux", "Protégé"];
const usageProfiles = ["Familiale (quotidien)", "Occasionnelle (week-end)", "Festive (nombreux baigneurs)"];

const DetailItem = ({ label, value }) => (
  <li><span className="font-medium text-pool-blue-900">{label} :</span> {value || 'N/A'}</li>
);

function PoolDetails({ pool, isEditing, formData, setFormData, handleSave, setIsEditing, fetchPoolData }) {

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (name, value) => {
    const newFormData = { ...formData, [name]: value };
    if (name === 'type') {
      if (value === 'Hors sol') {
        newFormData.material = 'Liner';
      } else if (!materialTypes[value]?.includes(newFormData.material)) {
        newFormData.material = '';
      }
    }
    setFormData(newFormData);
  };
  
  const handleSwitchChange = (name, checked) => {
    setFormData(prev => ({ ...prev, [name]: checked }));
  };

  return (
    <div className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm">
      <div className="flex justify-between items-center mb-4">
        <h3 className="text-xl font-semibold flex items-center text-pool-blue-900">
          <Info className="w-6 h-6 mr-2 text-pool-blue-500" />
          Détails de la Piscine
        </h3>
        {!isEditing && pool && (
          <Button variant="ghost" size="icon" onClick={() => setIsEditing(true)}>
            <Edit className="w-5 h-5" />
          </Button>
        )}
      </div>
      
      {isEditing ? (
        <form onSubmit={handleSave} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><Label htmlFor="name">Nom</Label><Input id="name" name="name" value={formData.name} onChange={handleInputChange} placeholder="Ma Piscine" /></div>
            <div><Label htmlFor="volume_m3">Volume (m³)</Label><Input id="volume_m3" name="volume_m3" type="number" value={formData.volume_m3} onChange={handleInputChange} placeholder="50" /></div>
            <div><Label htmlFor="shape">Forme</Label>
              <Select onValueChange={(value) => handleSelectChange('shape', value)} value={formData.shape}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez une forme" /></SelectTrigger>
                <SelectContent>{poolShapes.map(s => <SelectItem key={s} value={s}>{s}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div><Label htmlFor="type">Type</Label>
              <Select onValueChange={(value) => handleSelectChange('type', value)} value={formData.type}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez un type" /></SelectTrigger>
                <SelectContent>{poolTypes.map(t => <SelectItem key={t} value={t}>{t}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div><Label htmlFor="material">Matériau</Label>
              <Select onValueChange={(value) => handleSelectChange('material', value)} value={formData.material} disabled={!formData.type || formData.type === 'Hors sol'}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez un matériau" /></SelectTrigger>
                <SelectContent>{(materialTypes[formData.type] || []).map(m => <SelectItem key={m} value={m}>{m}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div><Label htmlFor="main_treatment">Traitement Principal</Label>
              <Select onValueChange={(value) => handleSelectChange('main_treatment', value)} value={formData.main_treatment}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez un traitement" /></SelectTrigger>
                <SelectContent>{mainTreatments.map(t => <SelectItem key={t} value={t}>{t}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div><Label htmlFor="filter_system">Système de filtration</Label>
              <Select onValueChange={(value) => handleSelectChange('filter_system', value)} value={formData.filter_system}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez un système" /></SelectTrigger>
                <SelectContent>{filterSystems.map(f => <SelectItem key={f} value={f}>{f}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div><Label htmlFor="filtration_hours_per_day">Heures de filtration / jour</Label><Input id="filtration_hours_per_day" name="filtration_hours_per_day" type="number" value={formData.filtration_hours_per_day} onChange={handleInputChange} placeholder="8" /></div>
            <div><Label htmlFor="environment">Environnement</Label>
              <Select onValueChange={(value) => handleSelectChange('environment', value)} value={formData.environment}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez l'environnement" /></SelectTrigger>
                <SelectContent>{environments.map(e => <SelectItem key={e} value={e}>{e}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div><Label htmlFor="usage_profile">Profil d'utilisation</Label>
              <Select onValueChange={(value) => handleSelectChange('usage_profile', value)} value={formData.usage_profile}>
                <SelectTrigger><SelectValue placeholder="Sélectionnez le profil" /></SelectTrigger>
                <SelectContent>{usageProfiles.map(u => <SelectItem key={u} value={u}>{u}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="flex items-center space-x-2 pt-4"><Switch id="has_cover" name="has_cover" checked={formData.has_cover} onCheckedChange={(checked) => handleSwitchChange('has_cover', checked)} /><Label htmlFor="has_cover">Présence d'un abri / bâche</Label></div>
          </div>

          <div className="flex space-x-2 pt-4">
            <Button type="button" variant="ghost" onClick={() => { setIsEditing(false); if (!pool) fetchPoolData(); }}>Annuler</Button>
            <Button type="submit" className="w-full bg-pool-blue-400 hover:bg-pool-blue-500 text-white"><Save className="w-4 h-4 mr-2" />Sauvegarder</Button>
          </div>
        </form>
      ) : pool ? (
        <ul className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-pool-blue-800">
          <DetailItem label="Nom" value={pool.name} />
          <DetailItem label="Volume" value={pool.volume_m3 ? `${pool.volume_m3} m³` : null} />
          <DetailItem label="Forme" value={pool.shape} />
          <DetailItem label="Type" value={pool.type} />
          <DetailItem label="Matériau" value={pool.material} />
          <DetailItem label="Traitement" value={pool.main_treatment} />
          <DetailItem label="Filtration" value={pool.filter_system} />
          <DetailItem label="Durée filtration" value={pool.filtration_hours_per_day ? `${pool.filtration_hours_per_day}h/j` : null} />
          <DetailItem label="Environnement" value={pool.environment} />
          <DetailItem label="Utilisation" value={pool.usage_profile} />
          <DetailItem label="Couverture" value={pool.has_cover ? 'Oui' : 'Non'} />
        </ul>
      ) : (
        <p className="opacity-70">Aucune information de piscine. Le formulaire est prêt à être rempli.</p>
      )}
    </div>
  );
}

export default PoolDetails;