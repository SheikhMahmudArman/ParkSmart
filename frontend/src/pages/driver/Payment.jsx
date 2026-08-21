import { useState, useEffect } from 'react';
import { Card } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';

const Payment = () => {
    const { user } = useAuth();
    const [payments, setPayments] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch(`http://localhost:8000/api/users/${user.id}/payments`, {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                setPayments(data);
                setLoading(false);
            })
            .catch(err => {
                console.error('Error fetching payments:', err);
                setLoading(false);
            });
    }, [user.id, user.token]);

    if (loading) return <div className="text-center mt-5">Loading payment history...</div>;

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Date</th><th>Lot</th><th>Amount</th><th>Status</th><th>Method</th></tr>
                            </thead>
                            <tbody>
                                {payments.length === 0 ? (
                                    <tr><td colSpan="5" className="text-center text-secondary">No payments found</td></tr>
                                ) : (
                                    payments.map((p) => (
                                        <tr key={p.id || p.PaymentID}>
                                            <td>{new Date(p.date || p.PaymentDate).toLocaleDateString()}</td>
                                            <td>{p.lot || p.lot_name || 'N/A'}</td>
                                            <td>${(p.amount || p.Amount).toFixed(2)}</td>
                                            <td><span className={`badge bg-${(p.status || p.Status) === 'Completed' ? 'success' : 'warning'}`}>{p.status || p.Status}</span></td>
                                            <td>{p.method || p.Method}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Payment;