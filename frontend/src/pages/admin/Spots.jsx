import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/admin/Spots.css';

const spots = [
    { id: 1, lot: 'Downtown Plaza', spot: 'A01-A20', status: 'Available', type: 'Standard' },
    { id: 2, lot: 'Downtown Plaza', spot: 'A21-A30', status: 'Occupied', type: 'EV' },
    { id: 3, lot: 'Mall Square', spot: 'B01-B15', status: 'Available', type: 'Standard' },
    { id: 4, lot: 'Mall Square', spot: 'B16-B25', status: 'Occupied', type: 'Disabled' },
    { id: 5, lot: 'Airport Terminal', spot: 'C01-C10', status: 'Available', type: 'Premium' },
    { id: 6, lot: 'Airport Terminal', spot: 'C11-C20', status: 'Occupied', type: 'Premium' },
];

const AdminSpots = () => {
    return (
        <div className="fade-in admin-spots">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary"><i className="bi bi-plus me-1"></i>Add Spot</Button>
                    </div>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Lot</th><th>Spot</th><th>Status</th><th>Type</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                {spots.map((s) => (
                                    <tr key={s.id}>
                                        <td>{s.lot}</td>
                                        <td>{s.spot}</td>
                                        <td><span className={`badge bg-${s.status === 'Available' ? 'success' : 'danger'}`}>{s.status}</span></td>
                                        <td>{s.type}</td>
                                        <td className="action-buttons">
                                            <Button size="sm" variant="outline-secondary">Edit</Button>
                                            <Button size="sm" variant="danger">Delete</Button>
                                        </td>
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

export default AdminSpots;