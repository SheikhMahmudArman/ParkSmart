import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Card, Button } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/driver/ParkingDetails.css';

const ParkingDetails = () => {
    const { id } = useParams();
    const { user } = useAuth();
    const [lot, setLot] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
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
                console.error('Error fetching lot details:', err);
                // Fallback data
                setLot({
                    id: 1,
                    name: 'Downtown Plaza',
                    location: '123 Main St, NYC',
                    hourly_rate: 5,
                    total_spots: 100,
                    available_spots: 42,
                    type: 'Standard',
                    features: ['EV Charging', 'Covered']
                });
                setLoading(false);
            });
    }, [id, user.token]);

    if (loading) return <div className="text-center mt-5">Loading...</div>;
    if (!lot) return <div className="text-center mt-5">Lot not found</div>;

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    <h4>{lot.name}</h4>
                    <p className="text-secondary"><i className="bi bi-geo-alt me-2"></i>{lot.location}</p>
                    <div className="d-flex flex-wrap gap-3 my-3">
                        <span className="badge bg-primary p-2">${lot.hourly_rate}/hr</span>
                        <span className="badge bg-success p-2">{lot.available_spots} spots available</span>
                        <span className="badge bg-secondary p-2">{lot.type}</span>
                    </div>
                    <p className="details-features">
                        <strong>Features:</strong> {(lot.features || []).map((f, i) => <span key={i} className="badge bg-info text-dark me-1">{f}</span>)}
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