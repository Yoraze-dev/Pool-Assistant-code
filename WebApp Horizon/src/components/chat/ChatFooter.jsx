import React from 'react';
import { motion } from 'framer-motion';
import { Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const SuggestionChip = ({ text, onClick }) => (
  <motion.button
    onClick={onClick}
    className="px-3 py-1.5 bg-pool-blue-100/80 text-pool-blue-800 rounded-full text-sm hover:bg-pool-blue-200/80 transition-colors"
    whileHover={{ scale: 1.05 }}
    whileTap={{ scale: 0.95 }}
  >
    {text}
  </motion.button>
);

const ChatFooter = ({ input, isLoading, onInputChange, onSubmit, onSuggestionClick, showSuggestions }) => {
  const suggestions = [
    "Quel était mon dernier pH ?",
    "Comment traiter une eau verte ?",
    "Propose-moi un plan d'action"
  ];

  return (
    <footer className="p-4 border-t border-pool-blue-200/50 bg-white/30 shrink-0">
      {showSuggestions && !isLoading && (
        <div className="flex flex-wrap gap-2 mb-3">
          {suggestions.map(s => <SuggestionChip key={s} text={s} onClick={() => onSuggestionClick(s)} />)}
        </div>
      )}
      <form onSubmit={onSubmit} className="flex items-center gap-2">
        <Input
          value={input}
          onChange={onInputChange}
          placeholder="Posez votre question..."
          className="flex-1 bg-white/90 rounded-full px-4 text-pool-blue-900 placeholder:text-pool-blue-700/60"
          disabled={isLoading}
        />
        <Button
          type="submit"
          size="icon"
          className="rounded-full bg-pool-blue-500 hover:bg-pool-blue-600"
          disabled={isLoading || !input.trim()}
        >
          <Send className="w-5 h-5" />
        </Button>
      </form>
    </footer>
  );
};

export default ChatFooter;