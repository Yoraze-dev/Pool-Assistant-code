
import React from 'react';
import { motion } from 'framer-motion';
import { useLocation, useNavigate } from 'react-router-dom';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

function Navigation({ role, navItems }) {
    const location = useLocation();
    const navigate = useNavigate();
      
    const getTabValueFromPath = (pathname) => {
        const pathSegments = pathname.split('/').filter(Boolean);
        const base = pathSegments[0];

        if (role === 'professional') {
            if (base === 'pro-dashboard') {
                return pathSegments[1] || 'pro-dashboard';
            }
        } else {
            if (pathname === '/') return 'dashboard';
            if (base === 'tests') return 'tests';
            if (base === 'graph') return 'graph';
            if (base === 'my-pool') return 'my-pool';
            if (base === 'find-professional') return 'find-professional';
        }
        if (base === 'messaging') return 'messages';
        if (base === 'settings') return 'settings';
        if (base === 'admin') return 'admin';
        return role === 'professional' ? 'pro-dashboard' : 'dashboard';
    };

    const currentTab = getTabValueFromPath(location.pathname);

    return (
        <motion.nav
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="mb-8 hidden sm:block"
        >
            <Tabs value={currentTab} onValueChange={(value) => navigate(navItems.find(t => t.value === value).path)}>
                <TabsList className="w-full justify-center">
                {navItems.map((tab) => (
                    <TabsTrigger key={tab.value} value={tab.value} className="flex-shrink-0 flex items-center gap-0 md:gap-2 py-3 px-2 md:px-4">
                    <tab.icon className="w-5 h-5" />
                    <span className="hidden lg:inline">{tab.label}</span>
                    </TabsTrigger>
                ))}
                </TabsList>
            </Tabs>
        </motion.nav>
    );
}

export default Navigation;
