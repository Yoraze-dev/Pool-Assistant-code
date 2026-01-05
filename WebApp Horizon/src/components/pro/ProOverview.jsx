import React, { useState, useEffect, useCallback } from 'react';
import { motion } from 'framer-motion';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarIcon, UserPlus, BarChart2, DollarSign, Check, X, AlertTriangle } from 'lucide-react';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { toast } from '@/components/ui/use-toast';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const ProOverview = () => {
    const { user } = useAuth();
    const [stats, setStats] = useState({ pending: 0, newClients: 0, avgRating: 0, monthlyRevenue: 0 });
    const [upcomingAppointments, setUpcomingAppointments] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchOverviewData = useCallback(async () => {
        if (!user) return;
        setLoading(true);

        const { data: proProfile, error: proError } = await supabase
            .from('professionals')
            .select('id')
            .eq('user_id', user.id)
            .single();

        if (proError || !proProfile) {
            console.error("Could not fetch professional profile:", proError);
            setLoading(false);
            return;
        }

        const { data: pendingBookings, error: pendingError } = await supabase
            .from('bookings')
            .select('id', { count: 'exact' })
            .eq('professional_id', proProfile.id)
            .eq('status', 'pending');
        
        const { data: upcomingBookingsData, error: upcomingError } = await supabase
            .from('bookings')
            .select('id, booking_time, status, user_profiles(nickname), professional_services(services(name))')
            .eq('professional_id', proProfile.id)
            .in('status', ['pending', 'confirmed'])
            .gte('booking_time', new Date().toISOString())
            .order('booking_time', { ascending: true })
            .limit(5);

        if (pendingError) console.error("Error fetching pending bookings:", pendingError);
        if (upcomingError) console.error("Error fetching upcoming bookings:", upcomingError);

        setStats(prev => ({ ...prev, pending: pendingBookings?.length || 0 }));
        setUpcomingAppointments(upcomingBookingsData || []);

        setLoading(false);
    }, [user]);

    useEffect(() => {
        fetchOverviewData();
    }, [fetchOverviewData]);

    const handleUpdateBookingStatus = async (bookingId, newStatus) => {
        const { error } = await supabase
            .from('bookings')
            .update({ status: newStatus })
            .eq('id', bookingId);

        if (error) {
            toast({ title: "Erreur", description: "Impossible de mettre à jour le rendez-vous.", variant: "destructive" });
        } else {
            toast({ title: "Succès", description: `Rendez-vous ${newStatus === 'confirmed' ? 'confirmé' : 'refusé'}.` });
            fetchOverviewData(); // Refresh all data
        }
    };

    const containerVariants = {
      hidden: { opacity: 0, y: 20 },
      visible: { opacity: 1, y: 0, transition: { staggerChildren: 0.1 } }
    };
    const itemVariants = { hidden: { opacity: 0, scale: 0.95 }, visible: { opacity: 1, scale: 1 } };
    
    return (
        <motion.div variants={containerVariants} className="space-y-6">
            <motion.div variants={itemVariants} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">RDV à valider</CardTitle>
                        <AlertTriangle className="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats.pending}</div>
                        {stats.pending > 0 && <p className="text-xs text-yellow-600">Action requise</p>}
                    </CardContent>
                </Card>
                 <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Nouveaux Clients</CardTitle>
                        <UserPlus className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">+12</div>
                        <p className="text-xs text-muted-foreground">Ce mois-ci</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Note Moyenne</CardTitle>
                        <BarChart2 className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">4.8/5</div>
                        <p className="text-xs text-muted-foreground">Basé sur 25 avis</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Revenus (Mensuel)</CardTitle>
                        <DollarSign className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">1,250€</div>
                        <p className="text-xs text-muted-foreground">+15% vs le mois dernier</p>
                    </CardContent>
                </Card>
            </motion.div>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <motion.div variants={itemVariants}>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                Rendez-vous à venir
                                {stats.pending > 0 && <Badge variant="destructive">{stats.pending} à valider</Badge>}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {loading ? <p>Chargement...</p> : upcomingAppointments.length > 0 ? (
                                 upcomingAppointments.map(app => (
                                    <div key={app.id} className="flex items-center justify-between p-2 hover:bg-pool-blue-50 rounded-lg">
                                        <div>
                                            <p className="font-semibold">{app.user_profiles.nickname}</p>
                                            <p className="text-sm text-muted-foreground">{app.professional_services.services.name}</p>
                                            <p className="text-sm font-medium">{format(new Date(app.booking_time), "dd/MM/yy 'à' HH:mm", {locale: fr})}</p>
                                        </div>
                                        {app.status === 'pending' ? (
                                            <div className="flex gap-1">
                                                <Button size="icon" variant="ghost" className="h-8 w-8 text-green-600 hover:bg-green-100" onClick={() => handleUpdateBookingStatus(app.id, 'confirmed')}><Check className="w-4 h-4" /></Button>
                                                <Button size="icon" variant="ghost" className="h-8 w-8 text-red-600 hover:bg-red-100" onClick={() => handleUpdateBookingStatus(app.id, 'cancelled')}><X className="w-4 h-4" /></Button>
                                            </div>
                                        ) : (
                                            <Badge variant={app.status === 'confirmed' ? 'default' : 'secondary'} className={app.status === 'confirmed' ? 'bg-green-100 text-green-800' : ''}>Confirmé</Badge>
                                        )}
                                    </div>
                                 ))
                            ) : <p className="text-muted-foreground">Aucun rendez-vous à venir.</p>}
                        </CardContent>
                    </Card>
                </motion.div>
                <motion.div variants={itemVariants}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Statistiques Rapides</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-40 flex items-center justify-center text-muted-foreground">Graphique à venir...</div>
                        </CardContent>
                    </Card>
                </motion.div>
            </div>
        </motion.div>
    );
};

export default ProOverview;