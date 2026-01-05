
import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Helmet } from 'react-helmet';
import { motion, AnimatePresence } from 'framer-motion';
import { Send, Search, Briefcase, Paperclip } from 'lucide-react';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { toast } from '@/components/ui/use-toast';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';

const ConversationItem = ({ conversation, onSelect, isActive, unreadCount }) => {
    const { role } = useAuth();
    const otherUser = role === 'professional' ? conversation.user_profiles : conversation.professionals;
    const fallback = (otherUser?.nickname || otherUser?.company_name || 'U').charAt(0);
    const lastMessage = conversation.conversation_messages[0];
    const isBold = unreadCount > 0;

    return (
        <motion.div
            onClick={() => onSelect(conversation)}
            className={`flex items-center p-3 cursor-pointer rounded-lg ${isActive ? 'bg-pool-blue-100' : 'hover:bg-pool-blue-50'}`}
            whileHover={{ scale: 1.02 }}
        >
            <Avatar className="h-12 w-12 mr-4">
                <AvatarImage src={otherUser?.logo_url} />
                <AvatarFallback className="bg-pool-blue-200 text-pool-blue-700 font-bold">{fallback}</AvatarFallback>
            </Avatar>
            <div className="flex-1 overflow-hidden">
                <p className={`font-semibold truncate ${isBold ? 'text-pool-blue-900' : ''}`}>{otherUser?.nickname || otherUser?.company_name}</p>
                <p className={`text-sm truncate ${isBold ? 'font-bold text-pool-blue-800' : 'text-gray-500'}`}>
                    {lastMessage ? lastMessage.content : "Aucun message"}
                </p>
            </div>
             {unreadCount > 0 && <div className="w-5 h-5 bg-pool-blue-500 text-white text-xs rounded-full flex items-center justify-center mr-2">{unreadCount}</div>}
            {lastMessage && (
                 <p className="text-xs text-gray-400 ml-2 whitespace-nowrap">
                    {formatDistanceToNow(parseISO(lastMessage.created_at), { addSuffix: true, locale: fr })}
                </p>
            )}
        </motion.div>
    );
};

const MessageBubble = ({ message, isOwnMessage, sender, otherParty }) => {
    const profile = isOwnMessage ? sender : otherParty;
    const fallback = (profile?.nickname || profile?.company_name || 'U').charAt(0);

    return (
        <div className={`flex items-end gap-3 my-4 ${isOwnMessage ? 'flex-row-reverse' : 'flex-row'}`}>
            <Avatar className="h-8 w-8">
                 <AvatarImage src={profile?.logo_url} />
                 <AvatarFallback className="bg-pool-blue-200 text-pool-blue-700">{fallback}</AvatarFallback>
            </Avatar>
            <div className={`max-w-md p-3 rounded-lg ${isOwnMessage ? 'bg-pool-blue-500 text-white' : 'bg-white'}`}>
                <p className="text-sm whitespace-pre-wrap">{message.content}</p>
            </div>
        </div>
    );
};

