import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { Settings as SettingsIcon, User, Bell, Palette, LogOut, Save } from 'lucide-react';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { toast } from '@/components/ui/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
  DialogClose
} from "@/components/ui/dialog"


function ChangePasswordDialog() {
    const { updateUserPassword } = useAuth();
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [loading, setLoading] = useState(false);

    const handlePasswordChange = async () => {
        if (password !== confirmPassword) {
            toast({ title: "Erreur", description: "Les mots de passe ne correspondent pas.", variant: "destructive" });
            return;
        }
        if (password.length < 6) {
             toast({ title: "Erreur", description: "Le mot de passe doit faire au moins 6 caractères.", variant: "destructive" });
            return;
        }
        setLoading(true);
        await updateUserPassword(password);
        setLoading(false);
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Changer de mot de passe</DialogTitle>
                <DialogDescription>
                    Entrez votre nouveau mot de passe ci-dessous. Vous serez déconnecté après la modification.
                </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
                 <div>
                    <Label htmlFor="new-password">Nouveau mot de passe</Label>
                    <Input id="new-password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
                </div>
                 <div>
                    <Label htmlFor="confirm-password">Confirmer le mot de passe</Label>
                    <Input id="confirm-password" type="password" value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} />
                </div>
            </div>
            <DialogFooter>
                <DialogClose asChild><Button variant="ghost">Annuler</Button></DialogClose>
                <Button onClick={handlePasswordChange} disabled={loading}>{loading ? 'Sauvegarde...' : 'Sauvegarder'}</Button>
            </DialogFooter>
        </DialogContent>
    )
}

function Settings() {
  const { user, signOut, updateUserNickname } = useAuth();
  const [nickname, setNickname] = useState('');
  const [isEditingNickname, setIsEditingNickname] = useState(false);
  const [loadingNickname, setLoadingNickname] = useState(false);

  useEffect(() => {
    if (user?.user_metadata?.nickname) {
      setNickname(user.user_metadata.nickname);
    }
  }, [user]);

  const handleNicknameSave = async () => {
    setLoadingNickname(true);
    await updateUserNickname(nickname);
    setLoadingNickname(false);
    setIsEditingNickname(false);
  };

  const handleFeatureNotImplemented = (feature) => {
    toast({
      title: `🚧 ${feature}`,
      description: "Cette fonctionnalité est en cours de développement. Revenez bientôt !",
    });
  };

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Paramètres</title>
        <meta name="description" content="Configurez votre compte, les notifications et l'apparence de Pool Assistant." />
      </Helmet>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="p-6 bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 shadow-sm"
      >
        <h2 className="text-3xl font-bold text-pool-blue-900 mb-6 flex items-center">
          <SettingsIcon className="w-8 h-8 mr-3 text-pool-blue-500" />
          Paramètres
        </h2>
        <p className="text-pool-blue-800 mb-8">Personnalisez votre expérience Pool Assistant.</p>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="bg-white/50 p-6 rounded-lg border border-pool-blue-200 space-y-2">
            <h3 className="text-xl font-semibold text-pool-blue-900 mb-2 flex items-center">
              <User className="w-6 h-6 mr-2 text-pool-blue-500" />
              Mon Compte
            </h3>
            <p className="text-pool-blue-800">Email : <span className="font-medium text-pool-blue-900">{user?.email}</span></p>
            
            <div className="pt-2">
              <Label htmlFor="nickname">Pseudo</Label>
              {isEditingNickname ? (
                <div className="flex items-center space-x-2 mt-1">
                  <Input id="nickname" value={nickname} onChange={(e) => setNickname(e.target.value)} placeholder="Votre pseudo" />
                  <Button onClick={handleNicknameSave} size="icon" disabled={loadingNickname}>
                    <Save className="w-4 h-4" />
                  </Button>
                </div>
              ) : (
                <div className="flex items-center justify-between mt-1">
                  <p className="text-pool-blue-900 font-medium">{nickname || 'Non défini'}</p>
                  <Button variant="link" className="p-0 h-auto text-pool-blue-600 hover:text-pool-blue-500" onClick={() => setIsEditingNickname(true)}>
                    Modifier
                  </Button>
                </div>
              )}
            </div>

            <div className="pt-2">
              <Dialog>
                   <DialogTrigger asChild>
                      <Button variant="link" className="p-0 h-auto text-pool-blue-600 hover:text-pool-blue-500">
                          Changer le mot de passe
                      </Button>
                  </DialogTrigger>
                  <ChangePasswordDialog />
              </Dialog>
            </div>
          </div>

          <div 
            className="bg-white/50 p-6 rounded-lg border border-pool-blue-200 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-white/70 transition-colors"
            onClick={() => handleFeatureNotImplemented('Gestion des notifications')}
          >
            <Bell className="w-12 h-12 text-pool-blue-500 mb-4" />
            <h3 className="text-xl font-semibold text-pool-blue-900 mb-2">Notifications</h3>
            <p className="text-pool-blue-800 text-sm">Configurez vos préférences d'alertes.</p>
          </div>

          <div 
            className="bg-white/50 p-6 rounded-lg border border-pool-blue-200 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-white/70 transition-colors"
            onClick={() => handleFeatureNotImplemented('Personnalisation de l\'apparence')}
          >
            <Palette className="w-12 h-12 text-pool-blue-500 mb-4" />
            <h3 className="text-xl font-semibold text-pool-blue-900 mb-2">Apparence</h3>
            <p className="text-pool-blue-800 text-sm">Changez le thème et les couleurs de l'application.</p>
          </div>
        </div>

        <motion.button
          whileHover={{ scale: 1.05 }}
          whileTap={{ scale: 0.95 }}
          className="mt-10 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg shadow-lg transition-all flex items-center mx-auto"
          onClick={signOut}
        >
          <LogOut className="w-5 h-5 mr-2" />
          Déconnexion
        </motion.button>
      </motion.div>
    </>
  );
}

export default Settings;