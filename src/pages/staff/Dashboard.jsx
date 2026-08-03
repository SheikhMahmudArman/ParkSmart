import { Card, Row, Col } from 'react-bootstrap';
import '../../styles/pages/staff/Dashboard.css';

const StaffDashboard = () => {
    const stats = [
        { label: "Today's Entries", value: '24', icon: 'bi-box-arrow-in-right', color: 'primary' },
        { label: "Today's Exits", value: '18', icon: 'bi-box-arrow-right', color: 'success' },
        { label: 'Active Reservations', value: '12', icon: 'bi-calendar-check', color: 'warning' },
        { label: 'Occupancy Rate', value: '67%', icon: 'bi-pie-chart', color: 'info' },
    ];

    // Sample logs
    const logs = [
        { vehicle: 'Toyota Camry', lot: 'Downtown Plaza', spot: 'A12', entry: '09:05', exit: '11:00', duration: '1h 55m', cost: 10.00 },
        { vehicle: 'Honda Civic', lot: 'Mall Square', spot: 'B07', entry: '14:10', exit: '15:50', duration: '1h 40m', cost: 6.00 },
    ];

    return (
        <div className="fade-in staff-dashboard">
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
                <Card.Header><i className="bi bi-clock-history me-2"></i>Recent Entry/Exit Logs</Card.Header>
                <Card.Body>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Vehicle</th><th>Lot</th><th>Spot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Cost</th></tr>
                            </thead>
                            <tbody>
                                {logs.map((log, i) => (
                                    <tr key={i}>
                                        <td>{log.vehicle}</td>
                                        <td>{log.lot}</td>
                                        <td>{log.spot}</td>
                                        <td>{log.entry}</td>
                                        <td>{log.exit}</td>
                                        <td>{log.duration}</td>
                                        <td>${log.cost.toFixed(2)}</td>
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

export default StaffDashboard;