import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import Landing from './pages/Landing';
import Login from './pages/Login';
import Layout from './components/Layout';

// Driver pages
import DriverDashboard from './pages/driver/Dashboard';
import SearchParking from './pages/driver/SearchParking';
import ParkingDetails from './pages/driver/ParkingDetails';
import ReserveSpot from './pages/driver/ReserveSpot';
import MyReservations from './pages/driver/MyReservations';
import Payment from './pages/driver/Payment';
import Notifications from './pages/driver/Notifications';
import Profile from './pages/driver/Profile';

// Staff pages
import StaffDashboard from './pages/staff/Dashboard';
import StaffReservations from './pages/staff/Reservations';
import Entry from './pages/staff/Entry';
import Exit from './pages/staff/Exit';
import StaffSpots from './pages/staff/Spots';

// Admin pages
import AdminDashboard from './pages/admin/Dashboard';
import AdminUsers from './pages/admin/Users';
import AdminLots from './pages/admin/Lots';
import AdminSpots from './pages/admin/Spots';
import AdminStaff from './pages/admin/Staff';
import AdminPayments from './pages/admin/Payments';
import AdminReports from './pages/admin/Reports';
import Register from './pages/Register';
function App() {
  const { user } = useAuth();

  // Not logged in – public routes
  if (!user) {
    return (
      <Routes>
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<Login />} />
        <Route path="*" element={<Navigate to="/" />} />
        <Route path="/register" element={<Register />} />
      </Routes>
    );
  }

  // Logged in – role-based routing
  const role = user.role;

  if (role === 'driver') {
    return (
      <Layout role="driver">
        <Routes>

          <Route path="/" element={<Navigate to="/driver/dashboard" />} />
          <Route path="/driver/dashboard" element={<DriverDashboard />} />
          <Route path="/driver/search" element={<SearchParking />} />
          <Route path="/driver/details/:id" element={<ParkingDetails />} />
          <Route path="/driver/reserve/:id" element={<ReserveSpot />} />
          <Route path="/driver/reservations" element={<MyReservations />} />
          <Route path="/driver/payment" element={<Payment />} />
          <Route path="/driver/notifications" element={<Notifications />} />
          <Route path="/driver/profile" element={<Profile />} />
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
          <Route path="/staff/reservations" element={<StaffReservations />} />
          <Route path="/staff/entry" element={<Entry />} />
          <Route path="/staff/exit" element={<Exit />} />
          <Route path="/staff/spots" element={<StaffSpots />} />
          <Route path="*" element={<Navigate to="/staff/dashboard" />} />
        </Routes>
      </Layout>
    );
  }

  if (role === 'admin') {
    return (
      <Layout role="admin">
        <Routes>
          <Route path="/register" element={<Register />} />
          <Route path="/" element={<Navigate to="/admin/dashboard" />} />
          <Route path="/admin/dashboard" element={<AdminDashboard />} />
          <Route path="/admin/users" element={<AdminUsers />} />
          <Route path="/admin/lots" element={<AdminLots />} />
          <Route path="/admin/spots" element={<AdminSpots />} />
          <Route path="/admin/staff" element={<AdminStaff />} />
          <Route path="/admin/payments" element={<AdminPayments />} />
          <Route path="/admin/reports" element={<AdminReports />} />
          <Route path="*" element={<Navigate to="/admin/dashboard" />} />
        </Routes>
      </Layout>
    );
  }

  return <Navigate to="/" />;
}

export default App;