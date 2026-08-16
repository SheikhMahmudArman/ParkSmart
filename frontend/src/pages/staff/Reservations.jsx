import { useState } from 'react';
import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/staff/Reservations.css';

const initialReservations = [
    { id: 1, lot: 'Downtown Plaza', spot: 'A12', date: '2026-08-05', start: '09:00', end: '11:00', status: 'Pending', payment: 'Pending', vehicle: 'Toyota Camry' },
    { id: 2, lot: 'Mall Square', spot: 'B07', date: '2026-08-06', start: '14:00', end: '16:00', status: 'Confirmed', payment: 'Pending', vehicle: 'Honda Civic' },
    { id: 3, lot: 'Airport Terminal', spot: 'C03', date: '2026-08-04', start: '07:00', end: '09:00', status: 'Active', payment: 'Paid', vehicle: 'Tesla Model 3' },
];

const StaffReservations = () => {
    const [reservations, setReservations] = useState(initialReservations);

    const updateStatus = (id, newStatus) => {
        setReservations(reservations.map(r => r.id === id ? { ...r, status: newStatus } : r));
        alert(`Reservation #${id} updated to ${newStatus}`);
    };

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
                                        <td>{r.lot}</td>
                                        <td>{r.spot}</td>
                                        <td>{r.vehicle}</td>
                                        <td>{r.date} {r.start}</td>
                                        <td><span className={`badge bg-${r.status === 'Active' ? 'success' : r.status === 'Confirmed' ? 'primary' : r.status === 'Pending' ? 'warning' : 'secondary'}`}>{r.status}</span></td>
                                        <td><span className={`badge bg-${r.payment === 'Paid' ? 'success' : 'warning'}`}>{r.payment}</span></td>
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