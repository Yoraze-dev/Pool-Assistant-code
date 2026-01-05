import React, { createContext, useContext, useEffect, useState, useCallback, useMemo } from 'react';

import { supabase } from '@/lib/customSupabaseClient';
import { useToast } from '@/components/ui/use-toast';

const AuthContext = createContext(undefined);

export const AuthProvider = ({ children }) => {
  const { toast } = useToast();

  const [user, setUser] = useState(null);
  const [session, setSession] = useState(null);
  const [loading, setLoading] = useState(true);

  const handleSession = useCallback(async (session) => {
    setSession(session);
    setUser(session?.user ?? null);
    setLoading(false);
  }, []);

  useEffect(() => {
    const getSession = async () => {
      const { data: { session } } = await supabase.auth.getSession();
      handleSession(session);
    };

    getSession();

    const { data: { subscription } } = supabase.auth.onAuthStateChange(
      async (event, session) => {
        handleSession(session);
      }
    );

    return () => subscription.unsubscribe();
  }, [handleSession]);

  const signUp = useCallback(async (email, password, options) => {
    const { error } = await supabase.auth.signUp({
      email,
      password,
      options,
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
    } else {
        toast({
            title: "Inscription réussie !",
            description: "Veuillez vérifier vos e-mails pour confirmer votre compte.",
        });
    }

    return { error };
  }, [toast]);

  const signIn = useCallback(async (email, password) => {
    const { error } = await supabase.auth.signInWithPassword({
      email,
      password,
    });

    if (error) {
      toast({
        variant: "destructive",
        title: "Échec de la connexion",
        description: error.message || "Une erreur est survenue.",
      });
    }

    return { error };
  }, [toast]);

  const signOut = useCallback(async () => {
    const { error } = await supabase.auth.signOut();

    if (error) {
      toast({
        variant: "destructive",
        title: "Échec de la déconnexion",
        description: error.message || "Une erreur est survenue.",
      });
    }

    return { error };
  }, [toast]);
  
  const updateUserPassword = useCallback(async (newPassword) => {
    const { error } = await supabase.auth.updateUser({ password: newPassword });
    if (error) {
        toast({
            variant: "destructive",
            title: "Erreur",
            description: "Impossible de mettre à jour le mot de passe: " + error.message,
        });
    } else {
        toast({
            title: "Succès",
            description: "Votre mot de passe a été mis à jour avec succès.",
        });
    }
    return { error };
  }, [toast]);

  const updateUserNickname = useCallback(async (nickname) => {
    const { data, error } = await supabase.auth.updateUser({
      data: { nickname }
    });
    if (error) {
      toast({
        variant: "destructive",
        title: "Erreur",
        description: "Impossible de mettre à jour le pseudo: " + error.message,
      });
    } else {
      toast({
        title: "Succès",
        description: "Votre pseudo a été mis à jour.",
      });
      setUser(data.user);
    }
    return { error };
  }, [toast]);

  const value = useMemo(() => ({
    user,
    session,
    loading,
    signUp,
    signIn,
    signOut,
    updateUserPassword,
    updateUserNickname,
  }), [user, session, loading, signUp, signIn, signOut, updateUserPassword, updateUserNickname]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};