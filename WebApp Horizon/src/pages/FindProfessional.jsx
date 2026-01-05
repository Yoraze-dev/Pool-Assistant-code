import React, { useState, useEffect, useCallback } from 'react';
import { Helmet } from 'react-helmet';
import { motion } from 'framer-motion';
import { Link, useNavigate } from 'react-router-dom';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Star, MapPin, Wrench, Search, Users, Calendar, Heart, Briefcase, Info } from 'lucide-react';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { toast } from '@/components/ui/use-toast';
import { useDebounce } from 'use-debounce';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

function ProfessionalCard({ pro }) {
  const avgRating = pro.avg_rating;
  const reviewCount = pro.review_count;

  return (
    <motion.div
      whileHover={{ y: -5, boxShadow: "0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)" }}
      className="bg-white rounded-xl shadow-md overflow-hidden"
    >
      <Link to={`/professional/${pro.id}`} className="block">
        <div className="p-6">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-xs uppercase tracking-wide text-pool-blue-500 font-semibold">{pro.company_name}</p>
              <h3 className="mt-1 text-lg leading-tight font-medium text-black">{pro.professional_services?.map(ps => ps.services.name).join(', ') || "Services variés"}</h3>
            </div>
            {pro.logo_url ? <img  className="w-12 h-12 rounded-full" alt={`Logo de ${pro.company_name}`} src="https://images.unsplash.com/photo-1485531865381-286666aa80a9" /> : <div className="w-12 h-12 rounded-full bg-pool-blue-100 flex items-center justify-center"><Wrench className="w-6 h-6 text-pool-blue-400"/></div>}
          </div>
          <div className="mt-4 flex items-center text-sm text-gray-500">
            <MapPin className="flex-shrink-0 mr-1.5 h-5 w-5 text-pool-blue-400" />
            <span>{pro.city}, {pro.postal_code}</span>
          </div>
           {avgRating && reviewCount > 0 && (
             <div className="mt-2 flex items-center text-sm text-gray-500">
               <Star className="flex-shrink-0 mr-1.5 h-5 w-5 text-yellow-400" />
               <span>{Number(avgRating).toFixed(1)} ({reviewCount} avis)</span>
             </div>
            )}
        </div>
      </Link>
    </motion.div>
  );
}

function MapUpdater({ center }) {
    const map = useMap();
    useEffect(() => {
        if (center) {
            map.setView(center, map.getZoom());
        }
    }, [center, map]);
    return null;
}

const MyBookings = () => {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const { user } = useAuth();
    
    const statusConfig = {
        pending: { label: "En attente", color: "bg-yellow-500" },
        confirmed: { label: "Confirmé", color: "bg-green-500" },
        cancelled: { label: "Refusé", color: "bg-red-500" },
        completed: { label: "Terminé", color: "bg-blue-500" }
    };

    useEffect(() => {
        const fetchBookings = async () => {
            if (!user) { setLoading(false); return; }
            const { data, error } = await supabase
                .from('bookings')
                .select(`
                    id, booking_time, status,
                    professionals (company_name),
                    professional_services (services(name))
                `)
                .eq('user_id', user.id)
                .order('booking_time', { ascending: false });
            
            if (error) {
                console.error(error);
                toast({ title: 'Erreur', description: "Impossible de charger vos rendez-vous." });
            } else {
                setBookings(data);
            }
            setLoading(false);
        };
        fetchBookings();
    }, [user]);

    if(loading) return <div className="text-center p-4">Chargement des rendez-vous...</div>
    if(bookings.length === 0) return (
        <Card className="text-center p-6 bg-pool-blue-50/50 border-dashed">
            <Info className="mx-auto h-12 w-12 text-pool-blue-400" />
            <h3 className="mt-4 text-lg font-medium text-pool-blue-900">Aucun rendez-vous</h3>
            <p className="mt-1 text-sm text-muted-foreground">
                Vous n'avez pas encore de rendez-vous programmé.
            </p>
        </Card>
    );

    return (
         <div className="space-y-4">
            {bookings.map(booking => (
                <Card key={booking.id} className="p-4 flex justify-between items-center">
                    <div>
                        <p className="font-bold">{booking.professional_services.services.name}</p>
                        <p>Avec : {booking.professionals.company_name}</p>
                        <p>Le : {new Date(booking.booking_time).toLocaleString('fr-FR', {dateStyle: 'long', timeStyle: 'short'})}</p>
                    </div>
                    <Badge className={`${statusConfig[booking.status]?.color || 'bg-gray-400'}`}>
                      {statusConfig[booking.status]?.label || 'Inconnu'}
                    </Badge>
                </Card>
            ))}
        </div>
    )
}

