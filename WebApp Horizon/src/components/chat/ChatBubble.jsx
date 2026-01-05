import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Bot, X } from 'lucide-react';

const ChatBubble = ({ isOpen, isLoading, hasNotifications, onClick }) => {
  const bubbleVariants = {
    rest: {
      y: [0, -4, 0],
      transition: { duration: 4, repeat: Infinity, ease: "easeInOut" }
    },
    hover: {
      scale: 1.1,
      boxShadow: "0 0 20px 5px rgba(92, 198, 212, 0.5)",
      transition: { type: "spring", stiffness: 300, damping: 10 }
    },
    click: {
      scale: [1, 1.2, 0.8, 1],
      transition: { duration: 0.3 }
    }
  };

  return (
    <motion.div
      className="fixed bottom-6 right-6 z-50"
      initial={{ scale: 0, opacity: 0 }}
      animate={{ scale: 1, opacity: 1 }}
      exit={{ scale: 0, opacity: 0 }}
      transition={{ delay: 0.5, duration: 0.5, type: "spring" }}
    >
      <motion.button
        onClick={onClick}
        className="relative w-20 h-20 rounded-full bg-gradient-to-br from-pool-blue-400 to-pool-blue-500 text-white shadow-2xl flex items-center justify-center focus:outline-none"
        aria-label={isOpen ? "Fermer le chat" : "Ouvrir le chat"}
        variants={bubbleVariants}
        initial="rest"
        animate="rest"
        whileHover="hover"
      >
        {hasNotifications && !isOpen && (
          <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full border-2 border-white animate-pulse" />
        )}
        <div className="absolute top-3 left-1/2 -translate-x-1/2 w-6 h-3 bg-white/30 rounded-full blur-sm" />
        <AnimatePresence mode="wait">
          <motion.div
            key={isOpen ? 'close' : (isLoading ? 'loading' : 'idle')}
            initial={{ rotate: -90, opacity: 0, scale: 0.5 }}
            animate={{ rotate: 0, opacity: 1, scale: 1 }}
            exit={{ rotate: 90, opacity: 0, scale: 0.5 }}
            transition={{ duration: 0.2 }}
            className="z-10"
          >
            {isOpen ? (
                <X className="w-9 h-9" />
            ) : isLoading ? (
              <div className="flex space-x-1">
                {[...Array(3)].map((_, i) => <motion.div key={i} className="w-2 h-2 bg-white/80 rounded-full" animate={{ y: [0, -4, 0] }} transition={{ duration: 1, repeat: Infinity, delay: i * 0.15 }} />)}
              </div>
            ) : (
              <Bot className="w-9 h-9" />
            )}
          </motion.div>
        </AnimatePresence>
      </motion.button>
    </motion.div>
  );
};

export default ChatBubble;