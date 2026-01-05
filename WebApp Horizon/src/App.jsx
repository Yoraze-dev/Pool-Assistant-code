import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { Helmet } from 'react-helmet';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { Toaster } from '@/components/ui/toaster';

import Auth from '@/pages/Auth';
import ProSignUp from '@/pages/ProSignUp';
import MainLayout from '@/components/layout/MainLayout';
import DashboardSummary from '@/pages/DashboardSummary';
import Tests from '@/pages/Tests';
import Graph from '@/pages/Graph';
import MyPool from '@/pages/MyPool';
import Settings from '@/pages/Settings';
import FindProfessional from '@/pages/FindProfessional';
import ProfessionalProfile from '@/pages/ProfessionalProfile';
import Messaging from '@/pages/Messaging';
import ProDashboard from '@/pages/ProDashboard';
import Admin from '@/pages/Admin';

function App() {
  const { session, loading, role } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-pool-blue-50">
        <img alt="Pool Assistant Logo - Loading" className="w-48 h-auto animate-pulse" src="https://horizons-cdn.hostinger.com/9d64b00d-aa76-49d8-82ac-c98cff77592b/f98c9313387076e5efce20eb986f4791.png" />
      </div>
    );
  }
  
  const getHomeRoute = () => {
    if (!session) return "/auth";
    return role === 'professional' ? '/pro-dashboard' : '/';
  };

  const UserRoutes = ({ isAdmin }) => (
    <Routes>
      <Route index element={<DashboardSummary />} />
      <Route path="/tests" element={<Tests />} />
      <Route path="/graph" element={<Graph />} />
      <Route path="/my-pool" element={<MyPool />} />
      <Route path="/find-professional" element={<FindProfessional />} />
      <Route path="/professional/:id" element={<ProfessionalProfile />} />
      <Route path="/messaging/*" element={<Messaging />} />
      <Route path="/settings" element={<Settings />} />
      {isAdmin && <Route path="/admin" element={<Admin />} />}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );

  const ProRoutes = ({ isAdmin }) => (
    <Routes>
      <Route path="/pro-dashboard/*" element={<ProDashboard />} />
      <Route path="/messaging/*" element={<Messaging />} />
      <Route path="/settings" element={<Settings />} />
      {isAdmin && <Route path="/admin" element={<Admin />} />}
      <Route path="*" element={<Navigate to="/pro-dashboard" replace />} />
    </Routes>
  );

  const AppRoutes = ({ isAdmin }) => {
    if (role === 'user') return <UserRoutes isAdmin={isAdmin} />;
    if (role === 'professional') return <ProRoutes isAdmin={isAdmin} />;
    return <Routes><Route path="*" element={<Navigate to="/" replace />} /></Routes>;
  };

  return (
    <>
      <Helmet>
        <title>Pool Assistant - Tableau de Bord Intelligent</title>
        <meta name="description" content="Gérez votre piscine intelligemment avec Pool Assistant. Analyse de l'eau, recommandations personnalisées et suivi en temps réel." />
      </Helmet>
      <Router>
        <Routes>
          <Route path="/auth" element={!session ? <Auth /> : <Navigate to={getHomeRoute()} replace />} />
          <Route path="/pro-signup" element={<ProSignUp />} />
          <Route path="/*" element={session ? <MainLayout><AppRoutes isAdmin={session.user.email === 'mfxlm@proton.me'} /></MainLayout> : <Navigate to="/auth" replace />} />
        </Routes>
      </Router>
      <Toaster />
    </>
  );
}

export default App;