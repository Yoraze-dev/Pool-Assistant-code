import React, { useState } from 'react';
import { Helmet } from 'react-helmet';
import { motion, AnimatePresence } from 'framer-motion';
import { Mail, KeyRound, Briefcase } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { Link } from 'react-router-dom';

const AuthForm = ({ isLogin, setIsLogin }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { signIn, signUp } = useAuth();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (isLogin) {
        await signIn({ email, password });
      } else {
        await signUp({ email, password }, email.split('@')[0]);
      }
    } catch (error) {
      // Error is handled in useAuth context
    } finally {
      setLoading(false);
    }
  };
  
  const title = isLogin ? 'Connexion' : 'Inscription';
  const buttonText = isLogin ? 'Se connecter' : 'Créer un compte';
  const switchText = isLogin ? "Pas de compte ?" : "Déjà un compte ?";
  const switchLinkText = isLogin ? "S'inscrire" : "Se connecter";

  return (
    <motion.div
      key={isLogin ? 'login' : 'signup'}
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -20 }}
      transition={{ duration: 0.3 }}
      className="w-full"
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        <h2 className="text-3xl font-bold text-center text-pool-blue-900">{title}</h2>
        <div className="relative">
          <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-pool-blue-400" />
          <Input 
            id="email"
            type="email"
            placeholder="email@exemple.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="pl-10 text-pool-blue-900 bg-white/70"
          />
        </div>
        <div className="relative">
          <KeyRound className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-pool-blue-400" />
          <Input 
            id="password"
            type="password"
            placeholder="••••••••"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="pl-10 text-pool-blue-900 bg-white/70"
          />
        </div>
        <Button 
          type="submit" 
          className="w-full bg-pool-blue-400 hover:bg-pool-blue-500 text-white font-semibold"
          disabled={loading}
        >
          {loading ? 'Chargement...' : buttonText}
        </Button>
      </form>
      <p className="mt-6 text-center text-sm text-pool-blue-800">
        {switchText}{' '}
        <button onClick={() => setIsLogin(!isLogin)} className="font-semibold text-pool-blue-600 hover:text-pool-blue-500">
          {switchLinkText}
        </button>
      </p>
      {!isLogin && (
        <div className="mt-4 text-center">
          <Link to="/pro-signup" className="inline-flex items-center text-sm font-semibold text-pool-blue-600 hover:text-pool-blue-500">
            <Briefcase className="w-4 h-4 mr-2" />
            Vous êtes un professionnel ? Créez un compte PRO
          </Link>
        </div>
      )}
    </motion.div>
  )
}

function Auth() {
  const [isLogin, setIsLogin] = useState(true);

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Authentification</title>
        <meta name="description" content="Connectez-vous ou inscrivez-vous à Pool Assistant pour gérer votre piscine." />
      </Helmet>
      <div className="min-h-screen flex items-center justify-center bg-pool-blue-50 p-4">
        <motion.div 
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.5 }}
          className="w-full max-w-md mx-auto bg-white/50 backdrop-blur-lg rounded-2xl border border-pool-blue-200 shadow-2xl p-8"
        >
          <div className="flex flex-col items-center mb-8">
             <img  alt="Pool Assistant Logo" className="h-16 w-auto mb-4" src="https://horizons-cdn.hostinger.com/9d64b00d-aa76-49d8-82ac-c98cff77592b/f98c9313387076e5efce20eb986f4791.png" />
          </div>
          <AnimatePresence mode="wait">
            <AuthForm isLogin={isLogin} setIsLogin={setIsLogin} />
          </AnimatePresence>
        </motion.div>
      </div>
    </>
  );
}

export default Auth;