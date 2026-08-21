import { useState, useEffect } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/staff/Entry.css';

const Entry = () => {
    const { user } = useAuth();
    const [form, setForm] = useState({ vehicle: '', lot_id: '', spot: '' });
    const [lots, setLots] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetch('http://localhost:8000/api/lots', {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => setLots(data))
            .catch(err => console.error('Error fetching lots:', err));
    }, [user.token]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            const response = await fetch('http://localhost:8000/api/sessions/entry', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    plate_number: form.vehicle,
                    lot_id: form.lot_id,
                    space_number: form.spot
                })
            });
            if (!response.ok) throw new Error('Failed to log entry');
            alert(`✅ Entry logged: ${form.vehicle}`);
            setForm({ vehicle: '', lot_id: '', spot: '' });
        } catch (error) {
            console.error('Error logging entry:', error);
            alert('Failed to log entry. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fade-in">
            <Card className="entry-card">
                <Card.Body>
                    <h5 className="mb-3"><i className="bi bi-box-arrow-in-right me-2"></i>Record Entry</h5>
                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3">
                            <Form.Label>Vehicle (plate)</Form.Label>
                            <Form.Control 
                                type="text" 
                                placeholder="ABC-123" 
                                value={form.vehicle} 
                                onChange={(e) => setForm({ ...form, vehicle: e.target.value })} 
                                required 
                            />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Parking Lot</Form.Label>
                            <Form.Select 
                                value={form.lot_id} 
                                onChange={(e) => setForm({ ...form, lot_id: e.target.value })} 
                                required
                            >
                                <option value="">Select lot...</option>
                                {lots.map((lot) => (
                                    <option key={lot.id} value={lot.id}>{lot.name}</option>
                                ))}
                            </Form.Select>
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Spot Number</Form.Label>
                            <Form.Control 
                                type="text" 
                                placeholder="e.g. A12" 
                                value={form.spot} 
                                onChange={(e) => setForm({ ...form, spot: e.target.value })} 
                                required 
                            />
                        </Form.Group>
                        <Button variant="success" type="submit" disabled={loading}>
                            <i className="bi bi-check-circle me-1"></i>{loading ? 'Logging...' : 'Log Entry'}
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Entry;