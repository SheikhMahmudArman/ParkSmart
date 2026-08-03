import { Card, Button } from 'react-bootstrap';
import '../../styles/pages/staff/Spots.css';

const spots = [
    { id: 1, lot: 'Downtown Plaza', spot: 'A01-A20', status: 'Available', type: 'Standard' },
    { id: 2, lot: 'Downtown Plaza', spot: 'A21-A30', status: 'Occupied', type: 'EV' },
    { id: 3, lot: 'Mall Square', spot: 'B01-B15', status: 'Available', type: 'Standard' },
    { id: 4, lot: 'Mall Square', spot: 'B16-B25', status: 'Occupied', type: 'Disabled' },
    { id: 5, lot: 'Airport Terminal', spot: 'C01-C10', status: 'Available', type: 'Premium' },
];

const StaffSpots = () => {
    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    <div className="table-wrap spots-table">
                        <table className="table">
                            <thead>
                                <tr><th>Lot</th><th>Spot</th><th>Status</th><th>Type</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                {spots.map((s) => (
                                    <tr key={s.id}>
                                        <td>{s.lot}</td>
                                        <td>{s.spot}</td>
                                        <td><span className={`badge bg-${s.status === 'Available' ? 'success' : 'danger'} status-badge`}>{s.status}</span></td>
                                        <td>{s.type}</td>
                                        <td>
                                            <Button size="sm" variant="outline-secondary" onClick={() => alert(`Toggle status for ${s.lot} - ${s.spot}`)}>
                                                Toggle
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card.Body>
            </Card>
        </div>
    );
};

export default StaffSpots;