import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

const DEMO_USERS = [
    { email: 'driver@parking.com', password: '123456', role: 'driver', name: 'Alex Driver' },
    { email: 'staff@parking.com', password: '123456', role: 'staff', name: 'Sam Staff' },
    { email: 'admin@parking.com', password: '123456', role: 'admin', name: 'Admin User' },
];

const Login = () => {
    const [email, setEmail] = useState('driver@parking.com');
    const [password, setPassword] = useState('123456');
    const [error, setError] = useState('');
    const { login } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = (e) => {
        e.preventDefault();
        const user = DEMO_USERS.find(u => u.email === email && u.password === password);
        if (user) {
            login(user);
            const route = { driver: '/driver/dashboard', staff: '/staff/dashboard', admin: '/admin/dashboard' };
            navigate(route[user.role]);
        } else {
            setError('Invalid credentials. Use demo accounts.');
        }
    };

    const fillDemo = (role) => {
        const user = DEMO_USERS.find(u => u.role === role);
        if (user) {
            setEmail(user.email);
            setPassword(user.password);
            setError('');
        }
    };

    return (
        <div className="login-page d-flex justify-content-center align-items-center vh-100" style={{ background: '#121212' }}>
            <div className="card p-4" style={{ maxWidth: '400px', width: '100%' }}>
                <div className="text-center mb-4">
                    <h2 className="text-white"><i className="bi bi-p-circle me-2 text-primary"></i>ParkManager</h2>
                    <p className="text-secondary">Sign in to your account</p>
                </div>

                <div className="d-flex gap-2 mb-3">
                    <button className="btn btn-outline-secondary flex-fill" onClick={() => fillDemo('driver')}>
                        <i className="bi bi-person me-1"></i>Driver
                    </button>
                    <button className="btn btn-outline-secondary flex-fill" onClick={() => fillDemo('staff')}>
                        <i className="bi bi-person-badge me-1"></i>Staff
                    </button>
                    <button className="btn btn-outline-secondary flex-fill" onClick={() => fillDemo('admin')}>
                        <i className="bi bi-person-lock me-1"></i>Admin
                    </button>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="mb-3">
                        <label className="form-label">Email</label>
                        <input
                            type="email"
                            className="form-control"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="Enter email"
                            required
                        />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Password</label>
                        <input
                            type="password"
                            className="form-control"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Enter password"
                            required
                        />
                    </div>
                    {error && <div className="alert alert-danger">{error}</div>}
                    <button type="submit" className="btn btn-primary w-100">
                        <i className="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
                <div className="mt-3 text-center text-secondary small">
                    <i className="bi bi-info-circle me-1"></i>Demo: click role button to autofill
                </div>
            </div>
        </div>
    );
};

export default Login;