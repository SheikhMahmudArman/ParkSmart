import { Card, Form, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/driver/Profile.css';

const Profile = () => {
    const { user } = useAuth();
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
                    <Form>
                        <Form.Group className="mb-3">
                            <Form.Label>Full Name</Form.Label>
                            <Form.Control type="text" value={user?.name || ''} disabled />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Email</Form.Label>
                            <Form.Control type="email" value={user?.email || ''} disabled />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Phone</Form.Label>
                            <Form.Control type="text" placeholder="+1 (555) 000-0000" />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Vehicle (primary)</Form.Label>
                            <Form.Control type="text" placeholder="Toyota Camry, ABC-123" />
                        </Form.Group>
                        <Button variant="primary">
                            <i className="bi bi-save me-1"></i>Save Changes
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Profile;