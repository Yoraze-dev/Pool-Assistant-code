import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';

const clients = [
    { id: 1, name: 'Charlie Dupont', lastBooking: '01/08/2025', totalSpent: '450€'},
    { id: 2, name: 'Diana Moreau', lastBooking: '28/07/2025', totalSpent: '200€'},
    { id: 3, name: 'Ethan Lefebvre', lastBooking: '15/07/2025', totalSpent: '1200€'},
];

const ProClients = () => {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Liste des Clients</CardTitle>
                <CardDescription>Gérez vos clients et consultez leur historique.</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm text-left">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" className="px-6 py-3">Nom</th>
                                <th scope="col" className="px-6 py-3">Dernier RDV</th>
                                <th scope="col" className="px-6 py-3">Dépenses totales</th>
                                <th scope="col" className="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {clients.map(client => (
                                <tr key={client.id} className="bg-white border-b hover:bg-gray-50">
                                    <td className="px-6 py-4 font-medium text-gray-900">{client.name}</td>
                                    <td className="px-6 py-4">{client.lastBooking}</td>
                                    <td className="px-6 py-4">{client.totalSpent}</td>
                                    <td className="px-6 py-4">
                                        <Button variant="ghost" size="sm">Voir profil</Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    )
};

export default ProClients;