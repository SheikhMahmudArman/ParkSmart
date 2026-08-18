import { useState, useEffect } from 'react';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';

const MyReservations = () => {
    const { user } = useAuth();
    const [reservations, setReservations] = useState([]);
    const [loading, setLoading] = useState(true);

    // Fetch reservations from backend
    useEffect(() => {
        fetch(`http://localhost:8000/api/users/${user.id}/reservations`, {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.reservations) {
                    setReservations(data.reservations);
                } else {
                    setReservations([]);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Error fetching reservations:', err);
                setLoading(false);
            });
    }, [user.id, user.token]);

    // Cancel reservation (DELETE)
    const handleCancel = (id) => {
        if (confirm('Cancel this reservation?')) {
            fetch(`http://localhost:8000/api/reservations/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(() => {
                    setReservations(reservations.filter(r => r.id !== id));
                    alert('Reservation cancelled successfully!');
                })
                .catch(err => console.error('Error cancelling:', err));
        }
    };

    if (loading) return <div className="text-center mt-5">Loading your reservations...</div>;

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    {reservations.length === 0 ? (
                        <p className="text-secondary">No reservations found.</p>
                    ) : (
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr><th>Lot</th><th>Spot</th><th>Date/Time</th><th>Status</th><th>Payment</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    {reservations.map((r) => (
                                        <tr key={r.id}>
                                            <td>{r.parking_space?.lot?.name || 'N/A'}</td>
                                            <td>{r.parking_space?.space_number || 'N/A'}</td>
                                            <td>{r.created_at ? new Date(r.created_at).toLocaleDateString() : 'N/A'}</td>
                                            <td><span className={`badge bg-${r.status === 'Active' ? 'success' : 'primary'}`}>{r.status || 'Pending'}</span></td>
                                            <td><span className={`badge bg-${r.payment_status === 'Paid' ? 'success' : 'warning'}`}>{r.payment_status || 'Pending'}</span></td>
                                            <td>
                                                {r.status !== 'Completed' && r.status !== 'Cancelled' && (
                                                    <Button variant="danger" size="sm" onClick={() => handleCancel(r.id)}>
                                                        <i className="bi bi-x-circle me-1"></i>Cancel
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card.Body>
            </Card>
        </div>
    );
};

export default MyReservations;