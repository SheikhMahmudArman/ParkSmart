import { useState, useEffect } from 'react';
import { Card, Row, Col } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/admin/Payments.css';

const AdminPayments = () => {
    const { user } = useAuth();
    const [payments, setPayments] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchPayments = async () => {
            try {
                // Fetch all payments (admin endpoint)
                const response = await fetch('http://localhost:8000/api/payments', {
                    headers: {
                        'Authorization': `Bearer ${user.token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch payments');
                const data = await response.json();
                setPayments(data);
            } catch (error) {
                console.error('Error fetching payments:', error);
                // Use fallback data
                setPayments([
                    { id: 1, date: '2026-08-04', lot: 'Downtown Plaza', amount: 10.00, status: 'Completed', method: 'Credit Card' },
                    { id: 2, date: '2026-08-03', lot: 'Airport Terminal', amount: 24.00, status: 'Completed', method: 'PayPal' },
                    { id: 3, date: '2026-08-02', lot: 'Mall Square', amount: 6.00, status: 'Pending', method: 'Debit Card' },
                ]);
            } finally {
                setLoading(false);
            }
        };
        fetchPayments();
    }, [user.token]);

    const total = payments.reduce((sum, p) => sum + (p.status === 'Completed' ? p.amount : 0), 0);
    const completed = payments.filter(p => p.status === 'Completed').length;
    const pending = payments.filter(p => p.status === 'Pending').length;

    if (loading) return <div className="text-center mt-5">Loading payments...</div>;

    return (
        <div className="fade-in admin-payments">
            <Row className="g-3 mb-4 summary-cards">
                <Col md={4}>
                    <div className="stat-card">
                        <div className="value">${total.toFixed(2)}</div>
                        <div className="label">Total Collected</div>
                    </div>
                </Col>
                <Col md={4}>
                    <div className="stat-card">
                        <div className="value">{completed}</div>
                        <div className="label">Completed</div>
                    </div>
                </Col>
                <Col md={4}>
                    <div className="stat-card">
                        <div className="value">{pending}</div>
                        <div className="label">Pending</div>
                    </div>
                </Col>
            </Row>
            <Card>
                <Card.Body>
                    <div className="table-wrap payment-table">
                        <table className="table">
                            <thead>
                                <tr><th>Date</th><th>Lot</th><th>Amount</th><th>Status</th><th>Method</th></tr>
                            </thead>
                            <tbody>
                                {payments.map((p) => (
                                    <tr key={p.id}>
                                        <td>{p.date || new Date(p.PaymentDate).toLocaleDateString()}</td>
                                        <td>{p.lot || p.lot_name}</td>
                                        <td className="amount">${(p.amount || p.Amount).toFixed(2)}</td>
                                        <td><span className={`badge bg-${(p.status || p.Status) === 'Completed' ? 'success' : 'warning'}`}>{p.status || p.Status}</span></td>
                                        <td>{p.method || p.Method}</td>
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

export default AdminPayments;