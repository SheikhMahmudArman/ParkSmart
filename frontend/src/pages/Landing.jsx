import { Link } from 'react-router-dom';
import { Container, Row, Col, Button } from 'react-bootstrap';
import '../styles/pages/Landing.css';
import { useState, useEffect } from 'react';

const Landing = () => {
    const features = [
        { icon: 'bi-search', title: 'Find Parking', desc: 'Search available spots by location, price, or type in real-time.' },
        { icon: 'bi-calendar-check', title: 'Reserve Instantly', desc: 'Book your spot with a few clicks and get instant confirmation.' },
        { icon: 'bi-credit-card', title: 'Secure Payments', desc: 'Pay seamlessly with full transaction history and receipts.' },
        { icon: 'bi-bell', title: 'Smart Notifications', desc: 'Get alerts for reservations, payments, and entry/exit updates.' },
        { icon: 'bi-bar-chart', title: 'Admin Insights', desc: 'Monitor revenue, occupancy, and generate detailed reports.' },
        { icon: 'bi-people', title: 'Role-based Access', desc: 'Driver, Staff, Admin – each with tailored dashboards and tools.' },
    ];

    const [stats, setStats] = useState({ total_users: 0, total_revenue: 0 });
    const [statsLoading, setStatsLoading] = useState(true);

    // NEW: State for revenue by lot
    const [revenueByLot, setRevenueByLot] = useState([]);
    const [revenueLoading, setRevenueLoading] = useState(true);

    useEffect(() => {
        // Fetch total stats
        fetch('http://localhost:8000/api/stats')
            .then(res => res.json())
            .then(data => {
                setStats(data);
                setStatsLoading(false);
            })
            .catch(err => {
                console.error('Error fetching stats:', err);
                setStatsLoading(false);
            });

        // NEW: Fetch revenue by lot (JOIN + GROUP BY)
        fetch('http://localhost:8000/api/revenue-by-lot')
            .then(res => res.json())
            .then(data => {
                setRevenueByLot(data);
                setRevenueLoading(false);
            })
            .catch(err => {
                console.error('Error fetching revenue-by-lot:', err);
                setRevenueLoading(false);
            });
    }, []);

    return (
        <div className="landing-wrapper">
            {/* Hero Section */}
            <section className="hero-section">
                <Container>
                    <Row className="justify-content-center text-center">
                        <Col md={8} lg={7}>

                            <h1 className="hero-title">
                                Park<span className="highlight">Smart</span>
                            </h1>
                            <p className="hero-subtitle">
                                The all‑in‑one platform for drivers, staff, and administrators to find, reserve, and manage parking with ease.
                            </p>
                            <div className="hero-actions">
                                <Button as={Link} to="/login" variant="primary" size="lg" className="px-5 py-3">
                                    <i className="bi bi-box-arrow-in-right me-2"></i>Get Started
                                </Button>
                                <Button as="a" href="#features" variant="outline-light" size="lg" className="px-4 py-3">
                                    <i className="bi bi-chevron-down me-2"></i>Learn More
                                </Button>
                            </div>
                            <div className="hero-stats mt-5">
                                <div className="stat-item">
                                    <span className="stat-number">24/7</span>
                                    <span className="stat-label">Availability</span>
                                </div>
                                <div className="stat-item">
                                    <span className="stat-number">650+</span>
                                    <span className="stat-label">Parking Spots</span>
                                </div>
                                <div className="stat-item">
                                    <span className="stat-number">98%</span>
                                    <span className="stat-label">Satisfaction</span>
                                </div>
                            </div>
                        </Col>
                    </Row>
                </Container>
            </section>

            {/* Stats Section – Total Users & Revenue */}
            <section className="stats-section">
                <Container>
                    <Row className="g-4 justify-content-center">
                        <Col md={6} lg={5}>
                            <div className="stat-card-big text-center glass">
                                <div className="stat-number">
                                    {statsLoading ? '...' : (typeof stats.total_users === 'number' ? stats.total_users : 0)}
                                </div>
                                <div className="stat-label">Total Users</div>
                                <i className="bi bi-people stat-icon-big"></i>
                            </div>
                        </Col>
                        <Col md={6} lg={5}>
                            <div className="stat-card-big text-center glass">
                                <div className="stat-number">
                                    {statsLoading ? '...' : `$${Number(stats.total_revenue).toFixed(2)}`}
                                </div>
                                <div className="stat-label">Total Revenue</div>
                                <i className="bi bi-currency-dollar stat-icon-big"></i>
                            </div>
                        </Col>
                    </Row>
                </Container>
            </section>

            {/* NEW: Revenue by Parking Lot – JOIN + GROUP BY */}
            <section className="revenue-section">
                <Container>
                    <h2 className="text-center text-white mb-4">Revenue by Parking Lot</h2>
                    <Row className="justify-content-center">
                        <Col md={8} lg={6}>
                            <div className="card glass p-3">
                                {revenueLoading ? (
                                    <p className="text-center text-secondary">Loading revenue data...</p>
                                ) : revenueByLot.length === 0 ? (
                                    <p className="text-center text-secondary">No revenue data available.</p>
                                ) : (
                                    <div className="table-wrap">
                                        <table className="table table-dark table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Parking Lot</th>
                                                    <th>Transactions</th>
                                                    <th>Total Revenue</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {revenueByLot.map((item, index) => (
                                                    <tr key={index}>
                                                        <td><strong>{item.lot_name}</strong></td>
                                                        <td>{item.total_transactions}</td>
                                                        <td>${parseFloat(item.total_revenue).toFixed(2)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </Col>
                    </Row>
                </Container>
            </section>

            {/* Features Section */}
            <section id="features" className="features-section">
                <Container>
                    <div className="section-header text-center">
                        <h2>Everything You Need</h2>
                        <p className="text-secondary">Built for efficiency, designed for simplicity.</p>
                    </div>
                    <Row className="g-4">
                        {features.map((f, idx) => (
                            <Col md={4} sm={6} key={idx}>
                                <div className="feature-card">
                                    <div className="feature-icon">
                                        <i className={`bi ${f.icon}`}></i>
                                    </div>
                                    <h5>{f.title}</h5>
                                    <p className="text-secondary">{f.desc}</p>
                                </div>
                            </Col>
                        ))}
                    </Row>
                </Container>
            </section>

            {/* CTA Section */}
            <section className="cta-section">
                <Container>
                    <Row className="justify-content-center text-center">
                        <Col md={8}>
                            <h2>Ready to Simplify Parking?</h2>
                            <p className="text-secondary mb-4">
                                Join thousands of users who already trust ParkManager for their parking needs.
                            </p>
                            <Button as={Link} to="/login" variant="primary" size="lg" className="px-5 py-3">
                                <i className="bi bi-person-plus me-2"></i>Sign In Now
                            </Button>
                        </Col>
                    </Row>
                </Container>
            </section>

            {/* Footer */}
            <footer className="landing-footer">
                <Container>
                    <div className="d-flex flex-wrap justify-content-between align-items-center">
                        <span>© {new Date().getFullYear()} ParkSmart. All rights reserved.</span>
                        <div className="footer-links">
                            <a href="#">Privacy</a>
                            <a href="#">Terms</a>
                            <a href="#">Contact</a>
                        </div>
                    </div>
                </Container>
            </footer>
        </div>
    );
};

export default Landing;