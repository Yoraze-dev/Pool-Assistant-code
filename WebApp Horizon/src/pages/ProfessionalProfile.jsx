import React, { useState, useEffect, useCallback } from 'react';
import { Helmet } from 'react-helmet';
import { useParams, Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { toast } from '@/components/ui/use-toast';
import BookingDialog from '@/components/BookingDialog';
import { Star, MapPin, Phone, MessageSquare, Wrench, Clock, Euro, Calendar, FileText } from 'lucide-react';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default function ProfessionalProfile() {
    const { id } = useParams();
    const { user } = useAuth();
    const [professional, setProfessional] = useState(null);
    const [services, setServices] = useState([]);
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isBookingOpen, setBookingOpen] = useState(false);
    const [selectedService, setSelectedService] = useState(null);

    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const { data: proData, error: proError } = await supabase
                .from('professionals')
                .select('*')
                .eq('id', id)
                .single();

            if (proError) throw proError;
            setProfessional(proData);

            const { data: servicesData, error: servicesError } = await supabase
                .from('professional_services')
                .select('*, services(id, name, description)')
                .eq('professional_id', id)
                .eq('is_active', true);

            if (servicesError) throw servicesError;
            setServices(servicesData);

            const { data: reviewsData, error: reviewsError } = await supabase
                .from('reviews')
                .select('*, user_profiles(nickname)')
                .eq('professional_id', id);

            if (reviewsError) throw reviewsError;
            setReviews(reviewsData);

        } catch (error) {
            console.error('Error fetching professional data:', error);
            toast({ title: 'Erreur', description: 'Impossible de charger le profil du professionnel.', variant: 'destructive' });
        } finally {
            setLoading(false);
        }
    }, [id]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const handleBookClick = (service) => {
        if (!user) {
            toast({ title: "Connexion requise", description: "Veuillez vous connecter pour prendre rendez-vous."});
            return;
        }
        setSelectedService(service);
        setBookingOpen(true);
    };

    if (loading) {
        return <div className="text-center p-10">Chargement du profil...</div>;
    }

    if (!professional) {
        return <div className="text-center p-10">Ce professionnel n'a pas été trouvé.</div>;
    }

    const position = [professional.latitude || 48.85, professional.longitude || 2.35];

    return (
        <>
            <Helmet>
                <title>{professional.company_name} - Pool Assistant</title>
            </Helmet>
            <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="max-w-6xl mx-auto p-4 space-y-8"
            >
                {/* Header */}
                <Card className="overflow-hidden">
                    <CardContent className="p-0">
                        <div className="h-48 bg-pool-blue-500 relative">
                            {professional.logo_url ?
                                <img  alt="Bannière" className="w-full h-full object-cover" src="https://images.unsplash.com/photo-1697829275977-c028a3ff9692" /> :
                                <div className="w-full h-full flex items-center justify-center bg-gradient-to-r from-pool-blue-400 to-teal-400"></div>
                            }
                            <div className="absolute top-1/2 left-8 transform -translate-y-1/3 flex items-center gap-6">
                                <div className="w-32 h-32 rounded-full bg-white shadow-lg flex items-center justify-center border-4 border-white">
                                    {professional.logo_url ? <img src={professional.logo_url} alt={`Logo de ${professional.company_name}`} className="w-full h-full rounded-full object-cover" /> : <Wrench className="w-16 h-16 text-pool-blue-500" />}
                                </div>
                                <div>
                                    <h1 className="text-4xl font-bold text-white shadow-text">{professional.company_name}</h1>
                                    <div className="flex items-center text-white mt-2 text-sm shadow-text">
                                        <MapPin className="w-4 h-4 mr-2" /> {professional.city}, {professional.postal_code}
                                        {reviews.length > 0 && <Star className="w-4 h-4 ml-4 mr-2" />}
                                        {reviews.length > 0 && <span>{ (reviews.reduce((acc, r) => acc + r.rating, 0) / reviews.length).toFixed(1) }/5 ({reviews.length} avis)</span>}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="p-6 flex justify-end gap-2">
                             <Button variant="outline"><Phone className="w-4 h-4 mr-2"/> Contacter</Button>
                             <Button asChild><Link to="/messaging"><MessageSquare className="w-4 h-4 mr-2"/> Envoyer un message</Link></Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Main Content */}
                <div className="grid md:grid-cols-3 gap-8">
                    {/* Left Column (Services) */}
                    <div className="md:col-span-2 space-y-6">
                        <Card>
                            <CardContent className="p-6">
                                <h2 className="text-2xl font-bold text-pool-blue-900 flex items-center"><FileText className="w-6 h-6 mr-3 text-pool-blue-500" />À propos</h2>
                                <p className="text-gray-600 mt-4">{professional.bio || "Ce professionnel n'a pas encore ajouté de biographie."}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-6">
                                <h2 className="text-2xl font-bold text-pool-blue-900 flex items-center"><Wrench className="w-6 h-6 mr-3 text-pool-blue-500" />Prestations</h2>
                                <div className="mt-4 space-y-4">
                                    {services.length > 0 ? services.map(service => (
                                        <div key={service.id} className="p-4 border rounded-lg flex justify-between items-center bg-gray-50 hover:bg-gray-100 transition-colors">
                                            <div>
                                                <h3 className="font-semibold text-lg">{service.services.name}</h3>
                                                <p className="text-sm text-gray-500 flex items-center gap-4 mt-1">
                                                    <span className="flex items-center"><Clock className="w-4 h-4 mr-1" />{service.duration_minutes} min</span>
                                                    <span className="flex items-center"><Euro className="w-4 h-4 mr-1" />{service.price}</span>
                                                </p>
                                                <p className="text-sm text-gray-600 mt-2">{service.services.description}</p>
                                            </div>
                                            <Button onClick={() => handleBookClick(service)}><Calendar className="w-4 h-4 mr-2"/>Prendre RDV</Button>
                                        </div>
                                    )) : <p>Ce professionnel n'a pas encore de services actifs.</p>}
                                </div>
                            </CardContent>
                        </Card>
                         <Card>
                            <CardContent className="p-6">
                                <h2 className="text-2xl font-bold text-pool-blue-900 flex items-center"><Star className="w-6 h-6 mr-3 text-pool-blue-500" />Avis des clients</h2>
                                 <div className="mt-4 space-y-4">
                                    {reviews.length > 0 ? reviews.map(review => (
                                        <div key={review.id} className="p-4 border-b">
                                            <div className="flex items-center justify-between">
                                                <p className="font-semibold">{review.user_profiles?.nickname || "Anonyme"}</p>
                                                <div className="flex items-center">
                                                    {[...Array(5)].map((_, i) => <Star key={i} className={`w-4 h-4 ${i < review.rating ? 'text-yellow-400' : 'text-gray-300'}`} fill="currentColor"/>)}
                                                </div>
                                            </div>
                                            <p className="text-gray-600 mt-2">{review.comment}</p>
                                        </div>
                                    )) : <p>Aucun avis pour le moment.</p>}
                                 </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column (Info & Map) */}
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="h-64 w-full p-0 rounded-lg overflow-hidden">
                                <MapContainer center={position} zoom={13} scrollWheelZoom={false} style={{ height: "100%", width: "100%" }}>
                                    <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                                    <Marker position={position}>
                                        <Popup>{professional.company_name}</Popup>
                                    </Marker>
                                </MapContainer>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </motion.div>
            
            <BookingDialog 
                open={isBookingOpen} 
                onOpenChange={setBookingOpen}
                professional={professional}
                service={selectedService}
                onBookingConfirmed={fetchData}
            />
        </>
    );
}
