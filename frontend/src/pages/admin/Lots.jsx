import { useState, useEffect } from 'react';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/admin/Lots.css';

const AdminLots = () => {
    const { user } = useAuth();
    const [lots, setLots] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchLots = async () => {
        try {
            const response = await fetch('http://localhost:8000/api/lots', {
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('Failed to fetch lots');
            const data = await response.json();
            setLots(data);
        } catch (error) {
            console.error('Error fetching lots:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLots();
    }, [user.token]);

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this lot?')) return;
        try {
            const response = await fetch(`http://localhost:8000/api/lots/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('Failed to delete lot');
            setLots(lots.filter(l => l.id !== id));
            alert('Lot deleted successfully!');
        } catch (error) {
            console.error('Error deleting lot:', error);
            alert('Failed to delete lot');
        }
    };

    const handleEdit = (id) => {
        // Navigate to edit form or open modal
        alert(`Edit lot ${id} - Implement edit modal/form`);
    };

    if (loading) return <div className="text-center mt-5">Loading lots...</div>;

    return (
        <div className="fade-in admin-lots">
            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="primary" onClick={() => alert('Open Add Lot Modal/Form')}>
                            <i className="bi bi-plus me-1"></i>Add Lot
                        </Button>
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
                                        <td>{l.total_spots}</td>
                                        <td>{l.available_spots}</td>
                                        <td>${l.hourly_rate}/hr</td>
                                        <td>{l.type}</td>
                                        <td className="action-buttons">
                                            <Button size="sm" variant="outline-secondary" onClick={() => handleEdit(l.id)}>Edit</Button>
                                            <Button size="sm" variant="danger" onClick={() => handleDelete(l.id)}>Delete</Button>
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