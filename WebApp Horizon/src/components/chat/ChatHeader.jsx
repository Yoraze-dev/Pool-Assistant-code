import React from 'react';
import { Bot, X } from 'lucide-react';
import { Button } from '@/components/ui/button';

const ChatHeader = ({ hasNotifications, onClose }) => (
  <header className="p-4 border-b border-pool-blue-200/50 flex items-center justify-between space-x-3 shrink-0">
    <div className="flex items-center space-x-3">
      <div className="w-10 h-10 rounded-full bg-gradient-to-br from-pool-blue-400 to-pool-blue-600 flex items-center justify-center shrink-0">
        <Bot className="w-6 h-6 text-white" />
      </div>
      <div>
        <h3 className="font-bold text-lg text-pool-blue-900">Pool Assistant</h3>
        <p className="text-xs text-pool-blue-700/80">Votre expert personnel</p>
      </div>
    </div>
    <Button variant="ghost" size="icon" onClick={onClose} className="text-pool-blue-700 hover:bg-pool-blue-100/50 rounded-full">
      <X className="w-6 h-6" />
    </Button>
  </header>
);

export default ChatHeader;