import { Card, Row, Col } from 'react-bootstrap';
import '../../styles/pages/admin/Payments.css';

const payments = [
    { id: 1, date: '2026-08-04', lot: 'Downtown Plaza', amount: 10.00, status: 'Completed', method: 'Credit Card' },
    { id: 2, date: '2026-08-03', lot: 'Airport Terminal', amount: 24.00, status: 'Completed', method: 'PayPal' },
    { id: 3, date: '2026-08-02', lot: 'Mall Square', amount: 6.00, status: 'Pending', method: 'Debit Card' },
    { id: 4, date: '2026-08-01', lot: 'City Center', amount: 12.00, status: 'Completed', method: 'Credit Card' },
    { id: 5, date: '2026-07-31', lot: 'Harbor View', amount: 8.00, status: 'Completed', method: 'Cash' },
];

const AdminPayments = () => {
    const total = payments.reduce((sum, p) => sum + (p.status === 'Completed' ? p.amount : 0), 0);
    const completed = payments.filter(p => p.status === 'Completed').length;
    const pending = payments.filter(p => p.status === 'Pending').length;

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
                                        <td>{p.date}</td>
                                        <td>{p.lot}</td>
                                        <td className="amount">${p.amount.toFixed(2)}</td>
                                        <td><span className={`badge bg-${p.status === 'Completed' ? 'success' : 'warning'}`}>{p.status}</span></td>
                                        <td>{p.method}</td>
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