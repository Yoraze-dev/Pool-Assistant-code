import React from 'react';
import { motion } from 'framer-motion';
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Umbrella, Wind } from 'lucide-react';
import { WeatherIcon } from '@/components/layout/WeatherWidget';

function WeatherForecastTabs({ isEditing, weatherLoading, forecast, dailyForecasts, handleDayClick }) {

  const renderForecast = (days) => {
    const forecastsToShow = dailyForecasts.slice(1, days + 1);
    return (
      <div className="space-y-4">
        {forecastsToShow.map((day, index) => (
          <div key={index} className="flex justify-between items-center p-2 bg-pool-blue-100/50 rounded-lg cursor-pointer hover:bg-pool-blue-100" onClick={() => handleDayClick(day)}>
            <p className="font-semibold w-1/4">{day.date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' })}</p>
            <WeatherIcon code={day.icon} className="w-10 h-10 text-pool-blue-600" />
            <div className="flex items-center space-x-3 text-sm opacity-80">
                <span className="flex items-center"><Umbrella className="w-4 h-4 mr-1"/>{day.pop}%</span>
                <span className="flex items-center"><Wind className="w-4 h-4 mr-1"/>{day.wind} km/h</span>
            </div>
            <div className="w-1/4 text-right">
                <span className="font-bold">{day.temp_max}°</span>
                <span className="opacity-70 ml-2">{day.temp_min}°</span>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (isEditing || weatherLoading || !forecast) {
    return null;
  }

  return (
    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
      <Tabs defaultValue="3days" className="w-full">
        <TabsList className="grid w-full grid-cols-2 max-w-sm mx-auto mb-4 bg-pool-blue-100/60">
          <TabsTrigger value="3days">3 Jours</TabsTrigger>
          <TabsTrigger value="7days">7 Jours</TabsTrigger>
        </TabsList>
        <TabsContent value="3days" className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm">
          {renderForecast(3)}
        </TabsContent>
        <TabsContent value="7days" className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm">
           {renderForecast(7)}
        </TabsContent>
      </Tabs>
    </motion.div>
  );
}

export default WeatherForecastTabs;