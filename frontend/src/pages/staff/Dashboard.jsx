import { useState, useEffect } from 'react';
import { Card, Row, Col } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';

const StaffDashboard = () => {
    const { user } = useAuth();
    const [sessions, setSessions] = useState([]);
    const [stats, setStats] = useState({
        totalEntries: 0,
        totalExits: 0,
        activeSessions: 0,
        occupancyRate: 0
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetch('http://localhost:8000/api/sessions/active', {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                // Ensure data is an array
                const sessionsArray = Array.isArray(data) ? data : [];
                setSessions(sessionsArray);

                // Calculate stats
                const total = sessionsArray.length;
                const active = sessionsArray.filter(s => s.ExitTime === null).length;
                const exited = sessionsArray.filter(s => s.ExitTime !== null).length;

                setStats({
                    totalEntries: total,
                    totalExits: exited,
                    activeSessions: active,
                    occupancyRate: total > 0 ? Math.round((active / total) * 100) : 0
                });
                setLoading(false);
            })
            .catch(err => {
                console.error('Error fetching sessions:', err);
                setError(err.message);
                setLoading(false);
            });
    }, [user.token]);

    if (loading) return <div className="text-center mt-5">Loading dashboard...</div>;
    if (error) return <div className="text-center mt-5 text-danger">Error: {error}</div>;

    const statCards = [
        { label: "Total Sessions", value: stats.totalEntries, icon: 'bi-box-arrow-in-right', color: 'primary' },
        { label: "Exited", value: stats.totalExits, icon: 'bi-box-arrow-right', color: 'success' },
        { label: 'Active', value: stats.activeSessions, icon: 'bi-calendar-check', color: 'warning' },
        { label: 'Occupancy Rate', value: stats.occupancyRate + '%', icon: 'bi-pie-chart', color: 'info' },
    ];

    return (
        <div className="fade-in staff-dashboard">
            <Row className="g-4 mb-4">
                {statCards.map((s, idx) => (
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
                <Card.Header><i className="bi bi-clock-history me-2"></i>Active Sessions</Card.Header>
                <Card.Body>
                    {sessions.length === 0 ? (
                        <p className="text-secondary">No active sessions.</p>
                    ) : (
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr><th>Vehicle</th><th>Lot</th><th>Spot</th><th>Entry Time</th><th>Duration</th></tr>
                                </thead>
                                <tbody>
                                    {sessions.map((s) => (
                                        <tr key={s.SessionID}>
                                            <td>{s.vehicle?.PlateNumber || 'N/A'}</td>
                                            <td>{s.parkingSpace?.parkingLot?.Name || 'N/A'}</td>
                                            <td>{s.parkingSpace?.SpaceNumber || 'N/A'}</td>
                                            <td>{new Date(s.EntryTime).toLocaleTimeString()}</td>
                                            <td>{Math.floor((Date.now() - new Date(s.EntryTime)) / 60000)} min</td>
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

export default StaffDashboard;