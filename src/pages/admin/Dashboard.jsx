import { Card, Row, Col } from 'react-bootstrap';
import '../../styles/pages/admin/Dashboard.css';

const AdminDashboard = () => {
    const stats = [
        { label: 'Total Users', value: '24', icon: 'bi-people', color: 'primary' },
        { label: 'Parking Lots', value: '5', icon: 'bi-building', color: 'success' },
        { label: 'Total Spots', value: '650', icon: 'bi-pin-map', color: 'warning' },
        { label: 'Revenue (month)', value: '$4,280', icon: 'bi-currency-dollar', color: 'info' },
    ];

    const recentUsers = [
        { id: 1, name: 'Alex Driver', email: 'alex@email.com', role: 'driver', joined: '2026-01-15' },
        { id: 2, name: 'Maria Garcia', email: 'maria@email.com', role: 'driver', joined: '2026-02-20' },
        { id: 3, name: 'James Lee', email: 'james@email.com', role: 'driver', joined: '2026-03-10' },
        { id: 4, name: 'Sam Staff', email: 'sam@staff.com', role: 'staff', joined: '2026-01-05' },
    ];

    return (
        <div className="fade-in admin-dashboard">
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
                <Card.Header><i className="bi bi-people me-2"></i>Recent Users</Card.Header>
                <Card.Body>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
                            </thead>
                            <tbody>
                                {recentUsers.map((u) => (
                                    <tr key={u.id}>
                                        <td>{u.name}</td>
                                        <td>{u.email}</td>
                                        <td><span className="badge bg-primary">{u.role}</span></td>
                                        <td>{u.joined}</td>
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

export default AdminDashboard;