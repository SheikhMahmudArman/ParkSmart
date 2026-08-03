import { Card, Row, Col } from 'react-bootstrap';
import '../../styles/pages/driver/Dashboard.css';

const DriverDashboard = () => {
    const stats = [
        { label: 'Active Reservations', value: '2', icon: 'bi-calendar-check', color: 'primary' },
        { label: 'Total Payments', value: '$52.00', icon: 'bi-credit-card', color: 'success' },
        { label: 'Notifications', value: '4', icon: 'bi-bell', color: 'warning' },
        { label: 'Spots Used', value: '8', icon: 'bi-car-front', color: 'info' },
    ];

    const reservations = [
        { id: 1, lot: 'Downtown Plaza', spot: 'A12', date: '2026-08-05', start: '09:00', end: '11:00', status: 'Active', payment: 'Paid', vehicle: 'Toyota Camry' },
        { id: 2, lot: 'Mall Square', spot: 'B07', date: '2026-08-06', start: '14:00', end: '16:00', status: 'Confirmed', payment: 'Pending', vehicle: 'Honda Civic' },
    ];

    return (
        <div className="fade-in dashboard-stats">
            <Row className="g-4 mb-4">
                {stats.map((s, idx) => (
                    <Col md={3} sm={6} key={idx}>
                        <div className="stat-card d-flex align-items-center gap-3">
                            <i className={`bi ${s.icon} fs-1 text-${s.color}`}></i>
                            <div>
                                <div className="value">{s.value}</div>
                                <div className="label">{s.label}</div>
                            </div>
                        </div>
                    </Col>
                ))}
            </Row>
            <Card>
                <Card.Header><i className="bi bi-clock-history me-2"></i>Recent Reservations</Card.Header>
                <Card.Body>
                    {reservations.length === 0 ? (
                        <p className="text-secondary">No reservations yet.</p>
                    ) : (
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr><th>Lot</th><th>Spot</th><th>Date</th><th>Status</th><th>Payment</th></tr>
                                </thead>
                                <tbody>
                                    {reservations.map((r) => (
                                        <tr key={r.id}>
                                            <td>{r.lot}</td>
                                            <td>{r.spot}</td>
                                            <td>{r.date} {r.start}-{r.end}</td>
                                            <td><span className={`badge bg-${r.status === 'Active' ? 'success' : r.status === 'Confirmed' ? 'primary' : r.status === 'Pending' ? 'warning' : 'secondary'}`}>{r.status}</span></td>
                                            <td><span className={`badge bg-${r.payment === 'Paid' ? 'success' : 'warning'}`}>{r.payment}</span></td>
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

export default DriverDashboard;