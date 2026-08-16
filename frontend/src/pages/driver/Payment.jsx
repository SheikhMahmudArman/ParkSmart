import { Card } from 'react-bootstrap';
import '../../styles/pages/driver/Payment.css';

const payments = [
    { id: 1, date: '2026-08-04', lot: 'Downtown Plaza', amount: 10.00, status: 'Completed', method: 'Credit Card' },
    { id: 2, date: '2026-08-03', lot: 'Airport Terminal', amount: 24.00, status: 'Completed', method: 'PayPal' },
    { id: 3, date: '2026-08-02', lot: 'Mall Square', amount: 6.00, status: 'Completed', method: 'Debit Card' },
];

const Payment = () => {
    return (
        <div className="fade-in">
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

export default Payment;