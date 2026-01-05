import React, { useRef, useEffect } from 'react';
import { motion } from 'framer-motion';
import MessageBubble from '@/components/chat/MessageBubble';
import { Bot } from 'lucide-react';

const ThinkingIndicator = () => (
  <motion.div
    className="flex items-end gap-2 justify-start"
    initial={{ opacity: 0, y: 10 }}
    animate={{ opacity: 1, y: 0 }}
  >
    <div className="w-8 h-8 rounded-full bg-pool-blue-200 flex items-center justify-center shrink-0">
      <Bot className="w-5 h-5 text-pool-blue-600" />
    </div>
    <div className="px-4 py-3 rounded-2xl bg-pool-blue-100 text-pool-blue-900 rounded-bl-lg">
      <div className="flex items-center space-x-1">
        <span className="w-2 h-2 bg-pool-blue-400 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
        <span className="w-2 h-2 bg-pool-blue-400 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
        <span className="w-2 h-2 bg-pool-blue-400 rounded-full animate-bounce"></span>
      </div>
    </div>
  </motion.div>
);

const MessageList = ({ messages, isLoading, onActionClick }) => {
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(scrollToBottom, [messages]);

  return (
    <div className="flex-1 p-4 overflow-y-auto">
      <div className="space-y-4">
        {messages.map((msg, index) => (
          <MessageBubble key={index} message={msg} onActionClick={onActionClick} />
        ))}
        {isLoading && messages[messages.length - 1]?.role !== 'assistant' && <ThinkingIndicator />}
        <div ref={messagesEndRef} />
      </div>
    </div>
  );
};

export default MessageList;