import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

const Login = () => {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const { login } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await fetch('http://localhost:8000/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Login failed');
            }

            // Store user and token
            const userData = {
                id: data.user.id,
                name: data.user.name,
                email: data.user.email,
                role: data.user.role,
                token: data.token
            };

            login(userData);

            // Redirect based on role
           switch (data.user.role) {
                case 'driver':
                    navigate('/driver/dashboard');
                    break;

                case 'staff':
                    navigate('/staff/dashboard');
                    break;

                case 'admin':
                    navigate('/admin/dashboard');
                    break;

                default:
                    setError('Invalid user role');
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

   

    return (
        <div className="d-flex justify-content-center align-items-center vh-100" style={{ background: '#121212' }}>
            <div className="card p-4" style={{ maxWidth: '400px', width: '100%' }}>
                <h2 className="text-center">Login</h2>
                <form onSubmit={handleSubmit}>
                    <div className="mb-3">
                        <label>Email</label>
                        <input type="email" className="form-control" value={email} onChange={e => setEmail(e.target.value)} required />
                    </div>
                    <div className="mb-3">
                        <label>Password</label>
                        <input type="password" className="form-control" value={password} onChange={e => setPassword(e.target.value)} required />
                    </div>
                    {error && <div className="alert alert-danger">{error}</div>}
                    <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                        {loading ? 'Logging in...' : 'Sign In'}
                    </button>
                </form>
                <p className="mt-3 text-center">
                    Don't have an account? <a href="/register">Register</a>
                </p>
            </div>
        </div>
    );
};

export default Login;