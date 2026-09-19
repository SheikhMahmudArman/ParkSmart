import {Link} from 'react-router-dom';
import {money} from '../../api';
import {useData,Notice,Panel} from '../../components/DataUI';
export default function Dashboard(){
 const stats=useData('/stats'),report=useData('/reports/revenue');
 return <Panel title="ParkSmart overview"><Notice error={stats.error||report.error} loading={stats.loading||report.loading}/>
 {stats.data&&<p>{stats.data.total_users} users · {stats.data.total_spaces} parking spaces · {money(stats.data.total_revenue)} recorded demo revenue</p>}
 {report.data&&<p>{report.data.occupancy}% physical occupancy · {report.data.total_reservations} reservations</p>}
 <div className="d-flex gap-3"><Link className="btn btn-primary" to="/admin/lots">Manage lots</Link><Link className="btn btn-outline-info" to="/admin/reports">View reports</Link></div></Panel>;
}
