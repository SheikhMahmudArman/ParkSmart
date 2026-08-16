import { useParams } from 'react-router-dom';
import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/driver/ParkingDetails.css';

const PARKING_LOTS = [
    { id: 1, name: 'Downtown Plaza', location: '123 Main St, NYC', price: 5, total: 100, available: 42, type: 'Standard', features: ['EV Charging', 'Covered'] },
    { id: 2, name: 'Mall Square', location: '456 Mall Ave, LA', price: 3, total: 200, available: 87, type: 'Standard', features: ['Open 24/7', 'Security'] },
    { id: 3, name: 'Airport Terminal', location: '789 Airport Rd, SF', price: 8, total: 150, available: 23, type: 'Premium', features: ['EV Charging', 'Valet', 'Covered'] },
    { id: 4, name: 'City Center', location: '321 Center Blvd, CHI', price: 6, total: 80, available: 15, type: 'Standard', features: ['Disabled Access'] },
    { id: 5, name: 'Harbor View', location: '555 Bay St, SEA', price: 4, total: 120, available: 61, type: 'Standard', features: ['Covered', 'Security'] },
];

const ParkingDetails = () => {
    const { id } = useParams();
    const lot = PARKING_LOTS.find(l => l.id === parseInt(id)) || PARKING_LOTS[0];

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    <h4>{lot.name}</h4>
                    <p className="text-secondary"><i className="bi bi-geo-alt me-2"></i>{lot.location}</p>
                    <div className="d-flex flex-wrap gap-3 my-3">
                        <span className="badge bg-primary p-2">${lot.price}/hr</span>
                        <span className="badge bg-success p-2">{lot.available} spots available</span>
                        <span className="badge bg-secondary p-2">{lot.type}</span>
                    </div>
                    <p className="details-features">
                        <strong>Features:</strong> {lot.features.map((f, i) => <span key={i} className="badge bg-info text-dark me-1">{f}</span>)}
                    </p>
                    <Button variant="primary" href={`#/driver/reserve/${lot.id}`}>
                        <i className="bi bi-parking me-1"></i>Reserve a Spot
                    </Button>
                </Card.Body>
            </Card>
        </div>
    );
};

export default ParkingDetails;