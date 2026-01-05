import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { useNavigate } from 'react-router-dom';
import ChatBubble from '@/components/chat/ChatBubble';
import ChatWindow from '@/components/chat/ChatWindow';

function ChatWidget({ hasNotifications }) {
    const [isOpen, setIsOpen] = useState(false);
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const { user, invokeFunction } = useAuth();
    const navigate = useNavigate();

    useEffect(() => {
        const fetchHistory = async () => {
            if (!isOpen || !user) return;
            setIsLoading(true);
            try {
                const { data, error } = await supabase
                    .from('chat_messages')
                    .select('role, content')
                    .eq('user_id', user.id)
                    .order('created_at', { ascending: true })
                    .limit(20);

                if (error) throw error;

                if (data && data.length > 0) {
                    setMessages(data);
                } else {
                    setMessages([{ role: 'assistant', content: 'Bonjour ! Comment puis-je vous aider avec votre piscine aujourd\'hui ?' }]);
                }
            } catch (error) {
                console.error("Failed to fetch chat history:", error);
                 setMessages([{ role: 'assistant', content: 'Bonjour ! Impossible de charger l\'historique. Comment puis-je vous aider ?' }]);
            } finally {
                setIsLoading(false);
            }
        };

        fetchHistory();
    }, [isOpen, user]);

    const sendQuery = async (query) => {
        if (!query.trim() || isLoading || !user) return;

        const userMessage = { role: 'user', content: query };
        const currentMessages = [...messages, userMessage];
        setMessages(currentMessages);
        setInput('');
        setIsLoading(true);

        try {
            await supabase.from('chat_messages').insert({ role: 'user', content: query, user_id: user.id });
            
            const { data, error } = await invokeFunction('claude-chat', {
                body: JSON.stringify({ messages: currentMessages.slice(-15) }) 
            });

            if (error) throw error;
            if (!data || !data.body) {
                throw new Error("Réponse invalide de la fonction serveur.");
            }

            let assistantResponse = '';
            let assistantMessage = { role: 'assistant', content: '', actions: [] };
            setMessages(prev => [...prev, assistantMessage]);

            const reader = data.body.getReader();
            const decoder = new TextDecoder();
            let done = false;

            while (!done) {
                const { value, done: readerDone } = await reader.read();
                done = readerDone;
                const chunk = decoder.decode(value, { stream: true });
                const lines = chunk.split('\n');
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const json = JSON.parse(line.substring(6));
                            if (json.type === 'content_block_delta' && json.delta.type === "text_delta") {
                                assistantResponse += json.delta.text;
                                assistantMessage.content = assistantResponse;
                                setMessages(prev => [...prev.slice(0, -1), { ...assistantMessage }]);
                            }
                        } catch(e) {
                            // Ignore malformed JSON
                        }
                    }
                }
            }
            
            const actionRegex = /\[ACTION:([^\]]+)\]/g;
            let match;
            const extractedActions = [];
            while ((match = actionRegex.exec(assistantResponse)) !== null) {
                try {
                    const actionData = JSON.parse(match[1]);
                    extractedActions.push(actionData);
                } catch (e) {
                    console.error("Failed to parse action JSON:", match[1]);
                }
            }

            if (extractedActions.length > 0) {
                assistantMessage.content = assistantResponse.replace(actionRegex, "").trim();
                assistantMessage.actions = extractedActions;
                setMessages(prev => [...prev.slice(0, -1), { ...assistantMessage }]);
            }
            
            if(assistantResponse) {
                await supabase.from('chat_messages').insert({ role: 'assistant', content: assistantResponse, user_id: user.id });
            }

        } catch (error) {
            console.error(error);
            const errorMessage = { role: 'assistant', content: "Désolé, une erreur est survenue. Veuillez réessayer.", actions: [] };
            setMessages(prev => [...prev.slice(0,-1), errorMessage]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleActionClick = (action) => {
        if (action.type === 'navigate') {
            navigate(action.path);
            setIsOpen(false);
        }
    };
    
    return (
        <>
            <ChatBubble 
                isOpen={isOpen}
                onClick={() => setIsOpen(!isOpen)} 
                hasNotifications={hasNotifications}
                isLoading={isLoading}
            />
            <AnimatePresence>
                {isOpen && (
                    <ChatWindow
                        messages={messages}
                        input={input}
                        setInput={setInput}
                        isLoading={isLoading}
                        onSend={sendQuery}
                        onClose={() => setIsOpen(false)}
                        onActionClick={handleActionClick}
                    />
                )}
            </AnimatePresence>
        </>
    );
}

export default ChatWidget;