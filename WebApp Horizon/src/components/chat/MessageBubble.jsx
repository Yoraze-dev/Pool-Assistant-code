import React from 'react';
import { motion } from 'framer-motion';
import { Bot, User as UserIcon, ChevronsRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

const MessageBubble = ({ message, onActionClick }) => {
  const isUser = message.role === 'user';

  return (
    <motion.div
      className={`flex flex-col gap-2 ${isUser ? 'items-end' : 'items-start'}`}
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4, ease: 'easeOut' }}
    >
      <div className={`flex w-full items-end gap-2 ${isUser ? 'justify-end' : 'justify-start'}`}>
        {!isUser && (
          <div className="w-8 h-8 rounded-full bg-pool-blue-200 flex items-center justify-center shrink-0">
            <Bot className="w-5 h-5 text-pool-blue-600" />
          </div>
        )}
        <div
          className={`max-w-xs md:max-w-sm px-4 py-2.5 rounded-2xl ${
            isUser
              ? 'bg-pool-blue-500 text-white rounded-br-lg'
              : 'bg-pool-blue-100 text-pool-blue-900 rounded-bl-lg'
          }`}
        >
          <p className="text-sm whitespace-pre-wrap">{message.content}</p>
        </div>
        {isUser && (
          <div className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center shrink-0">
            <UserIcon className="w-5 h-5 text-gray-600" />
          </div>
        )}
      </div>
      {!isUser && message.actions && message.actions.length > 0 && (
        <div className="flex flex-wrap gap-2 ml-10">
          {message.actions.map((action, i) => (
            <Button
              key={i}
              onClick={() => onActionClick(action)}
              size="sm"
              className="bg-pool-blue-500 hover:bg-pool-blue-600 text-white"
            >
              {action.label}
              <ChevronsRight className="w-4 h-4 ml-1" />
            </Button>
          ))}
        </div>
      )}
    </motion.div>
  );
};

export default MessageBubble;