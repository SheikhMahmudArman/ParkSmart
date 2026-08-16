import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/admin/Staff.css';

const staffList = [
    { id: 1, name: 'John Doe', email: 'john@parking.com', role: 'Manager', lot: 'Downtown Plaza' },
    { id: 2, name: 'Jane Smith', email: 'jane@parking.com', role: 'Attendant', lot: 'Mall Square' },
    { id: 3, name: 'Bob Wilson', email: 'bob@parking.com', role: 'Attendant', lot: 'Airport Terminal' },
];

const AdminStaff = () => {
    return (
        <div className="fade-in admin-staff">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary"><i className="bi bi-plus me-1"></i>Add Staff</Button>
                    </div>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Name</th><th>Email</th><th>Role</th><th>Assigned Lot</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                {staffList.map((s) => (
                                    <tr key={s.id}>
                                        <td>{s.name}</td>
                                        <td>{s.email}</td>
                                        <td><span className="badge bg-info text-dark">{s.role}</span></td>
                                        <td>{s.lot}</td>
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

export default AdminStaff;