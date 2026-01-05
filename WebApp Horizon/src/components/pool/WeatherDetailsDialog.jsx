import React from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog"
import { Clock, Umbrella, Wind } from 'lucide-react';
import { WeatherIcon } from '@/components/layout/WeatherWidget';

function WeatherDetailsDialog({ selectedDay, setSelectedDay }) {
  return (
    <Dialog open={!!selectedDay} onOpenChange={() => setSelectedDay(null)}>
      <DialogContent className="sm:max-w-[625px] bg-white/80 backdrop-blur-lg">
        <DialogHeader>
          <DialogTitle className="text-2xl text-pool-blue-900">
            Météo pour {selectedDay?.date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}
          </DialogTitle>
          <DialogDescription>
            Prévisions détaillées heure par heure.
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
          {selectedDay?.hourly?.map((hour, index) => (
            <div key={index} className="grid grid-cols-4 items-center gap-4 border-b border-pool-blue-100 pb-2">
              <div className="font-bold flex items-center"><Clock className="w-4 h-4 mr-2" />{new Date(hour.dt * 1000).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</div>
              <div className="flex items-center space-x-2">
                <WeatherIcon code={hour.weather[0].icon} className="w-8 h-8 text-pool-blue-600" />
                <span className="text-lg font-bold">{Math.round(hour.main.temp)}°C</span>
              </div>
              <div className="text-sm capitalize opacity-80">{hour.weather[0].description}</div>
              <div className="text-sm opacity-80 text-right">
                <p className="flex items-center justify-end"><Umbrella className="w-4 h-4 mr-1"/>{Math.round(hour.pop * 100)}%</p>
                <p className="flex items-center justify-end"><Wind className="w-4 h-4 mr-1"/>{Math.round(hour.wind.speed * 3.6)} km/h</p>
              </div>
            </div>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default WeatherDetailsDialog;