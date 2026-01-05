
import React, { useState, useEffect, useCallback } from 'react';
import { motion } from 'framer-motion';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/use-toast';
import { PlusCircle, Trash2, Loader2, List, UploadCloud, FileText, ShieldCheck, UserCheck } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';

const ManageProfessionals = () => {
    const [professionals, setProfessionals] = useState([]);
    const [loading, setLoading] = useState(true);
    const { toast } = useToast();

    const fetchProfessionals = useCallback(async () => {
        setLoading(true);
        const { data, error } = await supabase.from('professionals').select('id, company_name, is_verified');
        if (error) {
            toast({ variant: "destructive", title: "Erreur", description: "Impossible de charger les professionnels." });
        } else {
            setProfessionals(data);
        }
        setLoading(false);
    }, [toast]);

    useEffect(() => {
        fetchProfessionals();
    }, [fetchProfessionals]);
    
    const handleVerificationToggle = async (proId, currentStatus) => {
        const { error } = await supabase.from('professionals').update({ is_verified: !currentStatus }).eq('id', proId);
        if (error) {
            toast({ variant: "destructive", title: "Erreur", description: "La mise à jour a échoué." });
        } else {
            toast({ title: "Succès", description: "Statut de vérification mis à jour."});
            setProfessionals(professionals.map(p => p.id === proId ? {...p, is_verified: !currentStatus} : p));
        }
    };

    if (loading) return <div className="flex justify-center items-center h-40"><Loader2 className="h-8 w-8 animate-spin text-pool-blue-500" /></div>;

    return (
        <div className="space-y-3 max-h-[600px] overflow-y-auto pr-2">
            {professionals.map(pro => (
                <div key={pro.id} className="flex items-center justify-between bg-pool-blue-50 p-3 rounded-md">
                    <div>
                        <p className="font-semibold text-pool-blue-900">{pro.company_name}</p>
                        <Badge variant={pro.is_verified ? 'default' : 'secondary'} className={pro.is_verified ? 'bg-green-100 text-green-800' : ''}>
                           {pro.is_verified ? 'Vérifié' : 'Non vérifié'}
                        </Badge>
                    </div>
                    <Switch
                        checked={pro.is_verified}
                        onCheckedChange={() => handleVerificationToggle(pro.id, pro.is_verified)}
                    />
                </div>
            ))}
        </div>
    );
};

