
import React, { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Sun, Moon, CloudSun, Cloud, CloudDrizzle, CloudRain, CloudLightning, CloudSnow, Waves, Droplets, Wind, Thermometer } from 'lucide-react';
import { toast } from '@/components/ui/use-toast';

export const WeatherIcon = ({ code, className }) => {
    const icons = {
        '01d': <Sun className={className} />, '01n': <Moon className={className} />,
        '02d': <CloudSun className={className} />, '02n': <Cloud className={className} />,
        '03d': <Cloud className={className} />, '03n': <Cloud className={className} />,
        '04d': <Cloud className={className} />, '04n': <Cloud className={className} />,
        '09d': <CloudDrizzle className={className} />, '09n': <CloudDrizzle className={className} />,
        '10d': <CloudRain className={className} />, '10n': <CloudRain className={className} />,
        '11d': <CloudLightning className={className} />, '11n': <CloudLightning className={className} />,
        '13d': <CloudSnow className={className} />, '13n': <CloudSnow className={className} />,
        '50d': <Waves className={className} />, '50n': <Waves className={className} />,
    };
    return icons[code] || <Sun className={className} />;
};

function WeatherWidget({ city }) {
    const [weather, setWeather] = useState(null);
    const { invokeFunction } = useAuth();
    const [dailyForecasts, setDailyForecasts] = useState([]);

    useEffect(() => {
        const fetchWeather = async () => {
            if (!city) return;
            try {
                const { data, error } = await invokeFunction('get-weather', {
                    body: JSON.stringify({ city }),
                });
                if (error || (data && data.error)) throw error || new Error(data.error);
                setWeather(data);

                // Process forecast data
                const forecastList = data.forecast.list;
                const daily = {};
                forecastList.forEach(item => {
                    const date = item.dt_txt.split(' ')[0];
                    if (!daily[date]) {
                        daily[date] = { temps: [], icons: {} };
                    }
                    daily[date].temps.push(item.main.temp);
                    daily[date].icons[item.weather[0].icon] = (daily[date].icons[item.weather[0].icon] || 0) + 1;
                });
                const processed = Object.keys(daily).map(date => ({
                    date,
                    temp_min: Math.round(Math.min(...daily[date].temps)),
                    temp_max: Math.round(Math.max(...daily[date].temps)),
                    icon: Object.keys(daily[date].icons).reduce((a, b) => daily[date].icons[a] > daily[date].icons[b] ? a : b)
                })).slice(0, 7); // Limit to 7 days
                setDailyForecasts(processed);

            } catch (error) {
                console.error("Weather widget error:", error);
            }
        };

        fetchWeather();
        const interval = setInterval(fetchWeather, 300000); // Refresh every 5 minutes
        return () => clearInterval(interval);
    }, [city, invokeFunction]);

    const handleIaAdviceClick = () => {
        toast({
            title: "💡 Conseil IA Météo",
            description: "Une vague de chaleur arrive ! Surveillez l'évaporation et le niveau d'eau de votre piscine."
        });
    };

    if (!weather) {
        return <div className="text-sm text-pool-blue-900/70 h-10 flex items-center">Météo...</div>;
    }

    const { weather: current } = weather;
    
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <div className="flex items-center h-10 space-x-2 bg-white/60 px-3 rounded-xl shadow-sm cursor-pointer hover:bg-white/80 transition-colors">
                    <WeatherIcon code={current.weather[0].icon} className="w-6 h-6 text-pool-blue-600" />
                    <div>
                        <p className="font-bold text-pool-blue-900 text-base">{Math.round(current.main.temp)}°C</p>
                    </div>
                </div>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-80 bg-white/80 backdrop-blur-lg border-pool-blue-200" sideOffset={10}>
                <DropdownMenuLabel className="font-bold text-base px-3 py-2">{current.name}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <div className="p-2 space-y-1">
                    {dailyForecasts.slice(0, 3).map((day, index) => (
                         <DropdownMenuItem key={index} className="flex justify-between items-center focus:bg-pool-blue-100/50 p-2">
                            <div className="flex items-center space-x-2 flex-1">
                                <WeatherIcon code={day.icon} className="w-6 h-6 text-pool-blue-600" />
                                <p className="font-semibold text-sm w-20">{new Date(day.date).toLocaleDateString('fr-FR', { weekday: 'long' })}</p>
                            </div>
                            <div className="flex items-center text-sm">
                                <span className="font-bold">{day.temp_max}°</span>
                                <span className="text-gray-500 ml-2">{day.temp_min}°</span>
                            </div>
                        </DropdownMenuItem>
                    ))}
                </div>
                <DropdownMenuSeparator />
                 <DropdownMenuItem onSelect={handleIaAdviceClick} className="text-sm p-3 focus:bg-pool-blue-100/50 text-pool-blue-800 cursor-pointer">
                    <p>🤖 <span className="font-semibold">Conseil IA:</span> Vague de chaleur attendue. Surveillez l'évaporation.</p>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export default WeatherWidget;
