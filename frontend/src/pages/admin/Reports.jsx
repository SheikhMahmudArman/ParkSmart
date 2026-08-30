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

    const [reservationsByLot, setReservationsByLot] = useState([]);
    const [spendingByDriver, setSpendingByDriver] = useState([]);

    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchReports = async () => {
            try {
                const headers = {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                };

                // Fetch existing revenue report
                const revenueResponse = await fetch(
                    'http://localhost:8000/api/reports/revenue',
                    { headers }
                );

                if (!revenueResponse.ok) {
                    throw new Error('Failed to fetch revenue report');
                }

                const revenueData = await revenueResponse.json();

                // Fetch Reservations Per Lot
                const reservationsResponse = await fetch(
                    'http://localhost:8000/api/reports/reservations-by-lot',
                    { headers }
                );

                if (!reservationsResponse.ok) {
                    throw new Error('Failed to fetch reservations per lot');
                }

                const reservationsData = await reservationsResponse.json();

                // Fetch Total Spending By Driver
                const spendingResponse = await fetch(
                    'http://localhost:8000/api/reports/spending-by-driver',
                    { headers }
                );

                if (!spendingResponse.ok) {
                    throw new Error('Failed to fetch driver spending');
                }

                const spendingData = await spendingResponse.json();

                // Store all data
                setReports(revenueData);
                setReservationsByLot(reservationsData);
                setSpendingByDriver(spendingData);

            } catch (error) {
                console.error('Error fetching reports:', error);

                // Keep existing fallback data
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

    if (loading) {
        return (
            <div className="text-center mt-5">
                Loading reports...
            </div>
        );
    }

    return (
        <div className="fade-in admin-reports">

            {/* ==================== SUMMARY CARDS ==================== */}
            <Row className="g-3 mb-4">

                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">
                            {reports.occupancy || 67}%
                        </div>
                        <div className="label">
                            Occupancy Rate
                        </div>
                    </div>
                </Col>

                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">
                            ${(reports.monthlyRevenue || 4280).toLocaleString()}
                        </div>
                        <div className="label">
                            Monthly Revenue
                        </div>
                    </div>
                </Col>

                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">
                            {reports.totalReservations || 142}
                        </div>
                        <div className="label">
                            Total Reservations
                        </div>
                    </div>
                </Col>

                <Col md={3}>
                    <div className="stat-card report-card text-center">
                        <div className="value">
                            96%
                        </div>
                        <div className="label">
                            Satisfaction
                        </div>
                    </div>
                </Col>

            </Row>

            {/* ==================== EXISTING REPORTS ==================== */}
            <Row className="g-4 mb-4">

                {/* Revenue by Lot */}
                <Col md={6}>
                    <Card>
                        <Card.Header>
                            Revenue by Lot
                        </Card.Header>

                        <Card.Body>
                            {(reports.revenue || []).map((item, i) => (
                                <div
                                    key={i}
                                    className="revenue-item"
                                >
                                    <span>{item.lot}</span>
                                    <span className="fw-bold">
                                        ${item.amount}
                                    </span>
                                </div>
                            ))}
                        </Card.Body>
                    </Card>
                </Col>

                {/* Peak Hours */}
                <Col md={6}>
                    <Card>
                        <Card.Header>
                            Peak Hours
                        </Card.Header>

                        <Card.Body>
                            {peakHours.map((item, i) => (
                                <div
                                    key={i}
                                    className="revenue-item"
                                >
                                    <span>{item.time}</span>
                                    <span className="fw-bold">
                                        {item.occupancy}
                                    </span>
                                </div>
                            ))}
                        </Card.Body>
                    </Card>
                </Col>

            </Row>

            {/* ==================== ASSIGNMENT REPORTS ==================== */}
            <Row className="g-4">

                {/* Reservations Per Lot */}
                <Col md={6}>
                    <Card>
                        <Card.Header>
                            <i className="bi bi-calendar-check me-2"></i>
                            Reservations Per Lot
                        </Card.Header>

                        <Card.Body>
                            {reservationsByLot.length === 0 ? (
                                <p className="text-secondary text-center mb-0">
                                    No reservation data found.
                                </p>
                            ) : (
                                reservationsByLot.map((item, i) => (
                                    <div
                                        key={i}
                                        className="revenue-item"
                                    >
                                        <span>
                                            {item.lot_name}
                                        </span>

                                        <span className="fw-bold">
                                            {item.total_reservations}
                                        </span>
                                    </div>
                                ))
                            )}
                        </Card.Body>
                    </Card>
                </Col>

                {/* Total Spending By Driver */}
                <Col md={6}>
                    <Card>
                        <Card.Header>
                            <i className="bi bi-person me-2"></i>
                            Total Spending By Driver
                        </Card.Header>

                        <Card.Body>
                            {spendingByDriver.length === 0 ? (
                                <p className="text-secondary text-center mb-0">
                                    No spending data found.
                                </p>
                            ) : (
                                spendingByDriver.map((driver, i) => (
                                    <div
                                        key={i}
                                        className="revenue-item"
                                    >
                                        <span>
                                            {driver.driver_name}
                                        </span>

                                        <span className="fw-bold">
                                            ${Number(driver.total_spent).toFixed(2)}
                                        </span>
                                    </div>
                                ))
                            )}
                        </Card.Body>
                    </Card>
                </Col>

            </Row>

        </div>
    );
};

export default AdminReports;