import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { List, Edit, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/use-toast';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"

function MyTests() {
  const { user } = useAuth();
  const [tests, setTests] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchTests = async () => {
    if (!user) return;
    setLoading(true);
    const { data, error } = await supabase
      .from('tests')
      .select('*')
      .eq('user_id', user.id)
      .order('tested_at', { ascending: false });

    if (error) {
      toast({ title: "Erreur", description: "Impossible de charger les tests.", variant: "destructive" });
    } else {
      setTests(data);
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchTests();
  }, [user]);

  const handleEdit = (id) => {
    toast({
      title: "🚧 Bientôt disponible !",
      description: "La modification des tests sera bientôt possible.",
    });
  };

  const handleDelete = async (id) => {
    const { error } = await supabase.from('tests').delete().eq('id', id);
    if (error) {
      toast({ title: "Erreur", description: `Échec de la suppression: ${error.message}`, variant: "destructive" });
    } else {
      toast({ title: "Succès", description: "Test supprimé." });
      setTests(tests.filter(test => test.id !== id));
    }
  };
  
  const getStatus = (test) => {
    const { ph, chlorine } = test;
    const isPhOk = ph >= 7.2 && ph <= 7.6;
    const isClOk = chlorine >= 1 && chlorine <= 3;
    if (isPhOk && isClOk) return { text: 'Équilibré', className: 'bg-green-100 text-green-800 border border-green-200' };
    if (!isPhOk && !isClOk) return { text: 'Critique', className: 'bg-red-100 text-red-800 border border-red-200' };
    return { text: 'Attention', className: 'bg-amber-100 text-amber-800 border border-amber-200' };
  };

  if (loading) {
    return <div className="text-center p-10 text-pool-blue-700">Chargement de vos tests...</div>;
  }

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Mes Tests</title>
        <meta name="description" content="Consultez l'historique de tous vos tests d'eau de piscine." />
      </Helmet>

      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
      >
        <h2 className="text-3xl font-bold text-pool-blue-900 mb-2 flex items-center">
          <List className="w-8 h-8 mr-3 text-pool-blue-500" />
          Mes Tests Précédents
        </h2>
        <p className="text-pool-blue-800 mb-8">Retrouvez l'historique complet de toutes vos mesures d'eau.</p>

        {tests.length === 0 ? (
          <div className="text-center py-12 text-pool-blue-700">
            <p className="text-lg">Vous n'avez pas encore de tests enregistrés.</p>
            <p>Commencez par effectuer un nouveau test !</p>
          </div>
        ) : (
          <>
            <div className="space-y-4 sm:hidden">
              {tests.map(test => {
                const status = getStatus(test);
                return (
                  <div key={test.id} className="bg-pool-blue-50/50 p-4 rounded-lg border border-pool-blue-200">
                    <div className="flex justify-between items-start">
                      <div>
                        <p className="font-bold">{new Date(test.tested_at).toLocaleString('fr-FR', {dateStyle: 'medium', timeStyle: 'short'})}</p>
                        <span className={`mt-1 inline-block px-2 py-0.5 rounded-full text-xs font-medium ${status.className}`}>
                          {status.text}
                        </span>
                      </div>
                      <div className="flex space-x-1">
                         <Button 
                            variant="ghost" 
                            size="icon" 
                            className="text-pool-blue-600 hover:bg-pool-blue-200/50 h-8 w-8"
                            onClick={() => handleEdit(test.id)}
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                           <AlertDialog>
                              <AlertDialogTrigger asChild>
                                  <Button variant="ghost" size="icon" className="text-red-500 hover:bg-red-100 h-8 w-8">
                                      <Trash2 className="w-4 h-4" />
                                  </Button>
                              </AlertDialogTrigger>
                              <AlertDialogContent>
                                  <AlertDialogHeader>
                                  <AlertDialogTitle>Êtes-vous sûr ?</AlertDialogTitle>
                                  <AlertDialogDescription>
                                      Cette action est irréversible et supprimera définitivement ce test de votre historique.
                                  </AlertDialogDescription>
                                  </AlertDialogHeader>
                                  <AlertDialogFooter>
                                  <AlertDialogCancel>Annuler</AlertDialogCancel>
                                  <AlertDialogAction onClick={() => handleDelete(test.id)}>Supprimer</AlertDialogAction>
                                  </AlertDialogFooter>
                              </AlertDialogContent>
                          </AlertDialog>
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-x-4 gap-y-2 mt-3 text-sm">
                      <p><span className="font-medium">pH:</span> {test.ph ?? 'N/A'}</p>
                      <p><span className="font-medium">Chlore:</span> {test.chlorine ?? 'N/A'} ppm</p>
                      <p><span className="font-medium">Temp:</span> {test.temperature ?? 'N/A'} °C</p>
                      <p><span className="font-medium">Alcalinité:</span> {test.alkalinity ?? 'N/A'} ppm</p>
                    </div>
                  </div>
                )
              })}
            </div>
            
            <div className="overflow-x-auto hidden sm:block">
              <table className="min-w-full text-pool-blue-900">
                <thead>
                  <tr className="bg-pool-blue-100 text-left">
                    <th className="py-3 px-4 font-semibold">Date</th>
                    <th className="py-3 px-4 font-semibold">pH</th>
                    <th className="py-3 px-4 font-semibold">Chlore (ppm)</th>
                    <th className="py-3 px-4 font-semibold">Temp. (°C)</th>
                    <th className="py-3 px-4 font-semibold">Alcalinité (ppm)</th>
                    <th className="py-3 px-4 font-semibold">Statut</th>
                    <th className="py-3 px-4 font-semibold text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {tests.map((test, index) => {
                      const status = getStatus(test);
                      return (
                          <motion.tr 
                            key={test.id} 
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 0.05 * index }}
                            className="border-b border-pool-blue-200 last:border-b-0 hover:bg-pool-blue-100/50"
                          >
                            <td className="py-3 px-4">{new Date(test.tested_at).toLocaleString('fr-FR', {dateStyle: 'short', timeStyle: 'short'})}</td>
                            <td className="py-3 px-4">{test.ph}</td>
                            <td className="py-3 px-4">{test.chlorine}</td>
                            <td className="py-3 px-4">{test.temperature}</td>
                             <td className="py-3 px-4">{test.alkalinity}</td>
                            <td className="py-3 px-4">
                              <span className={`px-3 py-1 rounded-full text-xs font-medium ${status.className}`}>
                                {status.text}
                              </span>
                            </td>
                            <td className="py-3 px-4 flex space-x-2 justify-end">
                              <Button 
                                variant="ghost" 
                                size="icon" 
                                className="text-pool-blue-600 hover:bg-pool-blue-200/50"
                                onClick={() => handleEdit(test.id)}
                              >
                                <Edit className="w-4 h-4" />
                              </Button>
                               <AlertDialog>
                                  <AlertDialogTrigger asChild>
                                      <Button variant="ghost" size="icon" className="text-red-500 hover:bg-red-100">
                                          <Trash2 className="w-4 h-4" />
                                      </Button>
                                  </AlertDialogTrigger>
                                  <AlertDialogContent>
                                      <AlertDialogHeader>
                                      <AlertDialogTitle>Êtes-vous sûr ?</AlertDialogTitle>
                                      <AlertDialogDescription>
                                          Cette action est irréversible et supprimera définitivement ce test de votre historique.
                                      </AlertDialogDescription>
                                      </AlertDialogHeader>
                                      <AlertDialogFooter>
                                      <AlertDialogCancel>Annuler</AlertDialogCancel>
                                      <AlertDialogAction onClick={() => handleDelete(test.id)}>Supprimer</AlertDialogAction>
                                      </AlertDialogFooter>
                                  </AlertDialogContent>
                              </AlertDialog>
                            </td>
                          </motion.tr>
                      )
                  })}
                </tbody>
              </table>
            </div>
          </>
        )}
      </motion.div>
    </>
  );
}

export default MyTests;