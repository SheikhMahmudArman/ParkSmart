import {Link} from 'react-router-dom';
import {useData,Notice,Panel} from '../../components/DataUI';
import SessionsTable from '../../components/SessionsTable';
export default function Dashboard(){const state=useData('/sessions/active');return <Panel title="Active parking sessions"><Notice {...state}/>
 <div className="d-flex gap-2 mb-3"><Link className="btn btn-success" to="/staff/entry">Record entry</Link><Link className="btn btn-primary" to="/staff/exit">Record exit</Link><button className="btn btn-outline-info" onClick={state.reload}>Refresh</button></div>
 <SessionsTable rows={state.data||[]}/></Panel>;}
