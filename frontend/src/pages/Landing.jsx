import {Link} from 'react-router-dom';
import {Container,Row,Col,Button} from 'react-bootstrap';
import {useData,Notice} from '../components/DataUI';
import {money} from '../api';
import Chatbot from '../components/Chatbot';
import '../styles/pages/Landing.css';
export default function Landing(){
 const stats=useData('/stats'),revenue=useData('/revenue-by-lot');
 const features=[
  {icon:'bi-search',title:'Find parking',desc:'Browse parking lots and choose your booking time.'},
  {icon:'bi-calendar-check',title:'Reserve a space',desc:'Book a registered vehicle with a price calculated for your interval.'},
  {icon:'bi-credit-card',title:'Demo payments',desc:'Record simulated payments and view receipts. No money is charged.'},
  {icon:'bi-bell',title:'Reservation updates',desc:'See status changes recorded by database audit triggers.'},
  {icon:'bi-bar-chart',title:'Admin reports',desc:'Review occupancy, revenue and driver spending.'},
  {icon:'bi-people',title:'Three roles',desc:'Separate driver, staff and administrator workflows.'}
 ];
 return <div className="landing-wrapper">
 <section className="hero-section"><Container><Row className="justify-content-center text-center"><Col md={8}>
 <h1 className="hero-title">Park<span className="highlight">Smart</span></h1>
 <p className="hero-subtitle">Find, reserve and manage parking in one place.</p>
 <div className="hero-actions"><Button as={Link} to="/login" size="lg">Sign in</Button><Button as={Link} to="/register" variant="outline-light" size="lg">Create driver account</Button></div>
 <div className="hero-stats mt-5"><div className="stat-item"><span className="stat-number">{stats.data?.total_spaces??'—'}</span><span className="stat-label">Parking spaces</span></div><div className="stat-item"><span className="stat-number">{stats.data?.total_users??'—'}</span><span className="stat-label">Registered users</span></div></div>
 <Notice error={stats.error}/></Col></Row></Container></section>
 <section className="revenue-section"><Container><h2 className="text-center mb-4">Recorded demo revenue</h2><p className="text-center">{stats.data?money(stats.data.total_revenue):'—'} · Coursework demonstration</p><Notice {...revenue}/>
 <div className="table-responsive"><table className="table table-dark"><thead><tr><th>Lot</th><th>Payments</th><th>Revenue</th></tr></thead><tbody>{(revenue.data||[]).map((r,i)=><tr key={r.lot_id??i}><td>{r.lot_name}</td><td>{r.total_transactions}</td><td>{money(r.total_revenue)}</td></tr>)}{!revenue.loading&&!revenue.data?.length&&<tr><td colSpan="3">No payments recorded yet.</td></tr>}</tbody></table></div></Container></section>
 <section className="features-section"><Container><h2 className="text-center mb-4">Parking from booking to exit</h2><Row className="g-4">{features.map(f=><Col md={4} key={f.title}><div className="feature-card"><div className="feature-icon"><i className={`bi ${f.icon}`}/></div><h3 className="h5">{f.title}</h3><p>{f.desc}</p></div></Col>)}</Row></Container></section>
 <footer className="landing-footer"><Container>© {new Date().getFullYear()} ParkSmart · Times: Asia/Dhaka · Currency: BDT</Container></footer>
 <Chatbot />
 </div>;
}
