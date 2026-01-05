import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/use-toast';
import { TestTube, Scan, UploadCloud, Plus, Save, ArrowLeft, ChevronDown, Beaker, StickyNote } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { useNavigate } from 'react-router-dom';

function NewTest() {
  const [entryMode, setEntryMode] = useState(null); // null, 'scan', 'manual'
  const [formData, setFormData] = useState({
    tested_at: new Date().toISOString().slice(0, 16),
    ph: '',
    chlorine: '',
    temperature: '',
    alkalinity: '',
    hardness: '',
    stabilizer: '',
    total_chlorine: '',
    notes: ''
  });
  const [showAdvanced, setShowAdvanced] = useState(false);
  const [poolId, setPoolId] = useState(null);
  const [loading, setLoading] = useState(false);
  const { user } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    const fetchPool = async () => {
      if (!user) return;
      const { data, error } = await supabase
        .from('pools')
        .select('id')
        .eq('user_id', user.id)
        .limit(1);

      if (error || data.length === 0) {
        toast({
          title: "Aucune piscine trouvée",
          description: "Veuillez d'abord créer une piscine dans 'Ma Piscine'.",
          variant: "destructive"
        });
        navigate('/my-pool');
      } else {
        setPoolId(data[0].id);
      }
    };
    fetchPool();
  }, [user, navigate]);

  const handleScanTestStrip = () => {
    toast({
      title: "🚧 Bientôt disponible !",
      description: "Le scan de bandelette est en cours de développement.",
    });
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!poolId) {
      toast({ title: "Erreur", description: "Impossible de trouver votre piscine.", variant: "destructive" });
      return;
    }
    setLoading(true);

    const testData = {
      pool_id: poolId,
      user_id: user.id,
      tested_at: new Date(formData.tested_at).toISOString(),
      ph: parseFloat(formData.ph) || null,
      chlorine: parseFloat(formData.chlorine) || null,
      temperature: parseFloat(formData.temperature) || null,
      alkalinity: parseInt(formData.alkalinity, 10) || null,
      hardness: parseInt(formData.hardness, 10) || null,
      stabilizer: parseInt(formData.stabilizer, 10) || null,
      total_chlorine: parseFloat(formData.total_chlorine) || null,
      notes: formData.notes || null,
    };

    const { error } = await supabase.from('tests').insert(testData);

    setLoading(false);
    if (error) {
      toast({ title: "Erreur", description: `Échec de l'enregistrement du test: ${error.message}`, variant: "destructive" });
    } else {
      toast({ title: "Succès", description: "Nouveau test enregistré avec succès !" });
      navigate('/');
    }
  };

  const renderInitialOptions = () => (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="text-center"
    >
      <h2 className="text-3xl font-bold text-pool-blue-900 mb-2">Effectuer un Nouveau Test</h2>
      <p className="text-pool-blue-800 mb-8">Choisissez comment vous souhaitez enregistrer les résultats de votre test d'eau.</p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
        <motion.div
          whileHover={{ scale: 1.03 }}
          className="bg-pool-blue-100/50 p-8 rounded-lg border border-pool-blue-200 flex flex-col items-center justify-center cursor-pointer"
          onClick={handleScanTestStrip}
        >
          <Scan className="w-16 h-16 text-pool-blue-500 mb-4" />
          <h3 className="text-xl font-semibold text-pool-blue-900 mb-2">Scanner une Bandelette</h3>
          <p className="text-pool-blue-800 text-sm">Utilisez la caméra pour analyser votre bandelette de test.</p>
          <Button className="mt-4 bg-pool-blue-400 hover:bg-pool-blue-500 text-white">
            <UploadCloud className="w-4 h-4 mr-2" />
            Scanner
          </Button>
        </motion.div>
        <motion.div
          whileHover={{ scale: 1.03 }}
          className="bg-pool-blue-100/50 p-8 rounded-lg border border-pool-blue-200 flex flex-col items-center justify-center cursor-pointer"
          onClick={() => setEntryMode('manual')}
        >
          <TestTube className="w-16 h-16 text-pool-blue-500 mb-4" />
          <h3 className="text-xl font-semibold text-pool-blue-900 mb-2">Saisie Manuelle</h3>
          <p className="text-pool-blue-800 text-sm">Entrez les valeurs de vos tests manuellement.</p>
          <Button className="mt-4 bg-pool-blue-400 hover:bg-pool-blue-500 text-white">
            <Plus className="w-4 h-4 mr-2" />
            Saisir
          </Button>
        </motion.div>
      </div>
    </motion.div>
  );

  const renderManualForm = () => (
    <motion.div
      initial={{ opacity: 0, x: 50 }}
      animate={{ opacity: 1, x: 0 }}
      exit={{ opacity: 0, x: -50 }}
    >
      <Button variant="ghost" onClick={() => setEntryMode(null)} className="mb-4 text-pool-blue-600 hover:text-pool-blue-500">
        <ArrowLeft className="w-4 h-4 mr-2" />
        Retour
      </Button>
      <h2 className="text-3xl font-bold text-pool-blue-900 mb-6 flex items-center">
        <TestTube className="w-8 h-8 mr-3 text-pool-blue-500" />
        Saisie Manuelle du Test
      </h2>
      <form onSubmit={handleSubmit} className="space-y-4 max-w-md mx-auto">
        <div>
          <Label htmlFor="tested_at">Date et heure du test</Label>
          <Input id="tested_at" name="tested_at" type="datetime-local" value={formData.tested_at} onChange={handleInputChange} />
        </div>
        <div>
          <Label htmlFor="ph">pH</Label>
          <Input id="ph" name="ph" type="number" step="0.1" value={formData.ph} onChange={handleInputChange} placeholder="ex: 7.2" />
        </div>
        <div>
          <Label htmlFor="chlorine">Chlore libre (ppm)</Label>
          <Input id="chlorine" name="chlorine" type="number" step="0.1" value={formData.chlorine} onChange={handleInputChange} placeholder="ex: 1.5" />
        </div>
        <div>
          <Label htmlFor="temperature">Température (°C)</Label>
          <Input id="temperature" name="temperature" type="number" step="1" value={formData.temperature} onChange={handleInputChange} placeholder="ex: 25" />
        </div>
        <div>
          <Label htmlFor="alkalinity">Alcalinité (TAC, ppm)</Label>
          <Input id="alkalinity" name="alkalinity" type="number" step="1" value={formData.alkalinity} onChange={handleInputChange} placeholder="ex: 100" />
        </div>

        <div className="border-t border-pool-blue-200 pt-4">
            <button type="button" onClick={() => setShowAdvanced(!showAdvanced)} className="flex justify-between items-center w-full text-left font-semibold text-pool-blue-800">
                <span>Mesures avancées (Optionnel)</span>
                <ChevronDown className={`w-5 h-5 transition-transform ${showAdvanced ? 'rotate-180' : ''}`} />
            </button>
            {showAdvanced && (
                <motion.div 
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    className="space-y-4 mt-4"
                >
                    <div>
                        <Label htmlFor="hardness">Dureté (TH, ppm)</Label>
                        <Input id="hardness" name="hardness" type="number" step="1" value={formData.hardness} onChange={handleInputChange} placeholder="ex: 200" />
                    </div>
                    <div>
                        <Label htmlFor="stabilizer">Stabilisant (CYA, ppm)</Label>
                        <Input id="stabilizer" name="stabilizer" type="number" step="1" value={formData.stabilizer} onChange={handleInputChange} placeholder="ex: 30" />
                    </div>
                    <div>
                        <Label htmlFor="total_chlorine">Chlore total (ppm)</Label>
                        <Input id="total_chlorine" name="total_chlorine" type="number" step="0.1" value={formData.total_chlorine} onChange={handleInputChange} placeholder="ex: 1.7" />
                    </div>
                </motion.div>
            )}
        </div>
        
        <div className="border-t border-pool-blue-200 pt-4">
             <Label htmlFor="notes" className="flex items-center font-semibold text-pool-blue-800 mb-2">
                <StickyNote className="w-5 h-5 mr-2" />
                Notes et observations
            </Label>
            <Textarea id="notes" name="notes" value={formData.notes} onChange={handleInputChange} placeholder="Ex: L'eau est légèrement trouble, ajouté un floculant..." />
        </div>

        <Button type="submit" disabled={loading} className="w-full bg-pool-blue-400 hover:bg-pool-blue-500 text-white">
          <Save className="w-4 h-4 mr-2" />
          {loading ? 'Enregistrement...' : 'Enregistrer le test'}
        </Button>
      </form>
    </motion.div>
  );

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Nouveau Test</title>
        <meta name="description" content="Effectuez un nouveau test de l'eau de votre piscine avec Pool Assistant. Saisie manuelle ou scan de bandelette." />
      </Helmet>
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
      >
        {entryMode === 'manual' ? renderManualForm() : renderInitialOptions()}
      </motion.div>
    </>
  );
}

export default NewTest;