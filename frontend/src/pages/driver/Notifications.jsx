import { useState, useEffect } from 'react';
import { Card } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import '../../styles/pages/driver/Notifications.css';

const Notifications = () => {
    const { user } = useAuth();
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchNotifications = async () => {
            try {
                const response = await fetch(`http://localhost:8000/api/users/${user.id}/notifications`, {
                    headers: {
                        'Authorization': `Bearer ${user.token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch notifications');
                const data = await response.json();
                setNotifications(data);
            } catch (error) {
                console.error('Error fetching notifications:', error);
                // Fallback data
                setNotifications([
                    { id: 1, type: 'success', icon: 'bi-check-circle-fill', color: 'success', message: 'Reservation at Downtown Plaza confirmed for Aug 5, 09:00', time: '2 min ago' },
                    { id: 2, type: 'info', icon: 'bi-bell-fill', color: 'primary', message: 'Payment of $15.00 received for Mall Square', time: '1 hour ago' },
                ]);
            } finally {
                setLoading(false);
            }
        };
        fetchNotifications();
    }, [user.id, user.token]);

    if (loading) return <div className="text-center mt-5">Loading notifications...</div>;

    return (
        <div className="fade-in">
            <Card>
                <Card.Body>
                    {notifications.length === 0 ? (
                        <p className="text-secondary">No notifications.</p>
                    ) : (
                        notifications.map((n) => (
                            <div key={n.id} className="notification-item d-flex gap-3 align-items-start">
                                <i className={`bi ${n.icon} text-${n.color} icon`}></i>
                                <div>
                                    <p className="mb-0">{n.message}</p>
                                    <small className="text-secondary">{n.time || new Date(n.created_at).toLocaleDateString()}</small>
                                </div>
                            </div>
                        ))
                    )}
                </Card.Body>
            </Card>
        </div>
    );
};

export default Notifications;