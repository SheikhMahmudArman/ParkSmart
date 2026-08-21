import { useState } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/driver/Profile.css';

const Profile = () => {
    const { user } = useAuth();
    const [formData, setFormData] = useState({
        name: user?.name || '',
        email: user?.email || '',
        phone: '',
        vehicle: ''
    });
    const [saving, setSaving] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            const response = await fetch(`http://localhost:8000/api/users/${user.id}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            if (!response.ok) throw new Error('Failed to update profile');
            alert('Profile updated successfully!');
        } catch (error) {
            console.error('Error updating profile:', error);
            alert('Failed to update profile');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="fade-in">
            <Card style={{ maxWidth: '600px' }}>
                <Card.Body>
                    <div className="d-flex align-items-center gap-3 mb-4">
                        <div className="profile-avatar">
                            {user?.name?.charAt(0) || 'U'}
                        </div>
                        <div>
                            <h5>{user?.name}</h5>
                            <p className="text-secondary mb-0">{user?.email}</p>
                            <span className="badge bg-primary">Driver</span>
                        </div>
                    </div>
                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3">
                            <Form.Label>Full Name</Form.Label>
                            <Form.Control 
                                type="text" 
                                value={formData.name} 
                                onChange={(e) => setFormData({...formData, name: e.target.value})}
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Email</Form.Label>
                            <Form.Control 
                                type="email" 
                                value={formData.email} 
                                onChange={(e) => setFormData({...formData, email: e.target.value})}
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Phone</Form.Label>
                            <Form.Control 
                                type="text" 
                                placeholder="+1 (555) 000-0000"
                                value={formData.phone}
                                onChange={(e) => setFormData({...formData, phone: e.target.value})}
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Vehicle (primary)</Form.Label>
                            <Form.Control 
                                type="text" 
                                placeholder="Toyota Camry, ABC-123"
                                value={formData.vehicle}
                                onChange={(e) => setFormData({...formData, vehicle: e.target.value})}
                            />
                        </Form.Group>
                        <Button variant="primary" type="submit" disabled={saving}>
                            <i className="bi bi-save me-1"></i>{saving ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Profile;