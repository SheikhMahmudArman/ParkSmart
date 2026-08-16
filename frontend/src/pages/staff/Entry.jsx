import { useState } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import '../../styles/pages/staff/Entry.css';

const Entry = () => {
    const [form, setForm] = useState({ vehicle: '', lot: '', spot: '' });

    const handleSubmit = (e) => {
        e.preventDefault();
        alert(`✅ Entry logged: ${form.vehicle} at ${form.lot} - ${form.spot}`);
        setForm({ vehicle: '', lot: '', spot: '' });
    };

    return (
        <div className="fade-in">
            <Card className="entry-card">
                <Card.Body>
                    <h5 className="mb-3"><i className="bi bi-box-arrow-in-right me-2"></i>Record Entry</h5>
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
                            <Form.Label>Spot Number</Form.Label>
                            <Form.Control type="text" placeholder="e.g. A12" value={form.spot} onChange={(e) => setForm({ ...form, spot: e.target.value })} required />
                        </Form.Group>
                        <Button variant="success" type="submit">
                            <i className="bi bi-check-circle me-1"></i>Log Entry
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Entry;