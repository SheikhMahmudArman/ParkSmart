import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/admin/Lots.css';

const lots = [
    { id: 1, name: 'Downtown Plaza', location: '123 Main St, NYC', total: 100, available: 42, price: 5, type: 'Standard' },
    { id: 2, name: 'Mall Square', location: '456 Mall Ave, LA', total: 200, available: 87, price: 3, type: 'Standard' },
    { id: 3, name: 'Airport Terminal', location: '789 Airport Rd, SF', total: 150, available: 23, price: 8, type: 'Premium' },
    { id: 4, name: 'City Center', location: '321 Center Blvd, CHI', total: 80, available: 15, price: 6, type: 'Standard' },
    { id: 5, name: 'Harbor View', location: '555 Bay St, SEA', total: 120, available: 61, price: 4, type: 'Standard' },
];

const AdminLots = () => {
    return (
        <div className="fade-in admin-lots">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary"><i className="bi bi-plus me-1"></i>Add Lot</Button>
                    </div>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Name</th><th>Location</th><th>Total</th><th>Available</th><th>Price</th><th>Type</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                {lots.map((l) => (
                                    <tr key={l.id}>
                                        <td>{l.name}</td>
                                        <td>{l.location}</td>
                                        <td>{l.total}</td>
                                        <td>{l.available}</td>
                                        <td>${l.price}/hr</td>
                                        <td>{l.type}</td>
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

export default AdminLots;