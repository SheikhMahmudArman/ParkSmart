import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';

const DriverDashboard = () => {
    const { user } = useAuth();
    const [reservations, setReservations] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch(`http://localhost:8000/api/users/${user.id}/reservations`, {
            headers: { 
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                setReservations(data.reservations || []);
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    }, [user]);

    if (loading) return <div>Loading...</div>;

    return (
        <div>
            <h2>Dashboard</h2>
            {reservations.length === 0 ? (
                <p>No reservations found.</p>
            ) : (
                <ul>
                    {reservations.map(r => (
                        <li key={r.id}>{r.parking_space?.lot?.name} - {r.status}</li>
                    ))}
                </ul>
            )}
        </div>
    );
};
export default DriverDashboard;