
import React from 'react';
import { motion } from 'framer-motion';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { toast } from '@/components/ui/use-toast';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from "@/components/ui/dropdown-menu";
import { Bell, Plus, LogOut, Menu, Home, MessageSquare } from 'lucide-react';
import WeatherWidget from './WeatherWidget';

function Header({ poolCity, notifications, role, navItems }) {
    const { user, signOut } = useAuth();
    const navigate = useNavigate();
    const defaultPath = role === 'professional' ? '/pro-dashboard' : '/';

    const handleNewTestClick = () => navigate('/tests');

    const handleNotificationsClick = () => {
        if (notifications.length > 0) {
            notifications.forEach(notif => {
                toast({ title: "🔔 Notification", description: notif.message });
            });
        } else {
            toast({ title: "🔔 Notifications", description: "Vous n'avez aucune nouvelle notification." });
        }
    };
    
    // Ajout d'une notification pour la messagerie
    const unreadMessagesCount = user?.unreadMessagesCount || 0;

    return (
        <motion.header 
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          className="flex flex-row justify-between items-center mb-6 gap-2"
        >
          <div className="flex items-center gap-2 sm:gap-4">
             <div className="sm:hidden">
                 <Button variant="ghost" size="icon" onClick={() => navigate(defaultPath)}>
                    <Home className="h-6 w-6" />
                 </Button>
             </div>
             <div className="hidden sm:block flex-shrink-0">
                <Link to={defaultPath}>
                    <img alt="Pool Assistant Logo" className="h-16 w-auto" src="https://horizons-cdn.hostinger.com/9d64b00d-aa76-49d8-82ac-c98cff77592b/f98c9313387076e5efce20eb986f4791.png" />
                </Link>
             </div>
          </div>
          
          <div className="flex-shrink-0 sm:hidden">
             <Link to={defaultPath}>
                <img alt="Pool Assistant Logo" className="h-12 w-auto" src="https://horizons-cdn.hostinger.com/9d64b00d-aa76-49d8-82ac-c98cff77592b/f98c9313387076e5efce20eb986f4791.png" />
             </Link>
          </div>

          <div className="flex items-center justify-end space-x-1 sm:space-x-4">
            {poolCity && role === 'user' && <div className="hidden lg:block"><WeatherWidget city={poolCity} /></div>}
            
            <motion.div whileHover={{ scale: 1.05 }} className="relative">
                <Button 
                  variant="outline" 
                  size="icon"
                  className="bg-white/60 border-pool-blue-200 text-pool-blue-800 hover:bg-white rounded-xl"
                  onClick={() => navigate('/messaging')}
                >
                  <MessageSquare className="w-5 h-5" />
                  {unreadMessagesCount > 0 && (
                    <span className="absolute -top-2 -right-2 w-5 h-5 bg-red-500 rounded-full text-xs flex items-center justify-center text-white animate-pulse">
                      {unreadMessagesCount}
                    </span>
                  )}
                </Button>
            </motion.div>

            {role === 'user' && (
              <motion.div whileHover={{ scale: 1.05 }} className="relative hidden sm:block">
                <Button 
                  variant="outline" 
                  size="icon"
                  className="bg-white/60 border-pool-blue-200 text-pool-blue-800 hover:bg-white rounded-xl"
                  onClick={handleNotificationsClick}
                >
                  <Bell className="w-5 h-5" />
                  {notifications.length > 0 && (
                    <span className="absolute -top-2 -right-2 w-5 h-5 bg-red-500 rounded-full text-xs flex items-center justify-center text-white">
                      {notifications.length}
                    </span>
                  )}
                </Button>
              </motion.div>
            )}
            
            {role === 'user' && (
                <Button 
                  onClick={handleNewTestClick}
                  className="bg-pool-blue-400 hover:bg-pool-blue-500 text-white font-semibold px-2 sm:px-6 rounded-xl"
                >
                  <Plus className="w-5 h-5 sm:mr-2" />
                  <span className="hidden sm:inline">Nouveau Test</span>
                </Button>
            )}
            
            <div className="sm:hidden">
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon">
                      <Menu className="h-6 w-6" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="w-56 bg-white/80 backdrop-blur-lg border-pool-blue-200">
                    {navItems.map(item => (
                        <DropdownMenuItem key={item.value} onClick={() => navigate(item.path)} className="flex items-center gap-2">
                            <item.icon className="w-4 h-4" />
                            <span>{item.label}</span>
                        </DropdownMenuItem>
                    ))}
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onClick={signOut} className="flex items-center gap-2 text-red-500">
                        <LogOut className="w-4 h-4" />
                        <span>Déconnexion</span>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <Button variant="outline" size="icon" className="hidden sm:inline-flex bg-white/60 border-pool-blue-200 text-pool-blue-800 hover:bg-white rounded-xl" onClick={signOut}>
                <LogOut className="h-5 w-5" />
            </Button>
          </div>
        </motion.header>
    );
}

export default Header;
