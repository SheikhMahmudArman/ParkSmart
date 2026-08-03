import { useParams } from 'react-router-dom';
import { Card, Form, Button, Row, Col } from 'react-bootstrap';
import { useState } from 'react';
import '../../styles/pages/driver/ReserveSpot.css';

const PARKING_LOTS = [
    { id: 1, name: 'Downtown Plaza', location: '123 Main St, NYC', price: 5, total: 100, available: 42, type: 'Standard', features: ['EV Charging', 'Covered'] },
    { id: 2, name: 'Mall Square', location: '456 Mall Ave, LA', price: 3, total: 200, available: 87, type: 'Standard', features: ['Open 24/7', 'Security'] },
    { id: 3, name: 'Airport Terminal', location: '789 Airport Rd, SF', price: 8, total: 150, available: 23, type: 'Premium', features: ['EV Charging', 'Valet', 'Covered'] },
    { id: 4, name: 'City Center', location: '321 Center Blvd, CHI', price: 6, total: 80, available: 15, type: 'Standard', features: ['Disabled Access'] },
    { id: 5, name: 'Harbor View', location: '555 Bay St, SEA', price: 4, total: 120, available: 61, type: 'Standard', features: ['Covered', 'Security'] },
];

const ReserveSpot = () => {
    const { id } = useParams();
    const lot = PARKING_LOTS.find(l => l.id === parseInt(id)) || PARKING_LOTS[0];
    const [date, setDate] = useState('2026-08-10');
    const [start, setStart] = useState('10:00');
    const [end, setEnd] = useState('12:00');

    const handleReserve = () => {
        alert(`✅ Reservation confirmed!\n\nLot: ${lot.name}\nDate: ${date}\nTime: ${start} - ${end}\nSpot: A12 (assigned)`);
        window.location.hash = '#/driver/reservations';
    };

    return (
        <div className="fade-in">
            <Card className="reserve-card">
                <Card.Body>
                    <h5 className="mb-3">Reserve a Spot</h5>
                    <Form>
                        <Form.Group className="mb-3">
                            <Form.Label>Parking Lot</Form.Label>
                            <Form.Control type="text" value={lot.name} disabled />
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Date</Form.Label>
                            <Form.Control type="date" value={date} onChange={(e) => setDate(e.target.value)} />
                        </Form.Group>
                        <Row>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>Start Time</Form.Label>
                                    <Form.Control type="time" value={start} onChange={(e) => setStart(e.target.value)} />
                                </Form.Group>
                            </Col>
                            <Col>
                                <Form.Group className="mb-3">
                                    <Form.Label>End Time</Form.Label>
                                    <Form.Control type="time" value={end} onChange={(e) => setEnd(e.target.value)} />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Form.Group className="mb-3">
                            <Form.Label>Vehicle</Form.Label>
                            <Form.Select>
                                <option>Toyota Camry (ABC-123)</option>
                                <option>Honda Civic (XYZ-789)</option>
                                <option>Tesla Model 3 (EV-001)</option>
                            </Form.Select>
                        </Form.Group>
                        <div className="price-estimate mb-3">
                            <small className="text-secondary">
                                <i className="bi bi-info-circle me-1"></i> Price estimate: <strong>${lot.price * 2}.00</strong> (2 hours)
                            </small>
                        </div>
                        <Button variant="success" onClick={handleReserve}>
                            <i className="bi bi-check-circle me-1"></i>Confirm Reservation
                        </Button>
                    </Form>
                </Card.Body>
            </Card>
        </div>
    );
};

export default ReserveSpot;