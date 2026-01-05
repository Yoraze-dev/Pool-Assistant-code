
import React, { useState, useEffect, useCallback } from 'react';
import { motion } from 'framer-motion';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { Lightbulb, MessageSquare, Loader2, Settings, AlertTriangle, Droplets, Thermometer, CheckCircle } from 'lucide-react';
import WaterStatusCard from '@/components/WaterStatusCard';
import MetricCards from '@/components/MetricCards';
import HistorySection from '@/components/HistorySection';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/use-toast';

function RecommendationsPanel({ lastTest, weatherData }) {
    const [recommendations, setRecommendations] = useState([]);
    const [lastParams, setLastParams] = useState(null);

    const generateRecs = useCallback(() => {
        const newRecs = [];
        if (!lastTest) {
            newRecs.push({ id: 1, title: 'Faire un premier test', description: 'Analysez votre eau pour obtenir des recommandations personnalisées.', icon: Droplets, color: 'border-blue-400' });
            return newRecs;
        }

        if (lastTest.ph < 7.2) newRecs.push({ id: 'ph_plus', title: 'Ajuster le pH', description: 'Votre pH est trop bas. Ajoutez du pH+.', icon: Droplets, color: 'border-amber-400' });
        else if (lastTest.ph > 7.6) newRecs.push({ id: 'ph_minus', title: 'Ajuster le pH', description: 'Votre pH est trop élevé. Ajoutez du pH-.', icon: Droplets, color: 'border-amber-400' });

        if (lastTest.stabilizer < 30) newRecs.push({ id: 'stabilizer', title: 'Vérifier le stabilisant', description: 'Votre stabilisant est faible. Pensez à recharger.', icon: Settings, color: 'border-teal-400' });
        
        if (weatherData?.forecast?.list[0]?.weather[0]?.main === 'Rain') newRecs.push({ id: 'weather_rain', title: 'Pluie annoncée', description: 'Pas de traitement choc aujourd\'hui.', icon: AlertTriangle, color: 'border-red-400' });
        if (weatherData?.weather?.main.temp > 28) newRecs.push({ id: 'weather_heat', title: 'Forte chaleur', description: 'Surveillez le niveau de chlore.', icon: Thermometer, color: 'border-orange-400' });

        if (newRecs.length === 0) newRecs.push({ id: 'all_good', title: 'Tout est en ordre !', description: 'Vos paramètres sont bons.', icon: CheckCircle, color: 'border-green-400' });
        
        return newRecs;
    }, [lastTest, weatherData]);

    useEffect(() => {
        const currentParams = JSON.stringify({ test: lastTest, weatherMain: weatherData?.weather?.weather[0].main });
        if (currentParams !== lastParams) {
            setLastParams(currentParams);
            setRecommendations(generateRecs());
        }
    }, [lastTest, weatherData, lastParams, generateRecs]);

    return (
        <motion.div initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.4 }} className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm">
            <h3 className="text-lg font-semibold text-pool-blue-900 mb-4 flex items-center">
                <CheckCircle className="w-5 h-5 mr-2 text-green-500" />
                Conseils Personnalisés
            </h3>
            <div className="space-y-4">
                {recommendations.map((rec, index) => (
                    <motion.div key={rec.id} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.5 + index * 0.1 }} whileHover={{ scale: 1.02 }} className={`p-4 rounded-lg border-l-4 cursor-pointer transition-all bg-pool-blue-50/30 hover:bg-pool-blue-50/80 ${rec.color}`} onClick={() => toast({ title: `Action notée !` })}>
                        <div className="flex items-start space-x-3">
                            {React.createElement(rec.icon, { className: "w-5 h-5 text-pool-blue-700 mt-1" })}
                            <div>
                                <h4 className="font-semibold text-pool-blue-900 mb-1">{rec.title}</h4>
                                <p className="text-pool-blue-800/90 text-sm">{rec.description}</p>
                            </div>
                        </div>
                    </motion.div>
                ))}
            </div>
        </motion.div>
    );
}


