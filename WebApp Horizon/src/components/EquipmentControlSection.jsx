import React from 'react';
import { motion } from 'framer-motion';
import { Settings } from 'lucide-react';
import { toast } from '@/components/ui/use-toast';

const EquipmentControlSection = () => {
  const handleEquipmentControl = () => {
    toast({
      title: "🚧 Cette fonctionnalité n'est pas encore implémentée—mais ne vous inquiétez pas ! Vous pouvez la demander dans votre prochaine requête ! 🚀",
    });
  };

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.6 }}
      className="mt-8 bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20"
    >
      <h3 className="text-xl font-semibold text-white mb-6 flex items-center">
        <Settings className="w-6 h-6 mr-2 text-gray-400" />
        Équipements Connectés
      </h3>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <motion.div 
          whileHover={{ scale: 1.05 }}
          className="bg-gradient-to-r from-pool-blue-500/20 to-pool-blue-600/20 p-4 rounded-lg border border-pool-blue-400/30 cursor-pointer"
          onClick={handleEquipmentControl}
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-white font-medium">Pompe Principale</span>
            <div className="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
          </div>
          <p className="text-white/70 text-sm">Actif - 6h/jour</p>
        </motion.div>

        <motion.div 
          whileHover={{ scale: 1.05 }}
          className="bg-gradient-to-r from-purple-500/20 to-pink-500/20 p-4 rounded-lg border border-purple-400/30 cursor-pointer"
          onClick={handleEquipmentControl}
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-white font-medium">Robot Nettoyeur</span>
            <div className="w-3 h-3 bg-yellow-400 rounded-full"></div>
          </div>
          <p className="text-white/70 text-sm">En pause - Prêt</p>
        </motion.div>

        <motion.div 
          whileHover={{ scale: 1.05 }}
          className="bg-gradient-to-r from-green-500/20 to-emerald-500/20 p-4 rounded-lg border border-green-400/30 cursor-pointer"
          onClick={handleEquipmentControl}
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-white font-medium">Sonde pH</span>
            <div className="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
          </div>
          <p className="text-white/70 text-sm">Surveillance active</p>
        </motion.div>
      </div>
    </motion.div>
  );
};

export default EquipmentControlSection;