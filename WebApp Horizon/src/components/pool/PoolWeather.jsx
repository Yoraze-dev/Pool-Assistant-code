import React from 'react';
import { motion } from 'framer-motion';
import { MapPin, Wind, Droplets } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { WeatherIcon } from '@/components/layout/WeatherWidget';

function PoolWeather({ isEditing, city, setFormData, weatherData, weatherLoading, dailyForecasts, handleDayClick }) {

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const renderCurrentWeather = () => {
    const { weather } = weatherData;
    if (!weather) return null;
    const todayForecast = dailyForecasts.find(d => d.dateString === new Date().toISOString().split('T')[0]);
    
    return (
      <div className="cursor-pointer hover:bg-pool-blue-100/30 p-2 -m-2 rounded-lg" onClick={() => handleDayClick(todayForecast)}>
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center space-x-3">
            <WeatherIcon code={weather.weather[0].icon} className="w-16 h-16 text-pool-blue-600" />
            <div>
              <p className="text-5xl font-bold">{Math.round(weather.main.temp)}°C</p>
              <p className="opacity-70 capitalize">{weather.weather[0].description}</p>
            </div>
          </div>
          <div className="text-right">
            <p className="font-semibold">{weather.name}</p>
            <p className="opacity-70 flex items-center justify-end"><Droplets className="w-4 h-4 mr-1" /> {weather.main.humidity}%</p>
            <p className="opacity-70 flex items-center justify-end"><Wind className="w-4 h-4 mr-1" /> {Math.round(weather.wind.speed * 3.6)} km/h</p>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm text-pool-blue-900">
      <h3 className="text-xl font-semibold mb-4 flex items-center">
        <MapPin className="w-6 h-6 mr-2 text-pool-blue-500" />
        Météo Locale
      </h3>
      {isEditing ? (
        <div className="space-y-2">
          <Label htmlFor="city">Ville</Label>
          <Input id="city" name="city" value={city} onChange={handleInputChange} placeholder="Ex: Paris" />
          <p className="text-xs opacity-60">Modifiez et sauvegardez pour mettre à jour la météo.</p>
        </div>
      ) : (
          weatherLoading ? <p className="opacity-80">Chargement de la météo...</p> : 
          !weatherData.weather ? <p className="opacity-80">Données météo non disponibles.</p> :
          renderCurrentWeather()
      )}
    </div>
  );
}

export default PoolWeather;