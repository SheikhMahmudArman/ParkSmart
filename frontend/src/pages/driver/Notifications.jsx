import {useAuth} from '../../context/AuthContext';
import {useData,Notice,Panel,Table} from '../../components/DataUI';
export default function Notifications(){const {user}=useAuth(),state=useData(`/users/${user.id}/notifications`);return <Panel title="Reservation updates"><Notice {...state}/><Table rows={state.data||[]} columns={[{key:'message',label:'Update'},{key:'created_at',label:'Time'}]}/></Panel>;}
