import { useState, useEffect } from 'react';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/staff/Spots.css';

const StaffSpots = () => {
    const { user } = useAuth();
    const [spots, setSpots] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('http://localhost:8000/api/spots', {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                setSpots(data);
                setLoading(false);
            })
            .catch(err => {
                console.error('Error fetching spots:', err);
                // Fallback data
                setSpots([
                    { id: 1, lot_name: 'Downtown Plaza', spot: 'A01-A20', status: 'Available', type: 'Standard' },
                    { id: 2, lot_name: 'Downtown Plaza', spot: 'A21-A30', status: 'Occupied', type: 'EV' },
                    { id: 3, lot_name: 'Mall Square', spot: 'B01-B15', status: 'Available', type: 'Standard' },
                ]);
                setLoading(false);
            });
    }, [user.token]);

    const toggleStatus = async (id) => {
        try {
            const spot = spots.find(s => s.id === id);
            const newStatus = spot.status === 'Available' ? 'Occupied' : 'Available';
            const response = await fetch(`http://localhost:8000/api/spots/${id}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });
            if (!response.ok) throw new Error('Failed to toggle status');
            setSpots(spots.map(s => s.id === id ? { ...s, status: newStatus } : s));
            alert(`Spot status toggled to ${newStatus}`);
        } catch (error) {
            console.error('Error toggling status:', error);
            alert('Failed to toggle spot status');
        }
    };

    if (loading) return <div className="text-center mt-5">Loading spots...</div>;

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    <div className="table-wrap spots-table">
                        <table className="table">
                            <thead>
                                <tr><th>Lot</th><th>Spot</th><th>Status</th><th>Type</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                {spots.map((s) => (
                                    <tr key={s.id}>
                                        <td>{s.lot_name || s.parking_lot?.name}</td>
                                        <td>{s.spot || s.space_number}</td>
                                        <td><span className={`badge bg-${(s.status || 'Available') === 'Available' ? 'success' : 'danger'} status-badge`}>{s.status}</span></td>
                                        <td>{s.type}</td>
                                        <td>
                                            <Button 
                                                size="sm" 
                                                variant="outline-secondary" 
                                                onClick={() => toggleStatus(s.id)}
                                            >
                                                Toggle
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card.Body>
            </Card>
        </div>
    );
};

export default StaffSpots;