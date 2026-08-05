import { Card, Row, Col } from 'react-bootstrap';
import '../../styles/pages/admin/Reports.css';

const AdminReports = () => {
    const revenueByLot = [
        { lot: 'Downtown Plaza', amount: 1240 },
        { lot: 'Mall Square', amount: 980 },
        { lot: 'Airport Terminal', amount: 1560 },
        { lot: 'City Center', amount: 500 },
    ];

    const peakHours = [
        { time: '8 AM – 10 AM', occupancy: '85%' },
        { time: '12 PM – 2 PM', occupancy: '72%' },
        { time: '5 PM – 7 PM', occupancy: '91%' },
        { time: '9 PM – 11 PM', occupancy: '34%' },
    ];

    return (
        <div className="fade-in admin-reports">
            <Row className="g-3 mb-4">
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">67%</div>
                        <div className="label">Occupancy Rate</div>
                    </div>
                </Col>
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">$4,280</div>
                        <div className="label">Monthly Revenue</div>
                    </div>
                </Col>
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">142</div>
                        <div className="label">Total Reservations</div>
                    </div>
                </Col>
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">96%</div>
                        <div className="label">Satisfaction</div>
                    </div>
                </Col>
            </Row>
            <Row className="g-4">
                <Col md={6}>
                    <Card>
                        <Card.Header>Revenue by Lot</Card.Header>
                        <Card.Body>
                            {revenueByLot.map((item, i) => (
                                <div key={i} className="revenue-item">
                                    <span>{item.lot}</span>
                                    <span className="fw-bold">${item.amount}</span>
                                </div>
                            ))}
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={6}>
                    <Card>
                        <Card.Header>Peak Hours</Card.Header>
                        <Card.Body>
                            {peakHours.map((item, i) => (
                                <div key={i} className="revenue-item">
                                    <span>{item.time}</span>
                                    <span className="fw-bold">{item.occupancy}</span>
                                </div>
                            ))}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default AdminReports;