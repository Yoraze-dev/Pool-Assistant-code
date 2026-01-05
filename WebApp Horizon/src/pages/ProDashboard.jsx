import React, { useState, useEffect, useMemo } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { Routes, Route, useLocation } from 'react-router-dom';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { supabase } from '@/lib/customSupabaseClient';

import ProOverview from '@/components/pro/ProOverview';
import ProProfileServices from '@/components/pro/ProProfileServices';
import ProCalendar from '@/components/pro/ProCalendar';
import ProClients from '@/components/pro/ProClients';

export default function ProDashboard() {
    const { user } = useAuth();
    const location = useLocation();
    const [professionalProfile, setProfessionalProfile] = useState(null);

    useEffect(() => {
        const fetchProfile = async () => {
            if (!user) return;
            const { data, error } = await supabase
                .from('professionals')
                .select('id, company_name')
                .eq('user_id', user.id)
                .single();
            
            if (error) {
                console.error("Error fetching professional profile for dashboard:", error);
            } else if (data) {
                setProfessionalProfile(data);
            } else {
                setProfessionalProfile(null);
            }
        };
        fetchProfile();
    }, [user]);

    const pageTitle = useMemo(() => {
        const path = location.pathname;
        if (path.endsWith('/calendar')) return 'Agenda';
        if (path.endsWith('/clients')) return 'Clients';
        if (path.endsWith('/profile')) return 'Profil & Services';
        return 'Tableau de Bord PRO';
    }, [location.pathname]);

    return (
        <>
            <Helmet>
                <title>{pageTitle} - Pool Assistant</title>
            </Helmet>
            <motion.div
                key={location.pathname}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                transition={{ duration: 0.3 }}
            >
                 <div className="mb-6">
                    <h1 className="text-3xl font-bold text-pool-blue-900">{pageTitle}</h1>
                    <p className="text-pool-blue-700/80">Bienvenue, {professionalProfile?.company_name || user?.user_metadata?.nickname || 'Professionnel'} !</p>
                </div>

                <Routes>
                    <Route index element={<ProOverview />} />
                    <Route path="profile" element={<ProProfileServices professionalProfile={professionalProfile} />} />
                    <Route path="calendar" element={<ProCalendar />} />
                    <Route path="clients" element={<ProClients />} />
                </Routes>
            </motion.div>
        </>
    );
}