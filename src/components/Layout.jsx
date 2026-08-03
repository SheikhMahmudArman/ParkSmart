import Sidebar from './Sidebar';
import TopBar from './TopBar';
import { useAuth } from '../context/AuthContext';
import { useLocation } from 'react-router-dom';

const Layout = ({ children, role }) => {
    const { logout } = useAuth();
    const location = useLocation();

    const getTitle = () => {
        const path = location.pathname;
        if (path.includes('dashboard')) return 'Dashboard';
        if (path.includes('search')) return 'Search Parking';
        if (path.includes('reservations')) return 'Reservations';
        if (path.includes('payment')) return 'Payments';
        if (path.includes('notifications')) return 'Notifications';
        if (path.includes('profile')) return 'Profile';
        if (path.includes('entry')) return 'Record Entry';
        if (path.includes('exit')) return 'Record Exit';
        if (path.includes('spots')) return 'Parking Spots';
        if (path.includes('users')) return 'Users';
        if (path.includes('lots')) return 'Parking Lots';
        if (path.includes('staff')) return 'Staff Management';
        if (path.includes('reports')) return 'Reports';
        return 'Dashboard';
    };

    return (
        <div className="d-flex">
            <Sidebar role={role} onLogout={logout} />
            <div className="flex-grow-1" style={{ minHeight: '100vh', background: '#121212' }}>
                <TopBar title={getTitle()} role={role} />
                <div className="p-4">
                    {children}
                </div>
            </div>
        </div>
    );
};

export default Layout;