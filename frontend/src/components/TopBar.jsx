import { useAuth } from '../context/AuthContext';

const TopBar = ({ title, role }) => {
    const { user } = useAuth();
    const roleColors = {
        driver: 'primary',
        staff: 'success',
        admin: 'danger'
    };

    return (
        <div className="topbar">
            <div>
                <h5 className="mb-0 text-white">{title}</h5>
                <small className="text-secondary">{role.charAt(0).toUpperCase() + role.slice(1)} Panel</small>
            </div>
            <div className="d-flex align-items-center gap-3">
                <i className="bi bi-bell fs-5 text-secondary"></i>
                <div className="d-flex align-items-center gap-2">
                    <span className="text-secondary">{user?.name || 'User'}</span>
                    <span className={`badge bg-${roleColors[role]}`}>{role}</span>
                </div>
            </div>
        </div>
    );
};

export default TopBar;