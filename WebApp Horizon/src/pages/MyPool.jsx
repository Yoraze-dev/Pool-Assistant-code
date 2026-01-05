
import React, { useState, useEffect, useCallback } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { toast } from '@/components/ui/use-toast';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';

import PoolDetails from '@/components/pool/PoolDetails';
import PoolWeather from '@/components/pool/PoolWeather';
import WeatherForecastTabs from '@/components/pool/WeatherForecastTabs';
import WeatherDetailsDialog from '@/components/pool/WeatherDetailsDialog';

function MyPool() {
  const { user, invokeFunction } = useAuth();
  const [pool, setPool] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState(null);
  const [weatherData, setWeatherData] = useState({ weather: null, forecast: null });
  const [weatherLoading, setWeatherLoading] = useState(false);
  const [selectedDay, setSelectedDay] = useState(null);

  const fetchPoolData = useCallback(async () => {
    if (!user) return;
    setLoading(true);
    const { data, error } = await supabase
      .from('pools')
      .select('*')
      .eq('user_id', user.id)
      .limit(1);

    if (error) {
      toast({ title: "Erreur", description: "Impossible de charger les informations de la piscine.", variant: "destructive" });
    } else {
      const userPool = data[0] || null;
      setPool(userPool);
      if (userPool) {
        setFormData({
          name: userPool.name || '',
          volume_m3: userPool.volume_m3 || '',
          type: userPool.type || '',
          material: userPool.material || '',
          filter_system: userPool.filter_system || '',
          city: userPool.city || 'Paris',
          shape: userPool.shape || '',
          main_treatment: userPool.main_treatment || '',
          filtration_hours_per_day: userPool.filtration_hours_per_day || '',
          environment: userPool.environment || '',
          has_cover: userPool.has_cover ?? false,
          usage_profile: userPool.usage_profile || '',
        });
      } else {
        setFormData({ name: '', volume_m3: '', type: '', material: '', filter_system: '', city: 'Paris', shape: '', main_treatment: '', filtration_hours_per_day: '', environment: '', has_cover: false, usage_profile: '' });
        setIsEditing(true);
      }
    }
    setLoading(false);
  }, [user]);

  const fetchWeather = useCallback(async (city) => {
    if (!city) return;
    setWeatherLoading(true);
    try {
      const { data, error } = await invokeFunction('get-weather', {
        body: JSON.stringify({ city }),
      });
      if (error) throw error;
      if (data.error) throw new Error(data.error);
      setWeatherData(data);
    } catch (error) {
      const errorMessage = error.message.includes("Invalid API key")
        ? "Clé API météo invalide. Veuillez la vérifier dans les paramètres."
        : "Impossible de récupérer les données météo. Vérifiez le nom de la ville.";
      toast({ title: "Erreur Météo", description: errorMessage, variant: "destructive" });
      setWeatherData({ weather: null, forecast: null });
    } finally {
      setWeatherLoading(false);
    }
  }, [invokeFunction]);

  useEffect(() => {
    fetchPoolData();
  }, [fetchPoolData]);

  useEffect(() => {
    if (pool && pool.city) {
      fetchWeather(pool.city);
    }
  }, [pool, fetchWeather]);

  const handleSave = async (e) => {
    e.preventDefault();
    if (!user) return;

    const poolData = {
      ...formData,
      user_id: user.id,
      volume_m3: parseInt(formData.volume_m3, 10) || null,
      filtration_hours_per_day: parseInt(formData.filtration_hours_per_day, 10) || null,
      has_cover: formData.has_cover,
    };

    let response;
    if (pool) {
      response = await supabase.from('pools').update(poolData).eq('id', pool.id).select().single();
    } else {
      response = await supabase.from('pools').insert(poolData).select().single();
    }

    if (response.error) {
      toast({ title: "Erreur", description: `Échec de la sauvegarde: ${response.error.message}`, variant: "destructive" });
    } else {
      toast({ title: "Succès", description: "Informations de la piscine sauvegardées." });
      const updatedPool = response.data;
      setPool(updatedPool);
      setFormData({ ...updatedPool, has_cover: updatedPool.has_cover ?? false });
      setIsEditing(false);
      if (formData.city !== (pool?.city || '')) {
        fetchWeather(formData.city);
      }
    }
  };

  const { daily: dailyForecasts, hourly: hourlyForecasts } = processForecast(weatherData.forecast);

  const handleDayClick = (day) => {
    if (!day) return;
    const dateString = day.dateString || new Date().toISOString().split('T')[0];
    const hourlyData = hourlyForecasts[dateString];
    setSelectedDay({ ...day, hourly: hourlyData });
  };
  
  if (loading || !formData) {
    return <div className="text-center p-10 text-pool-blue-700">Chargement...</div>;
  }

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Ma Piscine</title>
        <meta name="description" content="Gérez les informations de votre piscine et consultez la météo locale." />
      </Helmet>

      <WeatherDetailsDialog selectedDay={selectedDay} setSelectedDay={setSelectedDay} />

      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="space-y-8"
      >
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <PoolDetails
            pool={pool}
            isEditing={isEditing}
            formData={formData}
            setFormData={setFormData}
            handleSave={handleSave}
            setIsEditing={setIsEditing}
            fetchPoolData={fetchPoolData}
          />
          <PoolWeather
            isEditing={isEditing}
            city={formData.city}
            setFormData={setFormData}
            weatherData={weatherData}
            weatherLoading={weatherLoading}
            dailyForecasts={dailyForecasts}
            handleDayClick={handleDayClick}
          />
        </div>
        
        <WeatherForecastTabs
          isEditing={isEditing}
          weatherLoading={weatherLoading}
          forecast={weatherData.forecast}
          dailyForecasts={dailyForecasts}
          handleDayClick={handleDayClick}
        />
      </motion.div>
    </>
  );
}

function processForecast(forecast) {
  if (!forecast) return { daily: [], hourly: {} };
  
  const dailyData = {};
  const hourlyData = {};

  forecast.list.forEach(item => {
      const date = item.dt_txt.split(' ')[0];
      if (!dailyData[date]) {
          dailyData[date] = { temps: [], icons: {}, descriptions: {}, pops: [], winds: [] };
          hourlyData[date] = [];
      }
      dailyData[date].temps.push(item.main.temp);
      dailyData[date].icons[item.weather[0].icon] = (dailyData[date].icons[item.weather[0].icon] || 0) + 1;
      dailyData[date].descriptions[item.weather[0].description] = (dailyData[date].descriptions[item.weather[0].description] || 0) + 1;
      dailyData[date].pops.push(item.pop);
      dailyData[date].winds.push(item.wind.speed);
      hourlyData[date].push(item);
  });
  
  const dailyForecasts = Object.keys(dailyData).map(date => {
      const day = dailyData[date];
      const mostCommonIcon = Object.keys(day.icons).reduce((a, b) => day.icons[a] > day.icons[b] ? a : b);
      const mostCommonDesc = Object.keys(day.descriptions).reduce((a, b) => day.descriptions[a] > day.descriptions[b] ? a : b);
      return {
          date: new Date(date),
          temp_min: Math.round(Math.min(...day.temps)),
          temp_max: Math.round(Math.max(...day.temps)),
          icon: mostCommonIcon,
          description: mostCommonDesc,
          pop: Math.round(Math.max(...day.pops) * 100),
          wind: Math.round(Math.max(...day.winds) * 3.6),
          dateString: date,
      };
  });

  return { daily: dailyForecasts, hourly: hourlyData };
};

export default MyPool;
