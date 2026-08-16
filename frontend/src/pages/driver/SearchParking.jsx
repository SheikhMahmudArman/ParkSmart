import { useState, useEffect } from 'react';
import { Card, Form, Row, Col, Button } from 'react-bootstrap';
import '../../styles/pages/driver/SearchParking.css';

const PARKING_LOTS = [
    { id: 1, name: 'Downtown Plaza', location: '123 Main St, NYC', price: 5, total: 100, available: 42, type: 'Standard', features: ['EV Charging', 'Covered'] },
    { id: 2, name: 'Mall Square', location: '456 Mall Ave, LA', price: 3, total: 200, available: 87, type: 'Standard', features: ['Open 24/7', 'Security'] },
    { id: 3, name: 'Airport Terminal', location: '789 Airport Rd, SF', price: 8, total: 150, available: 23, type: 'Premium', features: ['EV Charging', 'Valet', 'Covered'] },
    { id: 4, name: 'City Center', location: '321 Center Blvd, CHI', price: 6, total: 80, available: 15, type: 'Standard', features: ['Disabled Access'] },
    { id: 5, name: 'Harbor View', location: '555 Bay St, SEA', price: 4, total: 120, available: 61, type: 'Standard', features: ['Covered', 'Security'] },
];

const SearchParking = () => {
    const [search, setSearch] = useState('');
    const [type, setType] = useState('all');
    const [lots, setLots] = useState(PARKING_LOTS);

    useEffect(() => {
        let filtered = PARKING_LOTS;
        if (search) {
            filtered = filtered.filter(l =>
                l.name.toLowerCase().includes(search.toLowerCase()) ||
                l.location.toLowerCase().includes(search.toLowerCase())
            );
        }
        if (type !== 'all') {
            filtered = filtered.filter(l => l.type === type);
        }
        setLots(filtered);
    }, [search, type]);

    return (
        <div className="fade-in">
            <Card className="mb-4">
                <Card.Body>
                    <Row className="g-3 search-filters">
                        <Col md={5}>
                            <Form.Control
                                type="text"
                                placeholder="Search by name or location..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="bg-dark border-secondary"
                            />
                        </Col>
                        <Col md={4}>
                            <Form.Select value={type} onChange={(e) => setType(e.target.value)}>
                                <option value="all">All Types</option>
                                <option value="Standard">Standard</option>
                                <option value="Premium">Premium</option>
                            </Form.Select>
                        </Col>
                        <Col md={3}>
                            <Button variant="secondary" className="btn-reset" onClick={() => { setSearch(''); setType('all'); }}>
                                <i className="bi bi-arrow-counterclockwise me-1"></i>Reset
                            </Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>
            <div className="parking-list">
                {lots.map((lot) => (
                    <Card key={lot.id} className="mb-3 parking-card">
                        <Card.Body className="d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <h6 className="mb-1">{lot.name}</h6>
                                <small className="text-secondary"><i className="bi bi-geo-alt me-1"></i>{lot.location}</small>
                                <div className="mt-2 d-flex flex-wrap gap-1">
                                    <span className="badge bg-primary">{lot.type}</span>
                                    <span className="badge bg-success">{lot.available} / {lot.total} available</span>
                                    <span className="badge bg-secondary">${lot.price}/hr</span>
                                    {lot.features.map((f, i) => <span key={i} className="badge bg-info text-dark">{f}</span>)}
                                </div>
                            </div>
                            <div className="d-flex gap-2 mt-2 mt-md-0">
                                <Button variant="outline-primary" size="sm" href={`#/driver/details/${lot.id}`}>
                                    <i className="bi bi-eye me-1"></i>Details
                                </Button>
                                <Button variant="primary" size="sm" href={`#/driver/reserve/${lot.id}`}>
                                    <i className="bi bi-parking me-1"></i>Reserve
                                </Button>
                            </div>
                        </Card.Body>
                    </Card>
                ))}
                {lots.length === 0 && <p className="text-secondary text-center">No parking lots found.</p>}
            </div>
        </div>
    );
};

export default SearchParking;