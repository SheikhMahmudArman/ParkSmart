import { useState, useEffect } from 'react';
import { Card, Row, Col } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/admin/Reports.css';

const AdminReports = () => {
    const { user } = useAuth();
    const [reports, setReports] = useState({
        revenue: [],
        occupancy: 0,
        monthlyRevenue: 0,
        totalReservations: 0
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchReports = async () => {
            try {
                // Fetch revenue report
                const response = await fetch('http://localhost:8000/api/reports/revenue', {
                    headers: {
                        'Authorization': `Bearer ${user.token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch reports');
                const data = await response.json();
                setReports(data);
            } catch (error) {
                console.error('Error fetching reports:', error);
                // Use fallback data
                setReports({
                    revenue: [
                        { lot: 'Downtown Plaza', amount: 1240 },
                        { lot: 'Mall Square', amount: 980 },
                        { lot: 'Airport Terminal', amount: 1560 },
                        { lot: 'City Center', amount: 500 },
                    ],
                    occupancy: 67,
                    monthlyRevenue: 4280,
                    totalReservations: 142
                });
            } finally {
                setLoading(false);
            }
        };
        fetchReports();
    }, [user.token]);

    const peakHours = [
        { time: '8 AM – 10 AM', occupancy: '85%' },
        { time: '12 PM – 2 PM', occupancy: '72%' },
        { time: '5 PM – 7 PM', occupancy: '91%' },
        { time: '9 PM – 11 PM', occupancy: '34%' },
    ];

    if (loading) return <div className="text-center mt-5">Loading reports...</div>;

    return (
        <div className="fade-in admin-reports">
            <Row className="g-3 mb-4">
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">{reports.occupancy || 67}%</div>
                        <div className="label">Occupancy Rate</div>
                    </div>
                </Col>
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">${(reports.monthlyRevenue || 4280).toLocaleString()}</div>
                        <div className="label">Monthly Revenue</div>
                    </div>
                </Col>
                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">{reports.totalReservations || 142}</div>
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
                            {(reports.revenue || []).map((item, i) => (
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