const Admin = () => {
    const [documents, setDocuments] = useState([]);
    const [newDoc, setNewDoc] = useState({
        titre: '',
        contenu: '',
        source: '',
        type_document: '',
        marque: ''
    });
    const [loading, setLoading] = useState(true);
    const [isSubmittingText, setIsSubmittingText] = useState(false);
    const [isSubmittingFiles, setIsSubmittingFiles] = useState(false);
    const [selectedFiles, setSelectedFiles] = useState([]);
    const [activeTab, setActiveTab] = useState('knowledge');
    const { toast } = useToast();
    const { invokeFunction } = useAuth();

    const fetchDocuments = useCallback(async () => {
        setLoading(true);
        const { data, error } = await supabase
            .from('documents_globaux')
            .select('id, titre, contenu, source, type_document, marque')
            .order('date_upload', { ascending: false });

        if (error) {
            toast({ variant: "destructive", title: "Erreur de chargement", description: "Impossible de charger les documents." });
        } else {
            setDocuments(data);
        }
        setLoading(false);
    }, [toast]);

    useEffect(() => {
        if(activeTab === 'knowledge') {
            fetchDocuments();
        }
    }, [fetchDocuments, activeTab]);
    
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setNewDoc(prev => ({ ...prev, [name]: value }));
    };

    const handleAddDocument = async (e) => {
        e.preventDefault();
        if (!newDoc.titre.trim() || !newDoc.contenu.trim()) {
            toast({ variant: "destructive", title: "Champs requis", description: "Le titre et le contenu sont requis." });
            return;
        }
        setIsSubmittingText(true);
        const { error } = await invokeFunction('embed-and-store-document', { body: JSON.stringify(newDoc) });
        if (error) {
            toast({ variant: "destructive", title: "Erreur d'ajout", description: "Impossible d'ajouter le document: " + error.message });
        } else {
            toast({ title: "Succès", description: "Le document a été ajouté et indexé." });
            setNewDoc({ titre: '', contenu: '', source: '', type_document: '', marque: '' });
            await fetchDocuments();
        }
        setIsSubmittingText(false);
    };

    const handleDeleteDocument = async (id) => {
        const { error } = await supabase.from('documents_globaux').delete().eq('id', id);
        if (error) {
            toast({ variant: "destructive", title: "Erreur de suppression", description: "Impossible de supprimer le document." });
        } else {
            toast({ title: "Succès", description: "Le document a été supprimé." });
            setDocuments(documents.filter(doc => doc.id !== id));
        }
    };
    
    const handleFileChange = (event) => {
        setSelectedFiles(Array.from(event.target.files));
    };

    const handleFileUpload = async () => {
        if (selectedFiles.length === 0) {
            toast({ variant: "destructive", title: "Aucun fichier", description: "Veuillez sélectionner des fichiers à téléverser." });
            return;
        }
        setIsSubmittingFiles(true);

        const uploadPromises = selectedFiles.map(file => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('source', newDoc.source || 'Inconnue');
            formData.append('type_document', newDoc.type_document || 'Documentation');
            formData.append('marque', newDoc.marque || 'Générique');

            return invokeFunction('upload-and-embed-document', { body: formData });
        });
        
        const results = await Promise.all(uploadPromises);
        
        let successCount = 0;
        results.forEach((result, index) => {
            if (result.error) {
                toast({ variant: "destructive", title: `Erreur avec ${selectedFiles[index].name}`, description: result.error.message });
            } else {
                successCount++;
            }
        });

        if (successCount > 0) {
            toast({ title: "Téléversement réussi", description: `${successCount} sur ${selectedFiles.length} documents ont été traités.` });
            await fetchDocuments();
        }

        setSelectedFiles([]);
        setIsSubmittingFiles(false);
    };

    return (
        <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="p-4 sm:p-6"
        >
            <h1 className="text-3xl font-bold text-pool-blue-900 mb-6">Panneau d'Administration</h1>
            
            <div className="border-b border-gray-200 mb-6">
                <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                    <button onClick={() => setActiveTab('knowledge')} className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ${activeTab === 'knowledge' ? 'border-pool-blue-500 text-pool-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`}>
                        Gestion des Connaissances
                    </button>
                    <button onClick={() => setActiveTab('pros')} className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ${activeTab === 'pros' ? 'border-pool-blue-500 text-pool-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`}>
                        Gestion des Professionnels
                    </button>
                </nav>
            </div>

            {activeTab === 'knowledge' && (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div className="lg:col-span-2 grid grid-cols-1 gap-8">
                        <div className="bg-white/80 p-6 rounded-lg shadow-md">
                            <h2 className="text-2xl font-semibold text-pool-blue-800 mb-4 flex items-center"><PlusCircle className="mr-2" /> Ajouter une Connaissance</h2>
                            <form onSubmit={handleAddDocument} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="md:col-span-2">
                                    <Label htmlFor="titre">Titre</Label>
                                    <Input id="titre" name="titre" value={newDoc.titre} onChange={handleInputChange} />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="contenu">Contenu</Label>
                                    <Textarea id="contenu" name="contenu" value={newDoc.contenu} onChange={handleInputChange} className="min-h-[150px]" />
                                </div>
                                <div>
                                    <Label htmlFor="source">Source</Label>
                                    <Input id="source" name="source" placeholder="ex: Bayrol" value={newDoc.source} onChange={handleInputChange} />
                                </div>
                                <div>
                                    <Label htmlFor="type_document">Type</Label>
                                    <Input id="type_document" name="type_document" placeholder="ex: Protocole, Conseil" value={newDoc.type_document} onChange={handleInputChange} />
                                </div>
                                <div>
                                    <Label htmlFor="marque">Marque</Label>
                                    <Input id="marque" name="marque" placeholder="ex: HTH" value={newDoc.marque} onChange={handleInputChange} />
                                </div>
                                 <div className="md:col-span-2">
                                    <Button type="submit" className="w-full mt-2" disabled={isSubmittingText}>{isSubmittingText ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Ajout...</> : "Ajouter par Texte"}</Button>
                                </div>
                            </form>
                        </div>

                        <div className="bg-white/80 p-6 rounded-lg shadow-md">
                            <h2 className="text-2xl font-semibold text-pool-blue-800 mb-4 flex items-center"><UploadCloud className="mr-2" /> Ajouter par Fichier</h2>
                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm text-gray-600 mb-2">Les métadonnées (Source, Type, Marque) saisies ci-dessus seront appliquées aux fichiers téléversés.</p>
                                    <Label htmlFor="file-upload">Fichiers (.pdf, .docx, .txt)</Label>
                                    <Input id="file-upload" type="file" multiple onChange={handleFileChange} accept=".pdf,.docx,.txt" />
                                </div>
                                {selectedFiles.length > 0 && (
                                    <div className="space-y-2">
                                        <h3 className="text-sm font-medium">Fichiers sélectionnés:</h3>
                                        <ul className="space-y-1 text-sm text-gray-600 max-h-24 overflow-y-auto">
                                            {selectedFiles.map((file, i) => <li key={i} className="flex items-center"><FileText className="mr-2 h-4 w-4" />{file.name}</li>)}
                                        </ul>
                                    </div>
                                )}
                                <Button onClick={handleFileUpload} className="w-full" disabled={isSubmittingFiles || selectedFiles.length === 0}>{isSubmittingFiles ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Traitement...</> : `Traiter ${selectedFiles.length} fichier(s)`}</Button>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white/80 p-6 rounded-lg shadow-md">
                        <h2 className="text-2xl font-semibold text-pool-blue-800 mb-4 flex items-center"><List className="mr-2" /> Base de connaissances</h2>
                        {loading ? <div className="flex justify-center items-center h-full"><Loader2 className="h-8 w-8 animate-spin text-pool-blue-500" /></div> : (
                            <div className="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                                {documents.length > 0 ? documents.map(doc => (
                                    <motion.div key={doc.id} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 20 }} layout className="flex items-start justify-between bg-pool-blue-50 p-3 rounded-md">
                                        <div className="flex-grow">
                                            <p className="font-semibold text-pool-blue-900">{doc.titre}</p>
                                            <p className="text-sm text-gray-600 truncate max-w-xs">{doc.contenu}</p>
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                {doc.source && <span className="text-xs bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full">{doc.source}</span>}
                                                {doc.type_document && <span className="text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full">{doc.type_document}</span>}
                                                {doc.marque && <span className="text-xs bg-purple-200 text-purple-800 px-2 py-0.5 rounded-full">{doc.marque}</span>}
                                            </div>
                                        </div>
                                        <Button variant="ghost" size="icon" onClick={() => handleDeleteDocument(doc.id)} className="text-red-500 hover:text-red-700 hover:bg-red-100 shrink-0 ml-2"><Trash2 className="h-4 w-4" /></Button>
                                    </motion.div>
                                )) : <p className="text-center text-gray-500 py-8">Aucun document.</p>}
                            </div>
                        )}
                    </div>
                </div>
            )}
            
            {activeTab === 'pros' && (
                 <div className="bg-white/80 p-6 rounded-lg shadow-md">
                    <h2 className="text-2xl font-semibold text-pool-blue-800 mb-4 flex items-center"><UserCheck className="mr-2" /> Valider les Professionnels</h2>
                    <ManageProfessionals />
                </div>
            )}
        </motion.div>
    );
};

export default Admin;