const Messaging = () => {
    const { user, role } = useAuth();
    const [conversations, setConversations] = useState([]);
    const [activeConversation, setActiveConversation] = useState(null);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const messagesEndRef = useRef(null);

    const fetchConversations = useCallback(async () => {
        if (!user) return;
        setLoading(true);

        const { data, error } = await supabase
            .from('conversations')
            .select(`
                *,
                user_profiles:user_id(id, nickname, logo_url),
                professionals:professional_id(id, company_name, logo_url),
                conversation_messages(content, created_at, sender_id, is_read)
            `)
            .order('created_at', { foreignTable: 'conversation_messages', ascending: false })
            .limit(1, { foreignTable: 'conversation_messages' });
        
        if (error) {
            toast({ title: "Erreur", description: "Impossible de charger les conversations.", variant: "destructive" });
        } else {
            const sortedConversations = data.sort((a, b) => new Date(b.conversation_messages[0]?.created_at || 0) - new Date(a.conversation_messages[0]?.created_at || 0));
            
            const withUnread = await Promise.all(sortedConversations.map(async (convo) => {
                const { count } = await supabase
                    .from('conversation_messages')
                    .select('*', { count: 'exact', head: true })
                    .eq('conversation_id', convo.id)
                    .eq('is_read', false)
                    .not('sender_id', 'eq', user.id);
                return { ...convo, unreadCount: count };
            }));

            setConversations(withUnread);
        }
        setLoading(false);
    }, [user, role]);

    useEffect(() => {
        fetchConversations();
        const convoChannel = supabase.channel('public:conversations')
            .on('postgres_changes', { event: '*', schema: 'public', table: 'conversations'}, fetchConversations)
            .on('postgres_changes', { event: '*', schema: 'public', table: 'conversation_messages'}, fetchConversations)
            .subscribe();
        return () => supabase.removeChannel(convoChannel);
    }, [fetchConversations]);

    const fetchMessages = useCallback(async (conversationId) => {
        let { data, error } = await supabase
            .from('conversation_messages')
            .select('*')
            .eq('conversation_id', conversationId)
            .order('created_at', { ascending: true });
        
        if (error) toast({ title: "Erreur", description: "Impossible de charger les messages.", variant: "destructive" });
        else setMessages(data);
    }, []);

    useEffect(() => {
        if (activeConversation) {
            fetchMessages(activeConversation.id);
            const markAsRead = async () => {
                await supabase
                    .from('conversation_messages')
                    .update({ is_read: true })
                    .eq('conversation_id', activeConversation.id)
                    .not('sender_id', 'eq', user.id)
            };
            markAsRead();
        }
    }, [activeConversation, fetchMessages, user?.id]);

     useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);


    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || !activeConversation) return;

        const { data, error } = await supabase.from('conversation_messages').insert({
            conversation_id: activeConversation.id,
            sender_id: user.id,
            content: newMessage,
        }).select();

        if (error) {
            toast({ title: "Erreur", description: "Impossible d'envoyer le message.", variant: "destructive" });
        } else {
            setNewMessage('');
            if (data) setMessages(current => [...current, data[0]]);
        }
    };
    
    const filteredConversations = conversations.filter(c => {
        const otherUser = role === 'professional' ? c.user_profiles : c.professionals;
        return (otherUser?.nickname || otherUser?.company_name || '').toLowerCase().includes(searchTerm.toLowerCase());
    });

    return (
        <>
            <Helmet><title>Messagerie - Pool Assistant</title></Helmet>
            <div className="h-[calc(100vh-200px)] flex border border-gray-200 rounded-xl bg-white shadow-sm">
                <aside className="w-1/3 border-r border-gray-200 flex flex-col">
                    <div className="p-4 border-b">
                        <h2 className="text-xl font-bold">Conversations</h2>
                        <div className="relative mt-2">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input placeholder="Rechercher..." className="pl-10" value={searchTerm} onChange={e => setSearchTerm(e.target.value)}/>
                        </div>
                    </div>
                    <div className="flex-1 overflow-y-auto p-2 space-y-1">
                        {loading && <p className="p-4 text-center text-gray-500">Chargement...</p>}
                        {!loading && filteredConversations.map(convo => (
                            <ConversationItem 
                                key={convo.id} 
                                conversation={convo} 
                                onSelect={setActiveConversation}
                                isActive={activeConversation?.id === convo.id}
                                unreadCount={convo.unreadCount}
                            />
                        ))}
                    </div>
                </aside>

                <main className="w-2/3 flex flex-col bg-gray-50 rounded-r-xl">
                    <AnimatePresence mode="wait">
                        {activeConversation ? (
                            <motion.div key={activeConversation.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="flex flex-col h-full">
                                <header className="p-4 border-b flex items-center bg-white">
                                    <Avatar className="h-10 w-10 mr-3">
                                        <AvatarImage src={role === 'professional' ? activeConversation.user_profiles?.logo_url : activeConversation.professionals?.logo_url} />
                                        <AvatarFallback>{(role === 'professional' ? activeConversation.user_profiles?.nickname?.charAt(0) : activeConversation.professionals?.company_name?.charAt(0)) || 'U'}</AvatarFallback>
                                    </Avatar>
                                    <h3 className="font-semibold">{role === 'professional' ? activeConversation.user_profiles?.nickname : activeConversation.professionals?.company_name}</h3>
                                </header>

                                <div className="flex-1 overflow-y-auto p-4">
                                   {messages.map(msg => (
                                        <MessageBubble 
                                            key={msg.id} 
                                            message={msg}
                                            isOwnMessage={msg.sender_id === user.id}
                                            sender={role === 'professional' ? activeConversation.professionals : activeConversation.user_profiles}
                                            otherParty={role === 'professional' ? activeConversation.user_profiles : activeConversation.professionals}
                                        />
                                    ))}
                                    <div ref={messagesEndRef} />
                                </div>

                                <footer className="p-4 border-t bg-white">
                                    <form onSubmit={handleSendMessage} className="flex items-center gap-2">
                                        <Button variant="ghost" size="icon" onClick={() => toast({ title: "Bientôt disponible !" })}><Paperclip className="w-5 h-5 text-gray-500" /></Button>
                                        <Input value={newMessage} onChange={(e) => setNewMessage(e.target.value)} placeholder="Écrivez votre message..." className="bg-gray-100" />
                                        <Button type="submit" size="icon" disabled={!newMessage.trim()}>
                                            <Send className="w-5 h-5" />
                                        </Button>
                                    </form>
                                </footer>
                            </motion.div>
                        ) : (
                             <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="flex flex-col items-center justify-center h-full text-center text-gray-500">
                                <Briefcase className="w-16 h-16 mb-4 text-gray-300"/>
                                <h3 className="text-xl font-semibold">Sélectionnez une conversation</h3>
                                <p>Ou démarrez-en une nouvelle depuis la page d'un professionnel.</p>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </main>
            </div>
        </>
    );
};

export default Messaging;
