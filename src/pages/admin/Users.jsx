import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/admin/Users.css';

const users = [
    { id: 1, name: 'Alex Driver', email: 'alex@email.com', role: 'driver', joined: '2026-01-15' },
    { id: 2, name: 'Maria Garcia', email: 'maria@email.com', role: 'driver', joined: '2026-02-20' },
    { id: 3, name: 'James Lee', email: 'james@email.com', role: 'driver', joined: '2026-03-10' },
    { id: 4, name: 'Sarah Kim', email: 'sarah@email.com', role: 'driver', joined: '2026-04-05' },
    { id: 5, name: 'Sam Staff', email: 'sam@staff.com', role: 'staff', joined: '2026-01-05' },
    { id: 6, name: 'Admin User', email: 'admin@admin.com', role: 'admin', joined: '2025-12-01' },
];

const AdminUsers = () => {
    return (
        <div className="fade-in admin-users">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary"><i className="bi bi-plus me-1"></i>Add User</Button>
                    </div>
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                {users.map((u) => (
                                    <tr key={u.id}>
                                        <td>{u.name}</td>
                                        <td>{u.email}</td>
                                        <td><span className="badge bg-primary">{u.role}</span></td>
                                        <td>{u.joined}</td>
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

export default AdminUsers;