
import React, { useState, useEffect, useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { FileBadge as CheckBadgeIcon, Trash2, PlusCircle, Loader2 } from 'lucide-react';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { supabase } from '@/lib/customSupabaseClient';

const ManageProfileDialog = ({ open, onOpenChange, profile, onUpdate }) => {
    const [formData, setFormData] = useState({});
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (profile) {
            setFormData(profile);
        }
    }, [profile]);
    
    const handleChange = (e) => setFormData({ ...formData, [e.target.name]: e.target.value });
    
    const handleSubmit = async () => {
        setLoading(true);
        await onUpdate(formData);
        setLoading(false);
    };
    
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader><DialogTitle>Modifier mon profil public</DialogTitle></DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="company_name" className="text-right">Nom</Label>
                        <Input id="company_name" name="company_name" value={formData.company_name || ''} onChange={handleChange} className="col-span-3" />
                    </div>
                     <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="bio" className="text-right">Bio</Label>
                        <Textarea id="bio" name="bio" value={formData.bio || ''} onChange={handleChange} className="col-span-3" />
                    </div>
                </div>
                <DialogFooter>
                    <Button onClick={handleSubmit} disabled={loading}>
                        {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Enregistrer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};

const ManageServicesDialog = ({ open, onOpenChange, services, baseServices, professionalProfile, onUpdate }) => {
    const [localServices, setLocalServices] = useState([]);
    const [isAdding, setIsAdding] = useState(false);
    const [newService, setNewService] = useState({ service_id: '', price: '', duration_minutes: '', is_active: true });
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setLocalServices(services ? JSON.parse(JSON.stringify(services)) : []);
    }, [services, open]);

    const handleUpdate = (index, field, value) => {
        const updated = [...localServices];
        updated[index][field] = value;
        setLocalServices(updated);
    };
    
    const handleAddNew = () => {
        if (!newService.service_id) {
            toast({ title: "Erreur", description: "Veuillez choisir un service.", variant: "destructive"});
            return;
        }
        const serviceToAdd = {
            ...newService,
            id: `new-${Date.now()}`,
            professional_id: professionalProfile.id,
            services: { name: baseServices.find(s => s.id === newService.service_id)?.name }
        };
        setLocalServices([...localServices, serviceToAdd]);
        setNewService({ service_id: '', price: '', duration_minutes: '', is_active: true });
        setIsAdding(false);
    };

    const handleSave = async () => {
        setLoading(true);
        await onUpdate(localServices);
        setLoading(false);
    };
    
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl bg-white">
                <DialogHeader>
                    <DialogTitle>Gérer mes services</DialogTitle>
                    <DialogDescription>Ajoutez ou modifiez les prestations que vous proposez.</DialogDescription>
                </DialogHeader>
                <div className="py-4 max-h-[60vh] overflow-y-auto pr-4">
                    {localServices.map((service, index) => (
                        <div key={service.id} className="flex items-center gap-2 mb-4 p-3 border rounded-lg bg-gray-50">
                            <div className="flex-1 space-y-2">
                                 <p className="font-semibold">{service.services.name}</p>
                                 <div className="flex items-center gap-2">
                                    <Input type="number" placeholder="Prix (€)" value={service.price || ''} onChange={e => handleUpdate(index, 'price', e.target.value)} className="w-24 bg-white"/>
                                    <Input type="number" placeholder="Durée (min)" value={service.duration_minutes || ''} onChange={e => handleUpdate(index, 'duration_minutes', e.target.value)} className="w-28 bg-white"/>
                                 </div>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Switch checked={service.is_active} onCheckedChange={checked => handleUpdate(index, 'is_active', checked)}/>
                                <Button variant="ghost" size="icon" onClick={() => setLocalServices(localServices.filter((_, i) => i !== index))}><Trash2 className="w-4 h-4 text-red-500"/></Button>
                            </div>
                        </div>
                    ))}
                    
                    {isAdding && (
                         <div className="flex items-center gap-2 mb-4 p-3 border rounded-lg border-dashed">
                            <div className="flex-1 space-y-2">
                                <Select onValueChange={value => setNewService({...newService, service_id: value})}>
                                    <SelectTrigger><SelectValue placeholder="Choisir un service" /></SelectTrigger>
                                    <SelectContent>
                                        {baseServices.filter(bs => !localServices.some(ls => ls.service_id === bs.id)).map(bs => (
                                            <SelectItem key={bs.id} value={bs.id}>{bs.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <div className="flex items-center gap-2">
                                    <Input type="number" placeholder="Prix (€)" value={newService.price} onChange={e => setNewService({...newService, price: e.target.value})} className="w-24 bg-white"/>
                                    <Input type="number" placeholder="Durée (min)" value={newService.duration_minutes} onChange={e => setNewService({...newService, duration_minutes: e.target.value})} className="w-28 bg-white"/>
                                </div>
                            </div>
                            <Button onClick={handleAddNew}>Ajouter</Button>
                        </div>
                    )}
                    
                    {!isAdding && <Button variant="outline" onClick={() => setIsAdding(true)} className="w-full"><PlusCircle className="w-4 h-4 mr-2"/> Ajouter un service</Button>}
                </div>
                <DialogFooter>
                    <Button onClick={handleSave} disabled={loading}>
                        {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Enregistrer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};

const ProProfileServices = ({ professionalProfile }) => {
    const { user } = useAuth();
    const [profile, setProfile] = useState(null);
    const [services, setServices] = useState([]);
    const [baseServices, setBaseServices] = useState([]);
    const [isProfileDialogOpen, setProfileDialogOpen] = useState(false);
    const [isServicesDialogOpen, setServicesDialogOpen] = useState(false);

    const fetchProfileData = useCallback(async () => {
        if (!user || !professionalProfile) return;
        const { data, error } = await supabase.from('professionals').select('*').eq('id', professionalProfile.id).single();
        if (error) console.error("Profile fetch error:", error);
        else setProfile(data);
    }, [user, professionalProfile]);

    const fetchServicesData = useCallback(async () => {
        if (!user || !professionalProfile) return;
        const { data: proServicesData, error: proServicesError } = await supabase.from('professional_services').select('*, services(id, name)').eq('professional_id', professionalProfile.id);
        if (proServicesError) console.error("Pro services fetch error:", proServicesError);
        else {
            const mappedServices = proServicesData.map(s => ({...s, service_id: s.services.id, services: {name: s.services.name}}));
            setServices(mappedServices);
        }
        
        const { data: baseServicesData, error: baseServicesError } = await supabase.from('services').select('*');
        if (baseServicesError) console.error("Base services fetch error:", baseServicesError);
        else setBaseServices(baseServicesData);
    }, [user, professionalProfile]);

    useEffect(() => {
        if (professionalProfile) {
            fetchProfileData();
            fetchServicesData();
        }
    }, [professionalProfile, fetchProfileData, fetchServicesData]);

    const handleProfileUpdate = async (updatedData) => {
        const { id, user_id, created_at, updated_at, ...updatePayload } = updatedData;
        const { error } = await supabase.from('professionals').update(updatePayload).eq('id', id);
        if (error) {
            toast({ title: "Erreur", description: "La mise à jour du profil a échoué. " + error.message, variant: "destructive" });
        } else {
            toast({ title: "Succès", description: "Profil mis à jour." });
            setProfileDialogOpen(false);
            fetchProfileData();
        }
    };

    const handleServicesUpdate = async (updatedServices) => {
        if (!professionalProfile) return;

        const toDelete = services.filter(s => !updatedServices.some(us => us.id === s.id)).map(s => s.id);
        const toUpsert = updatedServices.map(s => ({
            id: typeof s.id === 'string' && s.id.startsWith('new-') ? undefined : s.id,
            professional_id: professionalProfile.id,
            service_id: s.service_id,
            price: s.price,
            duration_minutes: s.duration_minutes,
            is_active: s.is_active
        }));
        
        if (toDelete.length > 0) {
            const { error: deleteError } = await supabase.from('professional_services').delete().in('id', toDelete);
            if (deleteError) {
                toast({ title: "Erreur", description: "La suppression d'anciens services a échoué. " + deleteError.message, variant: "destructive" });
                return;
            }
        }

        if (toUpsert.length > 0) {
            const { error: upsertError } = await supabase.from('professional_services').upsert(toUpsert, { onConflict: 'id' });
            if (upsertError) {
                 toast({ title: "Erreur", description: "La mise à jour des services a échoué. " + upsertError.message, variant: "destructive" });
                 console.error("Service update error", upsertError);
                 return;
            }
        }
        
        toast({ title: "Succès", description: "Services mis à jour." });
        setServicesDialogOpen(false);
        await fetchServicesData();
    };

    const badgeStatus = profile?.is_verified 
        ? { text: 'Pro Vérifié', color: 'bg-green-100 text-green-800' }
        : { text: 'Badge "pro vérifié" en attente de validation', color: 'bg-yellow-100 text-yellow-800' };
    
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
                <CardHeader><CardTitle>Gestion du Profil Public</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-muted-foreground">Modifiez vos informations, logo, description, et zone d'intervention.</p>
                    <Button className="w-full" onClick={() => setProfileDialogOpen(true)} disabled={!profile}>Modifier mon profil</Button>
                    <div className="mt-4">
                      <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${badgeStatus.color}`}>
                        <CheckBadgeIcon className="w-4 h-4 mr-2"/>
                        {badgeStatus.text}
                      </span>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle>Gestion des Services</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                     <p className="text-muted-foreground">Ajoutez, modifiez ou supprimez les prestations que vous proposez.</p>
                     <Button className="w-full" onClick={() => setServicesDialogOpen(true)} disabled={!profile}>Gérer mes services</Button>
                </CardContent>
            </Card>

            {profile && <ManageProfileDialog open={isProfileDialogOpen} onOpenChange={setProfileDialogOpen} profile={profile} onUpdate={handleProfileUpdate} />}
            {profile && <ManageServicesDialog open={isServicesDialogOpen} onOpenChange={setServicesDialogOpen} services={services} baseServices={baseServices} professionalProfile={profile} onUpdate={handleServicesUpdate} />}
        </div>
    );
};

export default ProProfileServices;
