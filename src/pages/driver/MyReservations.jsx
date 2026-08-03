import { useState } from 'react';
import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/driver/MyReservations.css';

const initialReservations = [
    { id: 1, lot: 'Downtown Plaza', spot: 'A12', date: '2026-08-05', start: '09:00', end: '11:00', status: 'Active', payment: 'Paid', vehicle: 'Toyota Camry' },
    { id: 2, lot: 'Mall Square', spot: 'B07', date: '2026-08-06', start: '14:00', end: '16:00', status: 'Confirmed', payment: 'Pending', vehicle: 'Honda Civic' },
    { id: 3, lot: 'Airport Terminal', spot: 'C03', date: '2026-08-04', start: '07:00', end: '09:00', status: 'Completed', payment: 'Paid', vehicle: 'Tesla Model 3' },
];

const MyReservations = () => {
    const [reservations, setReservations] = useState(initialReservations);

    const handleCancel = (id) => {
        if (confirm('Cancel this reservation?')) {
            setReservations(reservations.filter(r => r.id !== id));
        }
    };

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    {reservations.length === 0 ? (
                        <p className="text-secondary">No reservations found.</p>
                    ) : (
                        <div className="table-wrap reservation-table">
                            <table className="table">
                                <thead>
                                    <tr><th>Lot</th><th>Spot</th><th>Date/Time</th><th>Status</th><th>Payment</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    {reservations.map((r) => (
                                        <tr key={r.id}>
                                            <td>{r.lot}</td>
                                            <td>{r.spot}</td>
                                            <td>{r.date} {r.start}-{r.end}</td>
                                            <td><span className={`badge bg-${r.status === 'Active' ? 'success' : r.status === 'Confirmed' ? 'primary' : r.status === 'Pending' ? 'warning' : 'secondary'}`}>{r.status}</span></td>
                                            <td><span className={`badge bg-${r.payment === 'Paid' ? 'success' : 'warning'}`}>{r.payment}</span></td>
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