import React from 'react';
import { motion } from 'framer-motion';
import { Droplets, Activity, Zap, Beaker, Thermometer, Wind, Loader2 } from 'lucide-react';

const MetricCard = ({ metric }) => (
  <div className="bg-white/50 p-4 rounded-xl shadow-sm border border-pool-blue-200/50 flex flex-col justify-between">
    <div className="flex items-start justify-between">
      {React.createElement(metric.icon, { className: `w-6 h-6 ${metric.color}` })}
      <span className="text-2xl font-bold text-pool-blue-900">{metric.value ?? '--'}</span>
    </div>
    <div>
      <div className="text-pool-blue-800/90 text-sm h-10">{metric.title} {metric.unit && `(${metric.unit})`}</div>
      {metric.max != null && metric.value != null && (
        <div className="w-full bg-pool-blue-200/50 rounded-full h-1.5 mt-2">
          <motion.div 
            className={`h-1.5 rounded-full ${metric.barColor}`}
            initial={{ width: 0 }}
            animate={{ width: `${(metric.value / metric.max) * 100}%`}}
            transition={{ duration: 0.5, ease: "easeInOut" }}
          />
        </div>
      )}
    </div>
  </div>
);

const MetricCards = ({ lastTest, loading }) => {
  const metrics = [
    { 
      title: 'pH', 
      value: lastTest?.ph, 
      unit: '', 
      icon: Droplets, 
      color: 'text-pool-blue-500', 
      barColor: 'bg-pool-blue-500',
      max: 14, 
    },
    { 
      title: 'Chlore', 
      value: lastTest?.chlorine, 
      unit: 'ppm', 
      icon: Zap, 
      color: 'text-green-500', 
      barColor: 'bg-green-500',
      max: 3, 
    },
    { 
      title: 'Alcalinité', 
      value: lastTest?.alkalinity, 
      unit: 'ppm', 
      icon: Activity, 
      color: 'text-purple-500', 
      barColor: 'bg-purple-500',
      max: 200, 
    },
    { 
      title: 'Stabilisant', 
      value: lastTest?.stabilizer, 
      unit: 'ppm', 
      icon: Beaker, 
      color: 'text-teal-500', 
      barColor: 'bg-teal-500',
      max: 100, 
    },
    { 
      title: 'Chlore Total', 
      value: lastTest?.total_chlorine, 
      unit: 'ppm', 
      icon: Zap, 
      color: 'text-indigo-500', 
      barColor: 'bg-indigo-500',
      max: 5, 
    },
    { 
      title: 'Dureté', 
      value: lastTest?.hardness, 
      unit: 'ppm', 
      icon: Wind, 
      color: 'text-orange-500', 
      barColor: 'bg-orange-500',
      max: 400, 
    },
  ];

  if (loading) {
    return (
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {Array.from({ length: 6 }).map((_, index) => (
          <div key={index} className="bg-white/50 rounded-xl p-4 h-[138px] flex items-center justify-center">
            <Loader2 className="w-6 h-6 text-pool-blue-400 animate-spin" />
          </div>
        ))}
      </div>
    );
  }

  return (
    <motion.div 
      variants={{
        visible: { transition: { staggerChildren: 0.05 } }
      }}
      initial="hidden"
      animate="visible"
      className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8"
    >
      {metrics.map((metric) => (
         <motion.div key={metric.title} variants={{ hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0 } }}>
           <MetricCard metric={metric} />
         </motion.div>
      ))}
    </motion.div>
  );
};

export default MetricCards;