import React from 'react';
import { motion } from 'framer-motion';
import { BarChart3, Calendar, TrendingUp, Info, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';

const HistorySection = ({ historyData, loading }) => {

  return (
    <motion.div 
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ delay: 0.3 }}
      className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-6 shadow-sm"
    >
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold text-pool-blue-900 flex items-center">
          <BarChart3 className="w-5 h-5 mr-2 text-pool-blue-500" />
          Historique Récent
        </h3>
        <Button 
          variant="outline" 
          size="sm"
          className="bg-pool-blue-100/50 border-pool-blue-200 text-pool-blue-800 hover:bg-pool-blue-100"
          asChild
        >
          <Link to="/my-tests">
            <TrendingUp className="w-4 h-4 mr-2" />
            Voir tout
          </Link>
        </Button>
      </div>
      
      <div className="space-y-3 min-h-[150px] flex flex-col">
        {loading ? (
          <div className="flex-grow flex items-center justify-center">
            <Loader2 className="w-8 h-8 text-pool-blue-500 animate-spin" />
          </div>
        ) : historyData && historyData.length > 0 ? (
          historyData.map((data, index) => (
            <motion.div 
              key={index}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.1 + index * 0.05 }}
              className="flex items-center justify-between p-3 bg-pool-blue-50/50 rounded-lg"
            >
              <div className="flex items-center space-x-3">
                <Calendar className="w-4 h-4 text-pool-blue-500" />
                <span className="text-pool-blue-900 font-medium text-sm">{data.date}</span>
              </div>
              <div className="flex space-x-3 text-xs sm:text-sm">
                <span className="text-pool-blue-700">pH: {data.ph ?? '--'}</span>
                <span className="text-green-600">Cl: {data.chlorine ?? '--'}</span>
                <span className="text-orange-600">{data.temperature ?? '--'}°C</span>
              </div>
            </motion.div>
          ))
        ) : (
          <div className="flex-grow flex flex-col items-center justify-center text-center p-4">
              <Info className="w-10 h-10 text-pool-blue-400 mb-2" />
              <p className="text-pool-blue-800 font-medium">Aucun test récent</p>
              <p className="text-sm text-pool-blue-700/80">Vos 5 derniers tests apparaîtront ici.</p>
          </div>
        )}
      </div>
    </motion.div>
  );
};

export default HistorySection;