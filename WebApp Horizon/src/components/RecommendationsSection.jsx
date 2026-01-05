
import React, { useState, useEffect, useCallback } from 'react';
import { motion } from 'framer-motion';
import { CheckCircle, Droplets, Settings, AlertTriangle, Thermometer } from 'lucide-react';
import { toast } from '@/components/ui/use-toast';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';

const RecommendationsSection = () => {
  const { user } = useAuth();
  const [recommendations, setRecommendations] = useState([]);
  const [lastParams, setLastParams] = useState(null);

  const generateRecommendations = useCallback((lastTest, weather) => {
    const newRecs = [];
    if (!lastTest) {
      newRecs.push({
        id: 1,
        title: 'Faire un premier test',
        description: 'Analysez votre eau pour obtenir des recommandations personnalisées.',
        icon: Droplets,
        color: 'border-blue-400'
      });
      return newRecs;
    }

    if (lastTest.ph < 7.2) {
      newRecs.push({ id: 'ph_plus', title: 'Ajuster le pH', description: 'Votre pH est trop bas. Ajoutez du pH+ pour le rééquilibrer.', icon: Droplets, color: 'border-amber-400' });
    } else if (lastTest.ph > 7.6) {
      newRecs.push({ id: 'ph_minus', title: 'Ajuster le pH', description: 'Votre pH est trop élevé. Ajoutez du pH- pour le rééquilibrer.', icon: Droplets, color: 'border-amber-400' });
    }

    if (lastTest.stabilizer < 30) {
      newRecs.push({ id: 'stabilizer', title: 'Vérifier le stabilisant', description: 'Votre stabilisant est faible. Pensez à recharger via des galets multi-actions.', icon: Settings, color: 'border-teal-400' });
    }
    
    if (weather?.forecast?.list[0]?.weather[0]?.main === 'Rain') {
        newRecs.push({ id: 'weather_rain', title: 'Pluie annoncée', description: 'Pas de traitement choc aujourd\'hui, la pluie pourrait diluer les produits.', icon: AlertTriangle, color: 'border-red-400' });
    }
    
    if (weather?.weather?.main.temp > 28) {
        newRecs.push({ id: 'weather_heat', title: 'Forte chaleur', description: 'Surveillez le niveau de chlore, il se consomme plus vite.', icon: Thermometer, color: 'border-orange-400' });
    }

    if (newRecs.length === 0) {
        newRecs.push({ id: 'all_good', title: 'Tout est en ordre !', description: 'Vos paramètres sont bons. Continuez l\'entretien régulier.', icon: CheckCircle, color: 'border-green-400' });
    }

    return newRecs;
  }, []);

  const fetchAndGenerate = useCallback(async () => {
    if (!user) return;

    const { data: testData, error: testError } = await supabase
      .from('tests')
      .select('*')
      .eq('user_id', user.id)
      .order('tested_at', { ascending: false })
      .limit(1);
    
    const { data: poolData, error: poolError } = await supabase
        .from('pools')
        .select('city')
        .eq('user_id', user.id)
        .single();
    
    let weatherData = null;
    if (poolData?.city) {
        const { data: weather, error: weatherError } = await supabase.functions.invoke('get-weather', {
            body: JSON.stringify({ city: poolData.city }),
        });
        if (!weatherError) weatherData = weather;
    }

    const currentParams = JSON.stringify({ test: testData?.[0], weather: weatherData?.weather?.weather[0].main });
    
    if(currentParams !== lastParams) {
        setLastParams(currentParams);
        const newRecommendations = generateRecommendations(testData?.[0], weatherData);
        setRecommendations(newRecommendations);
    }
  }, [user, generateRecommendations, lastParams]);

  useEffect(() => {
    fetchAndGenerate();
    // Re-check periodically
    const interval = setInterval(fetchAndGenerate, 60000 * 5); // every 5 minutes
    return () => clearInterval(interval);
  }, [fetchAndGenerate]);


  const handleRecommendationClick = (rec) => {
    toast({
      title: `✅ Recommandation "${rec.title}" notée !`,
      description: "Action ajoutée à votre planning d'entretien.",
    });
  };

  return (
    <motion.div 
      initial={{ opacity: 0, x: 20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ delay: 0.4 }}
      className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm"
    >
      <h3 className="text-lg font-semibold text-pool-blue-900 mb-4 flex items-center">
        <CheckCircle className="w-5 h-5 mr-2 text-green-500" />
        Conseils Personnalisés
      </h3>
      
      <div className="space-y-4">
        {recommendations.map((rec, index) => (
          <motion.div 
            key={rec.id}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.5 + index * 0.1 }}
            whileHover={{ scale: 1.02 }}
            className={`p-4 rounded-lg border-l-4 cursor-pointer transition-all bg-pool-blue-50/30 hover:bg-pool-blue-50/80 ${rec.color}`}
            onClick={() => handleRecommendationClick(rec)}
          >
            <div className="flex items-start space-x-3">
              {React.createElement(rec.icon, { className: "w-5 h-5 text-pool-blue-700 mt-1" })}
              <div className="flex-1">
                <h4 className="font-semibold text-pool-blue-900 mb-1">{rec.title}</h4>
                <p className="text-pool-blue-800/90 text-sm">{rec.description}</p>
              </div>
            </div>
          </motion.div>
        ))}
      </div>
    </motion.div>
  );
};

export default RecommendationsSection;