export default function FindProfessional() {
    const [professionals, setProfessionals] = useState([]);
    const [allServices, setAllServices] = useState([]);
    const [mapCenter, setMapCenter] = useState([48.8566, 2.3522]); 
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('pros');
    const [searchLocation, setSearchLocation] = useState('');
    const [debouncedLocation] = useDebounce(searchLocation, 500);
    const [selectedService, setSelectedService] = useState('all');
    const [loading, setLoading] = useState(false);

    const fetchProfessionals = useCallback(async () => {
        setLoading(true);
        try {
            let query = supabase
                .from('professionals_with_ratings')
                .select(`
                    *,
                    professional_services!inner(id, services(id, name))
                `);

            if (debouncedLocation) {
                query = query.or(`city.ilike.%${debouncedLocation}%,postal_code.ilike.%${debouncedLocation}%`);
            }

            if (selectedService !== 'all') {
                 query = query.filter('professional_services.service_id', 'eq', selectedService);
            }

            const { data, error } = await query;

            if (error) throw error;
            setProfessionals(data);
        } catch (error) {
            console.error("Error fetching professionals:", error);
            if (!error.message.includes('aborted')) {
              toast({ title: "Erreur", description: "Impossible de charger les professionnels.", variant: "destructive" });
            }
        } finally {
            setLoading(false);
        }
    }, [debouncedLocation, selectedService]);

    useEffect(() => {
        const fetchInitialData = async () => {
            try {
                let { data: servicesData, error: servicesError } = await supabase.from('services').select('*');
                if (servicesError) throw servicesError;
                setAllServices(servicesData);
            } catch (error) {
                console.error("Error fetching services:", error);
            }
        };
        fetchInitialData();
    }, []);

    useEffect(() => {
        if (activeTab === 'pros') {
            const controller = new AbortController();
            fetchProfessionals(controller.signal);
            return () => controller.abort();
        }
    }, [activeTab, fetchProfessionals]);

    const handleNotImplemented = () => {
        toast({
            title: "🚧 Bientôt disponible !",
            description: "Cette fonctionnalité est en cours de construction et arrivera très prochainement.",
        });
    };

    const renderContent = () => {
        switch (activeTab) {
            case 'bookings':
                return <MyBookings />;
            case 'pros':
            default:
                return (
                    <>
                        <Card>
                            <CardContent className="p-4 flex flex-col md:flex-row gap-4">
                                <div className="flex-1">
                                    <Label htmlFor="search-location" className="text-sm font-medium text-pool-blue-800">Ville ou code postal</Label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <Input 
                                            id="search-location"
                                            type="text" 
                                            placeholder="Ex: Paris, 75001" 
                                            className="w-full pl-10"
                                            value={searchLocation}
                                            onChange={(e) => setSearchLocation(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <Label htmlFor="search-service" className="text-sm font-medium text-pool-blue-800">Service recherché</Label>
                                    <Select value={selectedService} onValueChange={setSelectedService}>
                                        <SelectTrigger id="search-service">
                                            <SelectValue placeholder="Tous les services" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Tous les services</SelectItem>
                                            {allServices.map(service => <SelectItem key={service.id} value={service.id}>{service.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        <div>
                            <h2 className="text-2xl font-bold text-pool-blue-800 mb-4">Professionnels trouvés</h2>
                            {loading ? <div className="text-center p-4">Recherche en cours...</div> :
                                professionals.length > 0 ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {professionals.map(pro => (
                                            <ProfessionalCard key={pro.id} pro={pro} />
                                        ))}
                                    </div>
                                ) : (
                                    <Card className="text-center p-6 bg-pool-blue-50/50 border-dashed">
                                        <Info className="mx-auto h-12 w-12 text-pool-blue-400" />
                                        <h3 className="mt-4 text-lg font-medium text-pool-blue-900">Aucun résultat</h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Aucun professionnel ne correspond à vos critères de recherche.
                                        </p>
                                    </Card>
                                )
                            }
                        </div>
                    </>
                );
        }
    };


    return (
        <>
            <Helmet>
                <title>Trouver un Professionnel - Pool Assistant</title>
            </Helmet>
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
                className="space-y-8"
            >
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-pool-blue-900">Trouver un professionnel</h1>
                        <p className="text-pool-blue-700/80">Recherchez un expert pour l'entretien de votre piscine.</p>
                    </div>
                    <Button variant="link" onClick={() => navigate('/pro-signup')}>
                        <Briefcase className="mr-2 h-4 w-4" />
                        Je suis un professionnel
                    </Button>
                </div>
                
                <Card>
                    <CardContent className="p-0">
                         <div className="flex border-b">
                            <button className={`flex-1 p-4 flex items-center justify-center gap-2 font-medium ${activeTab === 'pros' ? 'border-b-2 border-pool-blue-500 text-pool-blue-600' : 'text-muted-foreground'}`} onClick={() => setActiveTab('pros')}>
                               <Search className="w-5 h-5"/> Recherche
                            </button>
                             <button className={`flex-1 p-4 flex items-center justify-center gap-2 font-medium ${activeTab === 'bookings' ? 'border-b-2 border-pool-blue-500 text-pool-blue-600' : 'text-muted-foreground'}`} onClick={() => setActiveTab('bookings')}>
                               <Calendar className="w-5 h-5"/> Mes RDV
                            </button>
                             <button className={`flex-1 p-4 flex items-center justify-center gap-2 font-medium ${activeTab === 'favorites' ? 'border-b-2 border-pool-blue-500 text-pool-blue-600' : 'text-muted-foreground'}`} onClick={handleNotImplemented}>
                               <Heart className="w-5 h-5"/> Favoris
                            </button>
                        </div>
                        <div className="p-6">
                            {renderContent()}
                        </div>
                    </CardContent>
                </Card>

            </motion.div>
        </>
    );
}
