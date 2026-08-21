import { useState, useEffect } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/staff/Exit.css';

const Exit = () => {
    const { user } = useAuth();
    const [form, setForm] = useState({ session_id: '', exit_time: '' });
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetch('http://localhost:8000/api/sessions/active', {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                const activeSessions = Array.isArray(data) ? data : [];
                setSessions(activeSessions);
            })
            .catch(err => console.error('Error fetching sessions:', err));
    }, [user.token]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            const response = await fetch(`http://localhost:8000/api/sessions/${form.session_id}/exit`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    exit_time: form.exit_time || new Date().toTimeString().slice(0, 5)
                })
            });
            if (!response.ok) throw new Error('Failed to log exit');
            alert(`✅ Exit logged successfully!`);
            setForm({ session_id: '', exit_time: '' });
            // Refresh active sessions
            const res = await fetch('http://localhost:8000/api/sessions/active', {
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            setSessions(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error logging exit:', error);
            alert('Failed to log exit. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fade-in">
            <Card className="exit-card">
                <Card.Body>
                    <h5 className="mb-3"><i className="bi bi-box-arrow-right me-2"></i>Record Exit</h5>
                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3">
                            <Form.Label>Select Active Session</Form.Label>
                            <Form.Select 
                                value={form.session_id} 
                                onChange={(e) => setForm({ ...form, session_id: e.target.value })} 
                                required
                            >
                                <option value="">Select session...</option>
                                {sessions.map((s) => (
                                    <option key={s.id || s.SessionID} value={s.id || s.SessionID}>
                                        {s.vehicle?.PlateNumber || s.plate_number || 'N/A'} - {s.parkingSpace?.parkingLot?.Name || s.lot_name || 'N/A'}
                                    </option>
                                ))}
                            </Form.Select>
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Exit Time</Form.Label>
                            <Form.Control 
                                type="time" 
                                value={form.exit_time} 
                                onChange={(e) => setForm({ ...form, exit_time: e.target.value })} 
                            />
                            <small className="text-secondary">Leave empty for current time</small>
                        </Form.Group>
                        <Button variant="primary" type="submit" disabled={loading || sessions.length === 0}>
                            <i className="bi bi-check-circle me-1"></i>{loading ? 'Logging...' : 'Log Exit'}
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default Exit;