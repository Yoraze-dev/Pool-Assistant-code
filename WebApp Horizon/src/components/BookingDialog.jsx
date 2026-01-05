
import React, { useState, useEffect, useCallback } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { useAuth } from '@/contexts/SupabaseAuthContext';
import { supabase } from '@/lib/customSupabaseClient';
import { toast } from '@/components/ui/use-toast';
import { add, set, format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import { AlertCircle, CheckCircle, Clock, Send } from 'lucide-react';

const BookingDialog = ({ open, onOpenChange, professional, service, onBookingConfirmed }) => {
    const { user } = useAuth();
    const [step, setStep] = useState(1);
    const [selectedDate, setSelectedDate] = useState(undefined);
    const [selectedTime, setSelectedTime] = useState(null);
    const [availableSlots, setAvailableSlots] = useState([]);
    const [loadingSlots, setLoadingSlots] = useState(false);

    const getAvailableSlots = useCallback(async (date) => {
        if (!date || !professional || !service) return [];
        setLoadingSlots(true);

        const { data: availabilities, error: availabilitiesError } = await supabase
            .from('professional_availabilities')
            .select('*')
            .eq('professional_id', professional.id)
            .eq('day_of_week', date.getDay());

        if (availabilitiesError) {
            console.error(availabilitiesError);
            setLoadingSlots(false);
            return [];
        }

        const { data: bookings, error: bookingsError } = await supabase
            .from('bookings')
            .select('booking_time, professional_service_id(duration_minutes)')
            .eq('professional_id', professional.id)
            .gte('booking_time', format(date, 'yyyy-MM-dd HH:mm:ss'))
            .lt('booking_time', format(add(date, { days: 1 }), 'yyyy-MM-dd HH:mm:ss'));
        
        if (bookingsError) {
            console.error(bookingsError);
            setLoadingSlots(false);
            return [];
        }

        const bookedSlots = bookings.map(b => ({
            start: parseISO(b.booking_time),
            end: add(parseISO(b.booking_time), { minutes: b.professional_service_id.duration_minutes })
        }));

        const slots = [];
        const duration = service.duration_minutes;

        for (const availability of availabilities) {
            let currentTime = set(date, { 
                hours: parseInt(availability.start_time.split(':')[0]), 
                minutes: parseInt(availability.start_time.split(':')[1]),
                seconds: 0,
                milliseconds: 0
            });
            const endTime = set(date, { 
                hours: parseInt(availability.end_time.split(':')[0]), 
                minutes: parseInt(availability.end_time.split(':')[1]),
                seconds: 0,
                milliseconds: 0
            });

            while (add(currentTime, { minutes: duration }) <= endTime) {
                const slotEnd = add(currentTime, { minutes: duration });
                const isBooked = bookedSlots.some(booked => 
                    (currentTime >= booked.start && currentTime < booked.end) ||
                    (slotEnd > booked.start && slotEnd <= booked.end)
                );

                if (!isBooked && currentTime > new Date()) {
                    slots.push(new Date(currentTime));
                }
                currentTime = add(currentTime, { minutes: 15 }); // Intervalle de 15 minutes
            }
        }
        setLoadingSlots(false);
        return slots;
    }, [professional, service]);

    useEffect(() => {
        if (selectedDate) {
            getAvailableSlots(selectedDate).then(setAvailableSlots);
        } else {
            setAvailableSlots([]);
        }
        setSelectedTime(null);
    }, [selectedDate, getAvailableSlots]);

    useEffect(() => {
        if (!open) {
            // Reset state on close
            setTimeout(() => {
                setStep(1);
                setSelectedDate(undefined);
                setSelectedTime(null);
                setAvailableSlots([]);
            }, 300);
        }
    }, [open]);

    const handleConfirmBooking = async () => {
        if (!user || !professional || !service || !selectedTime) return;

        const { error } = await supabase.from('bookings').insert({
            user_id: user.id,
            professional_id: professional.id,
            professional_service_id: service.id,
            booking_time: selectedTime,
            status: 'pending' // Changed from 'confirmed' to 'pending'
        });

        if (error) {
            toast({
                title: 'Erreur de réservation',
                description: 'Impossible d\'envoyer la demande de rendez-vous. Veuillez réessayer.',
                variant: 'destructive',
            });
        } else {
            setStep(3); // Confirmation success step
            if (onBookingConfirmed) {
                onBookingConfirmed();
            }
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl bg-white text-pool-blue-900">
                <DialogHeader>
                    <DialogTitle className="text-2xl text-pool-blue-900">Prendre rendez-vous</DialogTitle>
                    <DialogDescription className="text-pool-blue-700/80">
                        {service?.services?.name} avec {professional?.company_name}
                    </DialogDescription>
                </DialogHeader>

                {step === 1 && (
                    <div className="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="font-semibold mb-2">1. Choisissez une date</h3>
                            <Calendar
                                mode="single"
                                selected={selectedDate}
                                onSelect={setSelectedDate}
                                locale={fr}
                                disabled={{ before: new Date() }}
                                className="rounded-md border"
                            />
                        </div>
                        <div>
                            <h3 className="font-semibold mb-2">2. Choisissez un créneau</h3>
                            <div className="grid grid-cols-3 gap-2 max-h-72 overflow-y-auto">
                                {loadingSlots && <p>Chargement des créneaux...</p>}
                                {!loadingSlots && availableSlots.length > 0 && availableSlots.map(slot => (
                                    <Button
                                        key={slot.toISOString()}
                                        variant={selectedTime?.getTime() === slot.getTime() ? 'default' : 'outline'}
                                        onClick={() => setSelectedTime(slot)}
                                    >
                                        {format(slot, 'HH:mm')}
                                    </Button>
                                ))}
                                {!loadingSlots && availableSlots.length === 0 && selectedDate && <p>Aucun créneau disponible.</p>}
                                {!selectedDate && <p>Veuillez sélectionner une date.</p>}
                            </div>
                        </div>
                    </div>
                )}
                
                {step === 2 && (
                     <div className="space-y-4">
                        <h3 className="font-bold text-lg">Confirmez votre demande</h3>
                        <div className="p-4 bg-pool-blue-50 rounded-lg space-y-2">
                           <p><strong>Professionnel :</strong> {professional.company_name}</p>
                           <p><strong>Service :</strong> {service.services.name}</p>
                           <p><strong>Date :</strong> {format(selectedTime, 'eeee dd MMMM yyyy', { locale: fr })}</p>
                           <p><strong>Heure :</strong> {format(selectedTime, 'HH:mm')}</p>
                           <p><strong>Prix :</strong> {service.price} €</p>
                        </div>
                         <div className="flex items-start p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <AlertCircle className="w-5 h-5 mr-3 text-yellow-500 mt-1" />
                            <p className="text-sm">Ceci est une demande de rendez-vous. Vous recevrez une confirmation du professionnel. Le paiement s'effectuera directement auprès de lui.</p>
                        </div>
                    </div>
                )}

                {step === 3 && (
                    <div className="text-center p-8 space-y-4">
                        <Send className="w-16 h-16 text-green-500 mx-auto" />
                        <h3 className="font-bold text-2xl">Demande Envoyée !</h3>
                        <p className="text-muted-foreground">
                            Votre demande de rendez-vous avec {professional.company_name} a bien été envoyée.
                        </p>
                        <p>Vous recevrez une notification dès que le professionnel aura confirmé.</p>
                        <Button onClick={() => onOpenChange(false)}>Fermer</Button>
                    </div>
                )}

                {step !== 3 && (
                    <DialogFooter>
                        {step === 1 && <Button onClick={() => setStep(2)} disabled={!selectedTime}>Suivant</Button>}
                        {step === 2 && (
                            <div className="flex gap-2">
                                <Button variant="outline" onClick={() => setStep(1)}>Retour</Button>
                                <Button onClick={handleConfirmBooking}>Envoyer la demande</Button>
                            </div>
                        )}
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
};

export default BookingDialog;
