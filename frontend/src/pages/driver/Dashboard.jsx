import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';

const DriverDashboard = () => {
    const { user } = useAuth();
    const [reservations, setReservations] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Fetch user's reservations (assuming endpoint exists)
        fetch(`http://localhost:8000/api/users/${user.id}/reservations`, {
            headers: { 'Authorization': `Bearer ${user.token}` }
        })
            .then(res => res.json())
            .then(data => {
                setReservations(data);
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
            {/* render reservations */}
        </div>
    );
};
export default DriverDashboard;