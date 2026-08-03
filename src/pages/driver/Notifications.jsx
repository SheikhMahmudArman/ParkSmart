import { Card } from 'react-bootstrap';
import '../../styles/pages/driver/Notifications.css';

const notifications = [
    { id: 1, type: 'success', icon: 'bi-check-circle-fill', color: 'success', message: 'Reservation at Downtown Plaza confirmed for Aug 5, 09:00', time: '2 min ago' },
    { id: 2, type: 'info', icon: 'bi-bell-fill', color: 'primary', message: 'Payment of $15.00 received for Mall Square', time: '1 hour ago' },
    { id: 3, type: 'warning', icon: 'bi-exclamation-triangle-fill', color: 'warning', message: 'Your reservation at City Center expires in 30 min', time: '3 hours ago' },
];

const Notifications = () => {
    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    {notifications.map((n) => (
                        <div key={n.id} className="notification-item d-flex gap-3 align-items-start">
                            <i className={`bi ${n.icon} text-${n.color} icon`}></i>
                            <div>
                                <p className="mb-0">{n.message}</p>
                                <small className="text-secondary">{n.time}</small>
                            </div>
                        </div>
                    ))}
                </Card.Body>
            </Card>
        </div>
    );
};

export default Notifications;