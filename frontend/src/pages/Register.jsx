import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const Register = () => {
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [password_confirmation, setPasswordConfirmation] = useState('');
    const [role, setRole] = useState('driver');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await fetch('http://localhost:8000/api/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    email,
                    password,
                    password_confirmation,
                    role
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Registration failed');
            }

            alert('Registration successful! Please login.');
            navigate('/login');
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="d-flex justify-content-center align-items-center vh-100" style={{ background: '#121212' }}>
            <div className="card p-4" style={{ maxWidth: '500px', width: '100%' }}>
                <h2 className="text-center">Register</h2>
                <form onSubmit={handleSubmit}>
                    <div className="mb-3">
                        <label>Name</label>
                        <input type="text" className="form-control" value={name} onChange={e => setName(e.target.value)} required />
                    </div>
                    <div className="mb-3">
                        <label>Email</label>
                        <input type="email" className="form-control" value={email} onChange={e => setEmail(e.target.value)} required />
                    </div>
                    <div className="mb-3">
                        <label>Password</label>
                        <input type="password" className="form-control" value={password} onChange={e => setPassword(e.target.value)} required />
                    </div>
                    <div className="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" className="form-control" value={password_confirmation} onChange={e => setPasswordConfirmation(e.target.value)} required />
                    </div>
                    <div className="mb-3">
                        <label>Role</label>
                        <select className="form-select" value={role} onChange={e => setRole(e.target.value)}>
                            <option value="driver">Driver</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    {error && <div className="alert alert-danger">{error}</div>}
                    <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                        {loading ? 'Registering...' : 'Register'}
                    </button>
                </form>
                <p className="mt-3 text-center">
                    Already have an account? <a href="/login">Login</a>
                </p>
            </div>
        </div>
    );
};

export default Register;