import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { Link, useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { supabase } from '@/lib/customSupabaseClient';
import { Briefcase, Mail, Lock, User, Building, Phone, MapPin, Loader2, Info } from 'lucide-react';
import { useDebounce } from 'use-debounce';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"

const CustomInput = React.memo(({ id, type, value, onChange, required, icon: Icon, placeholder, ...props }) => (
    <div className="relative">
        <Input id={id} type={type} value={value} onChange={onChange} required={required} className="pl-10" placeholder={placeholder} {...props} />
         {Icon && <Icon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />}
    </div>
));

export default function ProSignUp() {
    const [formData, setFormData] = useState({
        email: '',
        password: '',
        nickname: '',
        companyName: '',
        phone: '',
        address: '',
        city: '',
        postalCode: '',
        siret: ''
    });
    const [loading, setLoading] = useState(false);
    const [showConfirmationDialog, setShowConfirmationDialog] = useState(false);
    const { signUp, session, invokeFunction, signOut } = useAuth();
    const navigate = useNavigate();

    const [addressQuery, setAddressQuery] = useState('');
    const [debouncedAddressQuery] = useDebounce(addressQuery, 300);
    const [addressSuggestions, setAddressSuggestions] = useState([]);
    const [isAddressInputFocused, setIsAddressInputFocused] = useState(false);

    useEffect(() => {
        if (debouncedAddressQuery.length > 3) {
            const fetchAddresses = async () => {
                const { data, error } = await invokeFunction('address-autocomplete', {
                    body: JSON.stringify({ query: debouncedAddressQuery })
                });

                if (error) {
                    console.error("Error fetching addresses:", error);
                    setAddressSuggestions([]);
                } else {
                    setAddressSuggestions(data.features || []);
                }
            };
            fetchAddresses();
        } else {
            setAddressSuggestions([]);
        }
    }, [debouncedAddressQuery, invokeFunction]);

    const handleAddressChange = (e) => {
        setFormData(prev => ({ ...prev, address: e.target.value }));
        setAddressQuery(e.target.value);
    };

    const handleSelectAddress = (suggestion) => {
        setFormData(prev => ({
            ...prev,
            address: suggestion.properties.name,
            city: suggestion.properties.city,
            postalCode: suggestion.properties.postcode,
        }));
        setAddressSuggestions([]);
        setIsAddressInputFocused(false);
    };

    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.id]: e.target.value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (formData.siret && !/^\d{14}$/.test(formData.siret)) {
            toast({
                title: "Numéro SIRET invalide",
                description: "Le numéro SIRET doit contenir exactement 14 chiffres.",
                variant: 'destructive',
            });
            return;
        }

        setLoading(true);

        try {
            const { data: authData, error: authError } = await signUp(
                { email: formData.email, password: formData.password },
                formData.nickname,
                true 
            );
            
            if (authError) {
                setLoading(false);
                return;
            }
            
            if (!authData.user) {
                toast({
                    title: "Erreur d'inscription",
                    description: "Impossible de créer l'utilisateur. Veuillez réessayer.",
                    variant: 'destructive',
                });
                setLoading(false);
                return;
            }
            
            const user = authData.user;
            
            // On s'assure que le profil utilisateur est créé avec le bon rôle
            // La fonction `handle_new_user` est maintenant responsable de cela.
            // On insère juste les données pro
            const { error: profileError } = await supabase.from('professionals').insert({
                user_id: user.id,
                company_name: formData.companyName,
                phone_number: formData.phone,
                address: formData.address,
                city: formData.city,
                postal_code: formData.postalCode,
                siret: formData.siret || null,
            });

            if (profileError) throw profileError;
            
            toast({
                title: "Compte PRO créé avec succès !",
                description: "Veuillez vérifier vos e-mails pour confirmer votre compte.",
            });
            setShowConfirmationDialog(true);

        } catch (error) {
            console.error("Pro sign up error:", error);
            toast({
                title: "Erreur lors de la création du profil",
                description: error.message,
                variant: 'destructive',
            });
        } finally {
            setLoading(false);
        }
    };
    
    const isUserConnected = useMemo(() => !!session, [session]);
    
    const handleDialogAction = useCallback(async () => {
        setShowConfirmationDialog(false);
        if(isUserConnected) {
            await signOut();
        }
        navigate('/auth');
    }, [isUserConnected, navigate, signOut]);

    return (
        <>
            <Helmet>
                <title>Devenir Partenaire PRO - Pool Assistant</title>
                <meta name="description" content="Rejoignez la plateforme Pool Assistant en tant que professionnel de la piscine." />
            </Helmet>
             <AlertDialog open={showConfirmationDialog} onOpenChange={setShowConfirmationDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                    <AlertDialogTitle>Vérifiez vos e-mails !</AlertDialogTitle>
                    <AlertDialogDescription>
                        Votre compte professionnel a été créé. Un e-mail de confirmation vous a été envoyé. Veuillez cliquer sur le lien dans l'e-mail pour activer votre compte, puis connectez-vous.
                    </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                    <AlertDialogAction onClick={handleDialogAction}>Aller à la page de connexion</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            <div className="min-h-screen flex items-center justify-center bg-pool-blue-50 p-4">
                <motion.div
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="w-full max-w-lg bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg p-8 space-y-6"
                >
                    <div className="text-center">
                         <Link to="/" className="absolute top-4 left-4 text-sm text-pool-blue-600 hover:underline">
                            &larr; Retour
                        </Link>
                        <Briefcase className="mx-auto h-12 w-12 text-pool-blue-500" />
                        <h1 className="text-3xl font-bold text-pool-blue-900 mt-4">Devenir Partenaire PRO</h1>
                        <p className="text-pool-blue-700/80">Rejoignez notre réseau et trouvez de nouveaux clients.</p>
                        {isUserConnected && (
                            <div className="mt-2 text-sm text-orange-600 bg-orange-100 p-3 rounded-md flex items-center gap-2">
                                <Info className="h-4 w-4 flex-shrink-0" />
                                <span>Vous êtes actuellement connecté. Créer un compte pro vous déconnectera de votre session actuelle.</span>
                            </div>
                        )}
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <h2 className="text-lg font-semibold text-pool-blue-800 border-b pb-2">Informations de connexion</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <CustomInput id="email" type="email" value={formData.email} onChange={handleChange} required icon={Mail} placeholder="vous@email.com"/>
                            </div>
                            <div>
                                <Label htmlFor="password">Mot de passe</Label>
                                <CustomInput id="password" type="password" value={formData.password} onChange={handleChange} required icon={Lock} placeholder="••••••••" autoComplete="new-password"/>
                            </div>
                        </div>

                         <h2 className="text-lg font-semibold text-pool-blue-800 border-b pb-2 pt-4">Informations sur votre entreprise</h2>
                         <div>
                            <Label htmlFor="nickname">Votre Nom / Gérant</Label>
                            <CustomInput id="nickname" type="text" value={formData.nickname} onChange={handleChange} required icon={User} placeholder="Jean Dupont"/>
                        </div>
                        <div>
                            <Label htmlFor="companyName">Nom de l'entreprise</Label>
                            <CustomInput id="companyName" type="text" value={formData.companyName} onChange={handleChange} required icon={Building} placeholder="Piscines & Co."/>
                        </div>
                        <div>
                            <Label htmlFor="siret">Numéro SIRET (Facultatif)</Label>
                            <CustomInput id="siret" type="text" value={formData.siret} onChange={handleChange} icon={Info} placeholder="12345678901234"/>
                        </div>
                        <div>
                            <Label htmlFor="phone">Téléphone</Label>
                            <CustomInput id="phone" type="tel" value={formData.phone} onChange={handleChange} required icon={Phone} placeholder="06 12 34 56 78"/>
                        </div>
                        <div className="relative">
                            <Label htmlFor="address">Adresse</Label>
                            <Input 
                                id="address" 
                                type="text" 
                                value={formData.address} 
                                onChange={handleAddressChange} 
                                onFocus={() => setIsAddressInputFocused(true)}
                                onBlur={() => setTimeout(() => setIsAddressInputFocused(false), 200)}
                                required 
                                placeholder="123 Rue de la Piscine" 
                                autoComplete="off"
                            />
                            {isAddressInputFocused && addressSuggestions.length > 0 && (
                                <ul className="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 max-h-48 overflow-y-auto shadow-lg">
                                    {addressSuggestions.map(suggestion => (
                                        <li 
                                            key={suggestion.properties.id}
                                            className="p-2 hover:bg-pool-blue-100 cursor-pointer"
                                            onMouseDown={() => handleSelectAddress(suggestion)}
                                        >
                                            {suggestion.properties.label}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                         <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                             <div>
                                <Label htmlFor="city">Ville</Label>
                                <CustomInput id="city" type="text" value={formData.city} onChange={handleChange} required icon={MapPin} placeholder="Paris"/>
                            </div>
                            <div>
                                <Label htmlFor="postalCode">Code Postal</Label>
                                <Input id="postalCode" type="text" value={formData.postalCode} onChange={handleChange} required placeholder="75001" />
                            </div>
                        </div>
                       
                        <Button type="submit" className="w-full" disabled={loading}>
                            {loading ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Création en cours...</> : "Créer mon compte PRO"}
                        </Button>
                    </form>
                    <p className="text-center text-sm text-pool-blue-800">
                        Déjà un compte PRO ? <Link to="/auth" className="font-medium text-pool-blue-600 hover:underline">Se connecter</Link>
                    </p>
                </motion.div>
            </div>
        </>
    );
}