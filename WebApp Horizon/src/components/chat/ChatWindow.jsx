import React from 'react';
import { motion } from 'framer-motion';
import ChatHeader from '@/components/chat/ChatHeader';
import MessageList from '@/components/chat/MessageList';
import ChatFooter from '@/components/chat/ChatFooter';

const chatWindowVariants = {
  closed: {
    opacity: 0,
    y: 50,
    scale: 0.8,
    transformOrigin: 'bottom right',
    transition: { type: 'spring', stiffness: 400, damping: 40 }
  },
  open: {
    opacity: 1,
    y: 0,
    scale: 1,
    transformOrigin: 'bottom right',
    transition: { type: 'spring', stiffness: 400, damping: 30, delay: 0.1 }
  }
};

const ChatWindow = ({ messages, input, isLoading, hasNotifications, onInputChange, onSubmit, onSuggestionClick, onActionClick, onClose }) => {
  return (
    <motion.div
      variants={chatWindowVariants}
      initial="closed"
      animate="open"
      exit="closed"
      className="fixed bottom-28 right-6 w-[calc(100vw-3rem)] max-w-md h-[70vh] max-h-[600px] bg-white/80 backdrop-blur-2xl border border-pool-blue-200/50 rounded-2xl shadow-2xl flex flex-col z-50 overflow-hidden"
    >
      <ChatHeader hasNotifications={hasNotifications} onClose={onClose} />
      <MessageList messages={messages} isLoading={isLoading} onActionClick={onActionClick} />
      <ChatFooter
        input={input}
        isLoading={isLoading}
        onInputChange={onInputChange}
        onSubmit={onSubmit}
        onSuggestionClick={onSuggestionClick}
        showSuggestions={messages.length <= 1}
      />
    </motion.div>
  );
};

export default ChatWindow;