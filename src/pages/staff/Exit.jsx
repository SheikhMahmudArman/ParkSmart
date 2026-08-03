import { useState } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import '../../styles/pages/staff/Exit.css';

const Exit = () => {
    const [form, setForm] = useState({ vehicle: '', lot: '', exitTime: '' });

    const handleSubmit = (e) => {
        e.preventDefault();
        alert(`✅ Exit logged: ${form.vehicle} from ${form.lot} at ${form.exitTime || 'now'}`);
        setForm({ vehicle: '', lot: '', exitTime: '' });
    };

    return (
        <div className="fade-in">
            <Card className="exit-card">
                <Card.Body>
                    <h5 className="mb-3"><i className="bi bi-box-arrow-right me-2"></i>Record Exit</h5>
                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3">
                            <Form.Label>Vehicle (plate)</Form.Label>
                            <Form.Control type="text" placeholder="ABC-123" value={form.vehicle} onChange={(e) => setForm({ ...form, vehicle: e.target.value })} required />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Parking Lot</Form.Label>
                            <Form.Select value={form.lot} onChange={(e) => setForm({ ...form, lot: e.target.value })} required>
                                <option value="">Select lot...</option>
                                <option>Downtown Plaza</option>
                                <option>Mall Square</option>
                                <option>Airport Terminal</option>
                            </Form.Select>
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Exit Time</Form.Label>
                            <Form.Control type="time" value={form.exitTime} onChange={(e) => setForm({ ...form, exitTime: e.target.value })} />
                        </Form.Group>
                        <Button variant="primary" type="submit">
                            <i className="bi bi-check-circle me-1"></i>Log Exit
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Exit;