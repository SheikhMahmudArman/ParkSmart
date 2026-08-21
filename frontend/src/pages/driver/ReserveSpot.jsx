import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Card, Form, Button, Row, Col } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/driver/ReserveSpot.css';

const ReserveSpot = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const [lot, setLot] = useState(null);
    const [loading, setLoading] = useState(true);
    const [formData, setFormData] = useState({
        date: new Date().toISOString().split('T')[0],
        start_time: '10:00',
        end_time: '12:00',
        vehicle_id: ''
    });
    const [vehicles, setVehicles] = useState([]);

    useEffect(() => {
        // Fetch lot details
        fetch(`http://localhost:8000/api/lots/${id}`, {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                setLot(data);
                setLoading(false);
            })
            .catch(err => {
                console.error('Error fetching lot:', err);
                // Fallback
                setLot({
                    id: 1,
                    name: 'Downtown Plaza',
                    location: '123 Main St, NYC',
                    hourly_rate: 5,
                    total_spots: 100,
                    available_spots: 42,
                    type: 'Standard'
                });
                setLoading(false);
            });

        // Fetch user's vehicles
        fetch(`http://localhost:8000/api/users/${user.id}/vehicles`, {
            headers: {
                'Authorization': `Bearer ${user.token}`,
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                setVehicles(data || []);
            })
            .catch(err => {
                console.error('Error fetching vehicles:', err);
                setVehicles([
                    { id: 1, make: 'Toyota', model: 'Camry', plate_number: 'ABC-123' }
                ]);
            });
    }, [id, user.id, user.token]);

    const handleReserve = async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('http://localhost:8000/api/reservations', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lot_id: lot.id,
                    vehicle_id: formData.vehicle_id,
                    reservation_date: formData.date,
                    start_time: formData.start_time,
                    end_time: formData.end_time
                })
            });
            if (!response.ok) throw new Error('Failed to create reservation');
            const data = await response.json();
            alert(`✅ Reservation confirmed!\n\nLot: ${lot.name}\nDate: ${formData.date}\nTime: ${formData.start_time} - ${formData.end_time}\nSpot: ${data.spot_number || 'Assigned'}`);
            navigate('/driver/reservations');
        } catch (error) {
            console.error('Error creating reservation:', error);
            alert('Failed to create reservation. Please try again.');
        }
    };

    if (loading) return <div className="text-center mt-5">Loading...</div>;

    return (
        <div className="fade-in">
            <Card className="reserve-card">
                <Card.Body>
                    <h5 className="mb-3">Reserve a Spot</h5>
                    <Form onSubmit={handleReserve}>
                        <Form.Group className="mb-3">
                            <Form.Label>Parking Lot</Form.Label>
                            <Form.Control type="text" value={lot.name} disabled />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Date</Form.Label>
                            <Form.Control 
                                type="date" 
                                value={formData.date} 
                                onChange={(e) => setFormData({...formData, date: e.target.value})}
                                required
                            />
                        </Form.Group>
                        <Row>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>Start Time</Form.Label>
                                    <Form.Control 
                                        type="time" 
                                        value={formData.start_time} 
                                        onChange={(e) => setFormData({...formData, start_time: e.target.value})}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>End Time</Form.Label>
                                    <Form.Control 
                                        type="time" 
                                        value={formData.end_time} 
                                        onChange={(e) => setFormData({...formData, end_time: e.target.value})}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Form.Group className="mb-3">
                            <Form.Label>Vehicle</Form.Label>
                            <Form.Select 
                                value={formData.vehicle_id} 
                                onChange={(e) => setFormData({...formData, vehicle_id: e.target.value})}
                                required
                            >
                                <option value="">Select a vehicle...</option>
                                {vehicles.map((v) => (
                                    <option key={v.id} value={v.id}>
                                        {v.make} {v.model} ({v.plate_number})
                                    </option>
                                ))}
                            </Form.Select>
                        </Form.Group>
                        <div className="price-estimate mb-3">
                            <small className="text-secondary">
                                <i className="bi bi-info-circle me-1"></i> Price estimate: <strong>${lot.hourly_rate * 2}.00</strong> (2 hours)
                            </small>
                        </div>
                        <Button variant="success" type="submit">
                            <i className="bi bi-check-circle me-1"></i>Confirm Reservation
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default ReserveSpot;