function DashboardSummary() {
  const { user, invokeFunction } = useAuth();
  const [lastTest, setLastTest] = useState(null);
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [iaSummary, setIaSummary] = useState('');
  const [iaLoading, setIaLoading] = useState(false);
  const [messageCount, setMessageCount] = useState(0);
  const [weatherData, setWeatherData] = useState(null);

  const fetchDashboardData = useCallback(async () => {
    if (!user) return;
    setLoading(true);

    const { data: testData, error: testError } = await supabase.from('tests').select('*').eq('user_id', user.id).order('tested_at', { ascending: false }).limit(5);
    if (testError) console.error("Error fetching last test:", testError);
    else {
      setLastTest(testData[0] || null);
      setHistory(testData.map(item => ({ ...item, date: new Date(item.tested_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) })));
    }

    const { data: poolData, error: poolError } = await supabase.from('pools').select('city').eq('user_id', user.id).single();
    if (!poolError && poolData?.city) {
        const { data: weather, error: weatherError } = await invokeFunction('get-weather', { body: JSON.stringify({ city: poolData.city }) });
        if (!weatherError) setWeatherData(weather);
    }
    
    setLoading(false);
  }, [user, invokeFunction]);

  const fetchIaSummary = useCallback(async () => {
    if (!user) return;
    setIaLoading(true);
    try {
      const { count } = await supabase.from('chat_messages').select('*', { count: 'exact', head: true }).eq('user_id', user.id);
      setMessageCount(count);
      if (count >= 5) {
        const { data } = await invokeFunction('get-ia-summary');
        const reader = data.body.getReader();
        const decoder = new TextDecoder();
        let summary = '';
        while (true) {
          const { value, done } = await reader.read();
          if (done) break;
          const chunk = decoder.decode(value);
          const lines = chunk.split('\n');
          for (const line of lines) {
            if (line.startsWith('data: ')) {
              try {
                const json = JSON.parse(line.substring(6));
                if (json.type === 'content_block_delta') summary += json.delta.text;
              } catch (e) { /* ignore */ }
            }
          }
        }
        setIaSummary(summary);
      }
    } catch (error) {
      console.error("Error fetching IA summary:", error);
      setIaSummary("Impossible de générer le résumé.");
    } finally {
      setIaLoading(false);
    }
  }, [user, invokeFunction]);

  useEffect(() => {
    if(user) {
      fetchDashboardData();
      fetchIaSummary();
    }
  }, [user, fetchDashboardData, fetchIaSummary]);

  const containerVariants = { hidden: { opacity: 0 }, visible: { opacity: 1, transition: { staggerChildren: 0.1 } } };

  return (
    <motion.div className="space-y-6" variants={containerVariants} initial="hidden" animate="visible">
      <div className="flex justify-between items-start">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold text-pool-blue-900">Tableau de bord</h2>
          <p className="text-sm sm:text-base text-pool-blue-700/80">Aperçu de l'état de votre piscine.</p>
        </div>
        <Button variant="outline" onClick={() => toast({title: "🚧 Bientôt disponible !"})} size="sm" className="px-2 sm:px-4">
          <Settings className="w-4 h-4 sm:mr-2" />
          <span className="hidden sm:inline">Personnaliser</span>
        </Button>
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <WaterStatusCard lastTest={lastTest} loading={loading} />
          <MetricCards lastTest={lastTest} loading={loading} />
          <div className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-4 sm:p-6 shadow-sm">
            <h3 className="text-md sm:text-lg font-semibold mb-4 flex items-center text-pool-blue-900">
              <Lightbulb className="w-5 h-5 mr-2 text-yellow-400" />
              Synthèse de l'Assistant IA
            </h3>
            {iaLoading ? (
              <div className="flex items-center space-x-2 text-pool-blue-700"><Loader2 className="animate-spin w-5 h-5" /><span>Génération...</span></div>
            ) : messageCount < 5 ? (
              <div className="text-center py-4">
                <MessageSquare className="mx-auto w-8 h-8 sm:w-10 sm:h-10 text-pool-blue-400 mb-2" />
                <p className="text-pool-blue-800 font-medium text-sm sm:text-base">Obtenez des conseils personnalisés !</p>
                <p className="text-xs sm:text-sm text-pool-blue-700/80">Discutez pour débloquer les résumés IA.</p>
              </div>
            ) : (
              <p className="text-pool-blue-800 text-sm leading-relaxed">{iaSummary || "Aucun résumé disponible."}</p>
            )}
          </div>
        </div>
        <div className="lg:col-span-1 space-y-6">
          <RecommendationsPanel lastTest={lastTest} weatherData={weatherData} />
          <HistorySection historyData={history} loading={loading} />
        </div>
      </div>
    </motion.div>
  );
}

export default DashboardSummary;
