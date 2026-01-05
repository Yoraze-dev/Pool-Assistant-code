import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { LineChart as LineChartIcon } from 'lucide-react';
import { ResponsiveContainer, LineChart, XAxis, YAxis, CartesianGrid, Tooltip, Legend, Line } from 'recharts';
import { Button } from '@/components/ui/button';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';

function Graph() {
  const { user } = useAuth();
  const [tests, setTests] = useState([]);
  const [filteredData, setFilteredData] = useState([]);
  const [timeRange, setTimeRange] = useState('7d');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchTests = async () => {
      if (!user) return;
      setLoading(true);
      const { data, error } = await supabase
        .from('tests')
        .select('*')
        .eq('user_id', user.id)
        .order('tested_at', { ascending: true });

      if (error) {
        console.error("Erreur de chargement des tests:", error);
      } else {
        const formattedData = data.map(t => ({
          ...t,
          date: new Date(t.tested_at).getTime(),
          name: new Date(t.tested_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
        }));
        setTests(formattedData);
      }
      setLoading(false);
    };
    fetchTests();
  }, [user]);

  useEffect(() => {
    const now = new Date();
    let startDate = new Date();

    switch (timeRange) {
      case '1d': startDate.setDate(now.getDate() - 1); break;
      case '7d': startDate.setDate(now.getDate() - 7); break;
      case '30d': startDate.setDate(now.getDate() - 30); break;
      case '3m': startDate.setMonth(now.getMonth() - 3); break;
      case '1y': startDate.setFullYear(now.getFullYear() - 1); break;
      default: break;
    }

    const filtered = tests.filter(test => test.date >= startDate.getTime());
    setFilteredData(filtered);
  }, [tests, timeRange]);

  const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
      const date = new Date(payload[0].payload.tested_at).toLocaleString('fr-FR');
      return (
        <div className="bg-white/80 backdrop-blur-sm p-3 border border-pool-blue-200 rounded-lg text-pool-blue-900 shadow-lg">
          <p className="label font-bold mb-2">{`${date}`}</p>
          {payload.map(p => (
            <p key={p.dataKey} style={{ color: p.color }}>{`${p.name}: ${p.value}`}</p>
          ))}
        </div>
      );
    }
    return null;
  };
  
  if (loading) {
      return <div className="text-center p-10 text-pool-blue-700/80">Chargement des données...</div>;
  }
  
  if (tests.length === 0) {
      return (
           <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="text-center"
          >
            <h2 className="text-2xl font-bold mb-4 text-pool-blue-900">Pas encore de données</h2>
            <p className="text-pool-blue-800">Effectuez quelques tests pour voir leurs évolutions ici.</p>
        </motion.div>
      )
  }

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Graphiques</title>
        <meta name="description" content="Visualisez l'évolution des paramètres de votre piscine." />
      </Helmet>
      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <h2 className="text-3xl font-bold text-pool-blue-900 mb-4 md:mb-0 flex items-center">
              <LineChartIcon className="w-8 h-8 mr-3 text-pool-blue-500" />
              Graphiques d'Évolution
            </h2>
            <div className="flex items-center space-x-2 bg-pool-blue-100/60 p-1 rounded-lg">
                {['1d', '7d', '30d', '3m', '1y'].map(range => (
                    <Button 
                        key={range}
                        variant={timeRange === range ? 'primary' : 'ghost'}
                        size="sm"
                        onClick={() => setTimeRange(range)}
                        className={`transition-colors duration-200 ${timeRange === range ? 'bg-pool-blue-500 text-white' : 'text-pool-blue-700 hover:bg-pool-blue-200/50'}`}
                    >
                        {range.replace('d', 'J').replace('m', 'M').replace('y','A')}
                    </Button>
                ))}
            </div>
        </div>
        
        <div className="h-96 w-full">
            <ResponsiveContainer>
                <LineChart data={filteredData} margin={{ top: 5, right: 20, left: -10, bottom: 5 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0, 0, 0, 0.1)" />
                    <XAxis dataKey="name" stroke="rgba(0, 0, 0, 0.7)" />
                    <YAxis stroke="rgba(0, 0, 0, 0.7)" />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend wrapperStyle={{ color: '#143C4B' }} />
                    <Line type="monotone" dataKey="ph" name="pH" stroke="#38bdf8" strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
                    <Line type="monotone" dataKey="chlorine" name="Chlore" stroke="#4ade80" strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
                    <Line type="monotone" dataKey="temperature" name="Température" stroke="#f97316" strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
                    <Line type="monotone" dataKey="alkalinity" name="Alcalinité" stroke="#a78bfa" strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
                </LineChart>
            </ResponsiveContainer>
        </div>
      </motion.div>
    </>
  );
}

export default Graph;