import { NavLink } from 'react-router-dom';

const Sidebar = ({ role, onLogout }) => {
    const navItems = {
        driver: [
            { to: '/driver/dashboard', icon: 'bi-grid', label: 'Dashboard' },
            { to: '/driver/search', icon: 'bi-search', label: 'Search Parking' },
            { to: '/driver/reservations', icon: 'bi-calendar-check', label: 'My Reservations' },
            { to: '/driver/payment', icon: 'bi-credit-card', label: 'Payment' },
            { to: '/driver/notifications', icon: 'bi-bell', label: 'Notifications' },
            { to: '/driver/profile', icon: 'bi-person', label: 'Profile' },
        ],
        staff: [
            { to: '/staff/dashboard', icon: 'bi-grid', label: 'Dashboard' },
            { to: '/staff/reservations', icon: 'bi-calendar-check', label: 'Reservations' },
            { to: '/staff/entry', icon: 'bi-box-arrow-in-right', label: 'Entry' },
            { to: '/staff/exit', icon: 'bi-box-arrow-right', label: 'Exit' },
            { to: '/staff/spots', icon: 'bi-pin-map', label: 'Parking Spots' },
        ],
        admin: [
            { to: '/admin/dashboard', icon: 'bi-grid', label: 'Dashboard' },
            { to: '/admin/users', icon: 'bi-people', label: 'Users' },
            { to: '/admin/lots', icon: 'bi-building', label: 'Parking Lots' },
            { to: '/admin/spots', icon: 'bi-pin-map', label: 'Parking Spots' },
            { to: '/admin/staff', icon: 'bi-person-badge', label: 'Staff' },
            { to: '/admin/payments', icon: 'bi-credit-card', label: 'Payments' },
            { to: '/admin/reports', icon: 'bi-bar-chart', label: 'Reports' },
        ]
    };

    const items = navItems[role] || [];

    return (
        <div className="sidebar d-flex flex-column flex-shrink-0" style={{ width: '250px' }}>
            <div className="brand d-flex align-items-center gap-2 px-3">
                <i className="bi bi-p-circle fs-3"></i>
                <span>ParkManager</span>
            </div>
            <ul className="nav flex-column flex-grow-1">
                {items.map((item) => (
                    <li className="nav-item" key={item.to}>
                        <NavLink
                            to={item.to}
                            className={({ isActive }) =>
                                `nav-link d-flex align-items-center gap-2 ${isActive ? 'active' : ''}`
                            }
                            end
                        >
                            <i className={`bi ${item.icon}`}></i>
                            {item.label}
                        </NavLink>
                    </li>
                ))}
            </ul>
            <hr className="text-secondary" />
            <button className="btn btn-outline-danger w-100" onClick={onLogout}>
                <i className="bi bi-box-arrow-right me-2"></i>Logout
            </button>
        </div>
    );
};

export default Sidebar;