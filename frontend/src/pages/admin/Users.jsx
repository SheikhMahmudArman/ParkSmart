import { useState, useEffect } from 'react';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/admin/Users.css';

const AdminUsers = () => {
    const { user } = useAuth();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchUsers = async () => {
            try {
                const response = await fetch('http://localhost:8000/api/users', {
                    headers: {
                        'Authorization': `Bearer ${user.token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch users');
                const data = await response.json();
                setUsers(data);
            } catch (error) {
                console.error('Error fetching users:', error);
                // Fallback data
                setUsers([
                    { id: 1, name: 'Alex Driver', email: 'alex@email.com', role: 'driver', joined: '2026-01-15' },
                    { id: 2, name: 'Maria Garcia', email: 'maria@email.com', role: 'driver', joined: '2026-02-20' },
                    { id: 3, name: 'Admin User', email: 'admin@admin.com', role: 'admin', joined: '2025-12-01' },
                ]);
            } finally {
                setLoading(false);
            }
        };
        fetchUsers();
    }, [user.token]);

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this user?')) return;
        try {
            const response = await fetch(`http://localhost:8000/api/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('Failed to delete user');
            setUsers(users.filter(u => u.id !== id));
            alert('User deleted successfully!');
        } catch (error) {
            console.error('Error deleting user:', error);
            alert('Failed to delete user');
        }
    };

    const handleEdit = (id) => {
        alert(`Edit user ${id} - Implement edit modal/form`);
    };

    if (loading) return <div className="text-center mt-5">Loading users...</div>;

    return (
        <div className="fade-in admin-users">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary" onClick={() => alert('Open Add User Modal/Form')}>
                            <i className="bi bi-plus me-1"></i>Add User
                        </Button>
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
                                        <td>{u.joined || new Date(u.created_at).toLocaleDateString()}</td>
                                        <td className="action-buttons">
                                            <Button size="sm" variant="outline-secondary" onClick={() => handleEdit(u.id)}>Edit</Button>
                                            <Button size="sm" variant="danger" onClick={() => handleDelete(u.id)}>Delete</Button>
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