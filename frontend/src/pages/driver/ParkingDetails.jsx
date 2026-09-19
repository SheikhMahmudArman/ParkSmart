import {Link,useParams} from 'react-router-dom';
import {money} from '../../api';
import {useData,Notice,Panel} from '../../components/DataUI';
export default function ParkingDetails(){
 const {id}=useParams();const state=useData('/lots/'+id),lot=state.data;
 return <Panel title={lot?.name||'Parking details'}><Notice {...state}/>{lot&&<>
 <p>{lot.location}</p><p>{money(lot.hourly_rate)} / started hour · {lot.available_spots} available now out of {lot.total_spots}</p>
 <p>{Array.isArray(lot.features)?lot.features.join(' · '):''}</p>
 <Link className="btn btn-success" to={`/driver/reserve/${id}`}>Choose booking time</Link></>}</Panel>;
}
