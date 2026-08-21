import { useState, useEffect } from 'react';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/admin/Staff.css';

const AdminStaff = () => {
    const { user } = useAuth();
    const [staffList, setStaffList] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchStaff = async () => {
            try {
                const response = await fetch('http://localhost:8000/api/staff', {
                    headers: {
                        'Authorization': `Bearer ${user.token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch staff');
                const data = await response.json();
                setStaffList(data);
            } catch (error) {
                console.error('Error fetching staff:', error);
                // Fallback data
                setStaffList([
                    { id: 1, name: 'John Doe', email: 'john@parking.com', role: 'Manager', lot: 'Downtown Plaza' },
                    { id: 2, name: 'Jane Smith', email: 'jane@parking.com', role: 'Attendant', lot: 'Mall Square' },
                ]);
            } finally {
                setLoading(false);
            }
        };
        fetchStaff();
    }, [user.token]);

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this staff member?')) return;
        try {
            const response = await fetch(`http://localhost:8000/api/staff/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('Failed to delete staff');
            setStaffList(staffList.filter(s => s.id !== id));
            alert('Staff member deleted successfully!');
        } catch (error) {
            console.error('Error deleting staff:', error);
            alert('Failed to delete staff member');
        }
    };

    const handleEdit = (id) => {
        alert(`Edit staff ${id} - Implement edit modal/form`);
    };

    if (loading) return <div className="text-center mt-5">Loading staff...</div>;

    return (
        <div className="fade-in admin-staff">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary" onClick={() => alert('Open Add Staff Modal/Form')}>
                            <i className="bi bi-plus me-1"></i>Add Staff
                        </Button>
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
                                        <td>{s.lot || s.assigned_lot}</td>
                                        <td className="action-buttons">
                                            <Button size="sm" variant="outline-secondary" onClick={() => handleEdit(s.id)}>Edit</Button>
                                            <Button size="sm" variant="danger" onClick={() => handleDelete(s.id)}>Delete</Button>
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