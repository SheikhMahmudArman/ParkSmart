import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import Login from './pages/Login';
import Layout from './components/Layout';

// Placeholder components – will be replaced in later commits
const DriverDashboard = () => <div>Driver Dashboard</div>;
const StaffDashboard = () => <div>Staff Dashboard</div>;
const AdminDashboard = () => <div>Admin Dashboard</div>;

function App() {
  const { user } = useAuth();

  if (!user) {
    return <Login />;
  }

  const role = user.role;

  if (role === 'driver') {
    return (
      <Layout role="driver">
        <Routes>
          <Route path="/" element={<Navigate to="/driver/dashboard" />} />
          <Route path="/driver/dashboard" element={<DriverDashboard />} />
          <Route path="*" element={<Navigate to="/driver/dashboard" />} />
        </Routes>
      </Layout>
    );
  }

  if (role === 'staff') {
    return (
      <Layout role="staff">
        <Routes>
          <Route path="/" element={<Navigate to="/staff/dashboard" />} />
          <Route path="/staff/dashboard" element={<StaffDashboard />} />
          <Route path="*" element={<Navigate to="/staff/dashboard" />} />
        </Routes>
      </Layout>
    );
  }

  if (role === 'admin') {
    return (
      <Layout role="admin">
        <Routes>
          <Route path="/" element={<Navigate to="/admin/dashboard" />} />
          <Route path="/admin/dashboard" element={<AdminDashboard />} />
          <Route path="*" element={<Navigate to="/admin/dashboard" />} />
        </Routes>
      </Layout>
    );
  }

  return <Navigate to="/" />;
}

export default App;