
import React, { useState, useEffect, useMemo } from 'react';
import { motion } from 'framer-motion';
import { useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { supabase } from '@/lib/customSupabaseClient';
import Header from './Header';
import Navigation from './Navigation';
import ChatWidget from '@/components/ChatWidget';
import { 
    LayoutDashboard, TestTube, LineChart, GlassWater as SwimmingPool, 
    Settings as SettingsIcon, Shield, Wrench, Briefcase, 
    MessageSquare, Users, Calendar 
} from 'lucide-react';

function MainLayout({ children }) {
  const [notifications, setNotifications] = useState([]);
  const location = useLocation();
  const { user, role } = useAuth();
  const [poolCity, setPoolCity] = useState(null);
  
  const isAdmin = useMemo(() => user?.email === 'mfxlm@proton.me', [user]);

  const navItems = useMemo(() => {
    let baseTabs = [];
    if (role === 'professional') {
        baseTabs = [
            { value: "pro-dashboard", label: "Aperçu", icon: LayoutDashboard, path: "/pro-dashboard" },
            { value: "calendar", label: "Agenda", icon: Calendar, path: "/pro-dashboard/calendar" },
            { value: "clients", label: "Clients", icon: Users, path: "/pro-dashboard/clients" },
            { value: "profile", label: "Profil & Services", icon: Briefcase, path: "/pro-dashboard/profile" },
            { value: "messages", label: "Messages", icon: MessageSquare, path: "/messaging" },
            { value: "settings", label: "Paramètres Pro", icon: SettingsIcon, path: "/settings" },
        ];
    } else {
         baseTabs = [
            { value: "dashboard", label: "Tableau de bord", icon: LayoutDashboard, path: "/" },
            { value: "tests", label: "Mes Tests", icon: TestTube, path: "/tests" },
            { value: "graph", label: "Graphique", icon: LineChart, path: "/graph" },
            { value: "my-pool", label: "Ma Piscine", icon: SwimmingPool, path: "/my-pool" },
            { value: "find-professional", label: "Trouver un Pro", icon: Wrench, path: "/find-professional" },
            { value: "messages", label: "Messages", icon: MessageSquare, path: "/messaging" },
            { value: "settings", label: "Paramètres", icon: SettingsIcon, path: "/settings" },
        ];
    }
    
    if (isAdmin) {
      const adminTab = { value: "admin", label: "Admin", icon: Shield, path: "/admin" };
      if (!baseTabs.find(t => t.value === 'admin')) {
        baseTabs.push(adminTab);
      }
    }
    return baseTabs;
  }, [isAdmin, role]);

  useEffect(() => {
    const fetchUserData = async () => {
        if (!user || role !== 'user') return;

        const { data: tests, error: testsError } = await supabase
            .from('tests')
            .select('created_at, ph, chlorine')
            .eq('user_id', user.id)
            .order('created_at', { ascending: false });

        let newNotifications = [];
        if (testsError) {
          console.error("Error fetching tests for notifications:", testsError);
        } else if (tests && tests.length > 0) {
            const lastTest = tests[0];
            const lastTestDate = new Date(lastTest.created_at);
            const daysSinceLastTest = (new Date() - lastTestDate) / (1000 * 3600 * 24);
            if (daysSinceLastTest > 7) {
                newNotifications.push({ id: `overdue-${user.id}`, message: "Plus de 7 jours depuis le dernier test. Pensez à analyser votre eau." });
            }
            if(lastTest.ph < 7.2 || lastTest.ph > 7.6) {
                 newNotifications.push({ id: `ph-alert-${user.id}`, message: `Alerte pH: Votre pH est à ${lastTest.ph}, il devrait être entre 7.2 et 7.6.` });
            }
             if(lastTest.chlorine < 1 || lastTest.chlorine > 3) {
                 newNotifications.push({ id: `cl-alert-${user.id}`, message: `Alerte Chlore: Votre chlore est à ${lastTest.chlorine}, il devrait être entre 1 et 3 ppm.` });
            }

        } else {
             newNotifications.push({ id: `welcome-${user.id}`, message: "Bienvenue ! Pensez à faire votre premier test." });
        }
        setNotifications(newNotifications);

        const { data: poolData, error: poolError } = await supabase
            .from('pools')
            .select('city')
            .eq('user_id', user.id)
            .limit(1);
        
        if (poolError) {
          console.error("Error fetching pool city:", poolError);
        } else if (poolData && poolData.length > 0) {
            setPoolCity(poolData[0].city);
        }
    };
    fetchUserData();
  }, [user, location.pathname, role]);

  return (
    <div className="min-h-screen bg-pool-blue-50 p-2 sm:p-4 text-pool-blue-900">
      <Header 
        poolCity={poolCity}
        notifications={notifications}
        role={role}
        navItems={navItems}
      />

      <Navigation role={role} isAdmin={isAdmin} navItems={navItems} />

      <main className="bg-white/70 backdrop-blur-lg rounded-xl border border-pool-blue-200 p-2 sm:p-6 shadow-sm">
          {children}
      </main>
      
      {role === 'user' && <ChatWidget hasNotifications={notifications.length > 0} />}
    </div>
  );
}

export default MainLayout;
