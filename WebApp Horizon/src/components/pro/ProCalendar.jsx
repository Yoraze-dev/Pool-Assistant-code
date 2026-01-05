
import React, { useState, useEffect, useCallback } from 'react';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import { toast } from '@/components/ui/use-toast';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { fr } from 'date-fns/locale';
import { Loader2 } from 'lucide-react';

const ProCalendar = () => {
    const { user } = useAuth();
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [allServices, setAllServices] = useState([]);
    const [filters, setFilters] = useState({ status: 'all', service: 'all' });

    const fetchCalendarData = useCallback(async () => {
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

        let query = supabase
            .from('bookings')
            .select(`
                id,
                booking_time,
                status,
                user_profiles (nickname),
                professional_services (duration_minutes, services(name, id))
            `)
            .eq('professional_id', proProfile.id);

        if (filters.status !== 'all') {
            query = query.eq('status', filters.status);
        }
        if (filters.service !== 'all') {
            query = query.eq('professional_services.services.id', filters.service);
        }

        const { data: bookings, error } = await query;

        if (error) {
            console.error("Error fetching bookings:", error);
            toast({ title: "Erreur", description: "Impossible de charger les rendez-vous." });
        } else {
            const formattedEvents = bookings.map(booking => ({
                id: booking.id,
                title: `${booking.user_profiles?.nickname || 'Client'} - ${booking.professional_services?.services?.name || 'Service'}`,
                start: new Date(booking.booking_time),
                end: new Date(new Date(booking.booking_time).getTime() + (booking.professional_services?.duration_minutes || 60) * 60000),
                classNames: [`status-${booking.status}`],
                extendedProps: {
                    status: booking.status
                }
            }));
            setEvents(formattedEvents);
        }
        setLoading(false);
    }, [user, filters]);

    const fetchServices = useCallback(async () => {
        const { data, error } = await supabase.from('services').select('*');
        if(error) console.error("Error fetching services for filter");
        else setAllServices(data);
    }, []);
    
    useEffect(() => {
        fetchCalendarData();
    }, [fetchCalendarData]);

    useEffect(() => {
        fetchServices();
    }, [fetchServices]);

    const handleFilterChange = (filterName, value) => {
        setFilters(prev => ({...prev, [filterName]: value}));
    };

    return (
        <Card>
            <CardContent className="p-4 space-y-4">
                <div className="flex flex-col sm:flex-row gap-4">
                    <Select value={filters.status} onValueChange={(value) => handleFilterChange('status', value)}>
                        <SelectTrigger className="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filtrer par statut" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les statuts</SelectItem>
                            <SelectItem value="pending">En attente</SelectItem>
                            <SelectItem value="confirmed">Confirmé</SelectItem>
                            <SelectItem value="cancelled">Annulé</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.service} onValueChange={(value) => handleFilterChange('service', value)}>
                        <SelectTrigger className="w-full sm:w-[180px]">
                            <SelectValue placeholder="Filtrer par service" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les services</SelectItem>
                            {allServices.map(service => (
                                <SelectItem key={service.id} value={service.id}>{service.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                     <Button variant="outline" onClick={() => toast({title: "🚧 Bientôt disponible!"})}>Filtrer par employé</Button>
                </div>
                
                {loading && <div className="flex justify-center items-center h-96"><Loader2 className="w-8 h-8 animate-spin text-pool-blue-500" /></div>}
                
                {!loading && (
                    <div className='relative'>
                        <FullCalendar
                            plugins={[dayGridPlugin, timeGridPlugin, listPlugin]}
                            initialView="timeGridWeek"
                            headerToolbar={{
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay,listYear'
                            }}
                            buttonText={{
                                today: "Aujourd'hui",
                                month: "Mois",
                                week: "Semaine",
                                day: "Jour",
                                list: "Année"
                            }}
                            locale={fr}
                            events={events}
                            allDaySlot={false}
                            slotMinTime="08:00:00"
                            slotMaxTime="20:00:00"
                            height="auto"
                            eventContent={renderEventContent}
                        />
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

function renderEventContent(eventInfo) {
  return (
    <>
      <b>{eventInfo.timeText}</b>
      <p className="whitespace-normal text-xs">{eventInfo.event.title}</p>
      <i className="capitalize text-xs opacity-80">{eventInfo.event.extendedProps.status}</i>
    </>
  );
}

export default ProCalendar;
