import React from 'react';
import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { CheckCircle, AlertTriangle, Activity, Thermometer, Droplets, Wind, Waves, Moon, Sun, Cloud, CloudSun, CloudDrizzle, CloudRain, CloudLightning, CloudSnow, Loader2, Info } from 'lucide-react';

const WaterStatusCard = ({ lastTest, loading }) => {
  if (loading) {
    return (
      <div className="bg-pool-blue-100/50 p-6 rounded-2xl shadow-lg mb-6 border border-pool-blue-200 flex items-center justify-center h-[148px]">
        <Loader2 className="w-8 h-8 text-pool-blue-500 animate-spin" />
      </div>
    );
  }

  if (!lastTest) {
    return (
       <div className="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-lg mb-6 border border-pool-blue-200 text-center">
            <Info className="w-12 h-12 text-pool-blue-500 mx-auto mb-3" />
            <h2 className="text-xl font-bold text-pool-blue-900 mb-2">Prêt à plonger ?</h2>
            <p className="text-pool-blue-700 mb-4">Enregistrez votre premier test pour obtenir une analyse complète de votre eau.</p>
            <Button asChild className="bg-pool-blue-500 hover:bg-pool-blue-600 text-white font-semibold px-6 rounded-xl">
                <Link to="/new-test">Faire un premier test</Link>
            </Button>
        </div>
    );
  }

  const getStatus = (test) => {
    if (!test) return { key: 'unknown', text: 'Données insuffisantes' };
    const { ph, chlorine } = test;
    const isPhOk = ph >= 7.2 && ph <= 7.6;
    const isClOk = chlorine >= 1 && chlorine <= 3;
    if (isPhOk && isClOk) return { key: 'balanced', text: 'Eau parfaitement équilibrée' };
    if (!isPhOk && !isClOk) return { key: 'critical', text: 'Action urgente requise' };
    return { key: 'warning', text: 'Attention requise' };
  };

  const status = getStatus(lastTest);
  const formattedDate = new Date(lastTest.tested_at).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

  const getStatusColor = (key) => {
    switch (key) {
      case 'balanced': return 'bg-green-500';
      case 'warning': return 'bg-amber-500';
      case 'critical': return 'bg-red-500';
      default: return 'bg-gray-500';
    }
  };

  const StatusIcon = status.key === 'balanced' ? CheckCircle : AlertTriangle;

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
      className="bg-white/70 backdrop-blur-lg p-6 rounded-2xl shadow-sm border border-pool-blue-200 text-pool-blue-900 mb-6"
    >
      <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="flex items-center space-x-4">
          <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center ${getStatusColor(status.key)}`}>
            <StatusIcon className="w-7 h-7 sm:w-8 sm:h-8 text-white" />
          </div>
          <div>
            <h2 className="text-xl sm:text-2xl font-bold">{status.text}</h2>
            <p className="text-xs sm:text-sm text-pool-blue-800/90">Dernier test : {formattedDate}</p>
          </div>
        </div>
        <div className="text-center sm:text-right bg-pool-blue-50/50 p-3 rounded-lg">
          <div className="text-3xl sm:text-4xl font-bold">{lastTest.temperature || '--'}°C</div>
          <div className="text-xs sm:text-sm text-pool-blue-800/90">Température de l'eau</div>
        </div>
      </div>
    </motion.div>
  );
};

export default WaterStatusCard;