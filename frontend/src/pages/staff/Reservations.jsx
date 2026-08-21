import { useState, useEffect } from 'react';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/staff/Reservations.css';

const StaffReservations = () => {
    const { user } = useAuth();
    const [reservations, setReservations] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('http://localhost:8000/api/reservations', {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                setReservations(data);
                setLoading(false);
            })
            .catch(err => {
                console.error('Error fetching reservations:', err);
                // Fallback data
                setReservations([
                    { id: 1, lot_name: 'Downtown Plaza', spot: 'A12', date: '2026-08-05', start: '09:00', end: '11:00', status: 'Pending', payment_status: 'Pending', vehicle_plate: 'ABC-123' },
                    { id: 2, lot_name: 'Mall Square', spot: 'B07', date: '2026-08-06', start: '14:00', end: '16:00', status: 'Confirmed', payment_status: 'Pending', vehicle_plate: 'XYZ-789' },
                ]);
                setLoading(false);
            });
    }, [user.token]);

    const updateStatus = async (id, newStatus) => {
        try {
            const response = await fetch(`http://localhost:8000/api/reservations/${id}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });
            if (!response.ok) throw new Error('Failed to update status');
            setReservations(reservations.map(r => r.id === id ? { ...r, status: newStatus } : r));
            alert(`Reservation #${id} updated to ${newStatus}`);
        } catch (error) {
            console.error('Error updating status:', error);
            alert('Failed to update reservation status');
        }
    };

    if (loading) return <div className="text-center mt-5">Loading reservations...</div>;

    return (
        <div className="fade-in staff-reservations">
            <Card>
                <Card.Body>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Lot</th><th>Spot</th><th>Vehicle</th><th>Date</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                {reservations.map((r) => (
                                    <tr key={r.id}>
                                        <td>{r.lot_name || r.parking_space?.parking_lot?.name}</td>
                                        <td>{r.spot || r.parking_space?.space_number}</td>
                                        <td>{r.vehicle_plate || r.vehicle?.plate_number}</td>
                                        <td>{r.date || new Date(r.reservation_time).toLocaleDateString()}</td>
                                        <td><span className={`badge bg-${r.status === 'Active' ? 'success' : r.status === 'Confirmed' ? 'primary' : r.status === 'Pending' ? 'warning' : 'secondary'}`}>{r.status}</span></td>
                                        <td><span className={`badge bg-${r.payment_status === 'Paid' ? 'success' : 'warning'}`}>{r.payment_status}</span></td>
                                        <td className="action-buttons">
                                            <Button size="sm" variant="success" onClick={() => updateStatus(r.id, 'Confirmed')}>Confirm</Button>
                                            <Button size="sm" variant="primary" onClick={() => updateStatus(r.id, 'Active')}>Activate</Button>
                                            <Button size="sm" variant="danger" onClick={() => updateStatus(r.id, 'Cancelled')}>Cancel</Button>
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

export default StaffReservations;