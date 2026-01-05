import { createContext, useState, useEffect, useContext, useCallback, useMemo } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import React from 'react';
import { useToast } from '@/components/ui/use-toast';

const SupabaseAuthContext = createContext();

export function AuthProvider({ children }) {
  const [session, setSession] = useState(null);
  const [user, setUser] = useState(null);
  const [role, setRole] = useState(null);
  const [loading, setLoading] = useState(true);
  const { toast } = useToast();

  const fetchAndSetProfile = useCallback(async (user) => {
    if (!user) {
      setRole(null);
      return null;
    }
    try {
      let { data: profile, error } = await supabase
        .from('user_profiles')
        .select('role')
        .eq('id', user.id)
        .single();

      if (error && error.code === 'PGRST116') {
        await new Promise(resolve => setTimeout(resolve, 500));
        let { data: newProfile, error: retryError } = await supabase
            .from('user_profiles')
            .select('role')
            .eq('id', user.id)
            .single();

        if (retryError) throw retryError;
        profile = newProfile;
      } else if (error) {
        if (error.message.includes('JWT expired')) return null; 
        throw error;
      }
      
      const userRole = profile?.role || 'user';
      setRole(userRole);
      return userRole;
    } catch (error) {
      console.error("Error fetching user profile:", error.message);
      setRole(null); 
      return null;
    }
  }, []);

  const fetchUnreadMessages = useCallback(async (userId) => {
    if(!userId) return 0;
    
    // Step 1: Fetch conversation IDs
    const { data: convos, error: convoError } = await supabase
        .from('conversations')
        .select('id')
        .or(`user_id.eq.${userId},professional_id.eq.${userId}`);

    if (convoError) {
        console.error("Error fetching conversation IDs:", convoError);
        return 0;
    }
    if (!convos || convos.length === 0) {
        return 0;
    }

    const conversationIds = convos.map(c => c.id);

    // Step 2: Fetch unread messages count
    const { count, error } = await supabase
      .from('conversation_messages')
      .select('*', { count: 'exact', head: true })
      .eq('is_read', false)
      .not('sender_id', 'eq', userId)
      .in('conversation_id', conversationIds);
      
    if (error) {
        console.error("Error fetching unread messages count:", error);
        return 0;
    }
    return count || 0;
  }, []);

  useEffect(() => {
    setLoading(true);
    const getInitialSession = async () => {
        const { data: { session: initialSession }, error } = await supabase.auth.getSession();
        if (error) {
            console.error("Error getting initial session:", error);
        }
        setSession(initialSession);
        const currentUser = initialSession?.user ?? null;
        
        if (currentUser) {
            const unreadCount = await fetchUnreadMessages(currentUser.id);
            setUser({ ...currentUser, unreadMessagesCount: unreadCount });
            await fetchAndSetProfile(currentUser);
        } else {
            setUser(null);
        }
        setLoading(false);
    };

    getInitialSession();

    const { data: authListener } = supabase.auth.onAuthStateChange(async (_event, session) => {
      setSession(session);
      const currentUser = session?.user ?? null;
      if (currentUser) {
        const unreadCount = await fetchUnreadMessages(currentUser.id);
        setUser({ ...currentUser, unreadMessagesCount: unreadCount });
        await fetchAndSetProfile(currentUser);
      } else {
        setUser(null);
        setRole(null);
      }
      if (loading) setLoading(false);
    });
    
    const messageChannel = supabase.channel('public:conversation_messages')
      .on('postgres_changes', { event: '*', schema: 'public', table: 'conversation_messages' },
      async (payload) => {
          if(user) {
              const unreadCount = await fetchUnreadMessages(user.id);
              setUser(prevUser => ({...prevUser, unreadMessagesCount: unreadCount}));
          }
      })
      .subscribe();

    return () => {
      authListener.subscription.unsubscribe();
      supabase.removeChannel(messageChannel);
    };
  }, [fetchAndSetProfile, fetchUnreadMessages, user?.id]);

  const signIn = useCallback(async (credentials) => {
    const { data, error } = await supabase.auth.signInWithPassword(credentials);
    if (error) {
        toast({
            variant: "destructive",
            title: "Échec de la connexion",
            description: error.message || "Une erreur est survenue.",
        });
        throw error;
    }
    if (data.user) {
      await fetchAndSetProfile(data.user);
    }
    return data;
  }, [toast, fetchAndSetProfile]);

  const signUp = useCallback(async (credentials, nickname, isPro = false) => {
    const { data: currentSessionData } = await supabase.auth.getSession();
    const currentSession = currentSessionData.session;
    
    if(isPro && currentSession) {
        await supabase.auth.signOut();
    }
    
    const { data, error } = await supabase.auth.signUp({
      ...credentials,
      options: {
        data: {
          nickname: nickname,
          is_pro_signup: isPro,
        }
      }
    });

    if (error) {
        if (error.message.includes('over_email_send_rate_limit')) {
            toast({
              variant: "destructive",
              title: "Trop de tentatives",
              description: "Pour des raisons de sécurité, veuillez attendre un peu avant de réessayer.",
            });
        } else {
            toast({
              variant: "destructive",
              title: "Échec de l'inscription",
              description: error.message || "Une erreur est survenue.",
            });
        }
        if(isPro && currentSession) {
             const { error: sessionError } = await supabase.auth.setSession({
                access_token: currentSession.access_token,
                refresh_token: currentSession.refresh_token,
             });
             if(sessionError) console.error("Error re-authenticating user:", sessionError);
        }
        throw error;
    }
    return data;
  }, [toast]);

  const signOut = useCallback(async () => {
    const { error } = await supabase.auth.signOut();
    if (error) {
        toast({
            variant: "destructive",
            title: "Échec de la déconnexion",
            description: error.message || "Une erreur est survenue.",
        });
        throw error;
    }
  }, [toast]);

  const invokeFunction = useCallback(async (functionName, options) => {
    try {
        const { data, error } = await supabase.functions.invoke(functionName, options);
        
        if (error) {
          throw error;
        }

        return { data, error: null };
    } catch (error) {
        let description = error.message;
        if(error.message.includes('permission denied for table secrets')){
            description = "Vous n'avez pas les droits pour effectuer cette action.";
        }
        else if (error.message.includes('Invalid API key')) {
            description = "Clé API météo invalide. Veuillez la vérifier dans les paramètres.";
        } else if (error.message.includes("failed to fetch")) {
            description = "Problème de réseau ou fonction indisponible. Veuillez réessayer.";
        } else if (error.message.includes("Erreur de l'API d'adresse")) {
            description = "Le service d'autocomplétion d'adresse est indisponible. Veuillez saisir manuellement."
        }

        toast({
            variant: "destructive",
            title: `Erreur de la fonction ${functionName}`,
            description: description,
        });
        return { data: null, error };
    }
}, [toast]);

  const value = useMemo(() => ({
    session,
    user,
    role,
    loading,
    signIn,
    signUp,
    signOut,
    invokeFunction
  }), [session, user, role, loading, signIn, signUp, signOut, invokeFunction]);

  return (
    <SupabaseAuthContext.Provider value={value}>
      {!loading && children}
    </SupabaseAuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(SupabaseAuthContext);
   if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};