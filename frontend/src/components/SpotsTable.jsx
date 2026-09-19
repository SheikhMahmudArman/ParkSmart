import {useState} from 'react';
import {api} from '../api';
import {useData,useAction,ActionNotice,Notice,Panel,Field,Select,Table} from './DataUI';
export default function SpotsTable({admin=false}){
 const state=useData('/spots'),lots=useData('/lots'),action=useAction(state.reload);
 const [form,setForm]=useState({parking_lot_id:'',space_number:'',type:'Standard'});
 async function add(e){e.preventDefault();if(await action.run(()=>api('/spots',{method:'POST',body:form})))setForm({...form,space_number:''});}
 return <Panel title="Parking spaces"><p>Occupied spaces are managed through Entry and Exit. Maintenance is allowed only when a space has no current or future bookings.</p>
 <Notice {...state}/><ActionNotice {...action}/>
 {admin&&<form onSubmit={add} className="mb-4">
 <Select label="Lot" name="parking_lot_id" required value={form.parking_lot_id} onChange={e=>setForm({...form,parking_lot_id:e.target.value})}><option value="">Choose lot</option>{(lots.data||[]).map(l=><option key={l.id} value={l.id}>{l.name}</option>)}</Select>
 <Field label="Space number" name="space_number" required maxLength={20} value={form.space_number} onChange={e=>setForm({...form,space_number:e.target.value})}/>
 <Field label="Type" name="type" required value={form.type} onChange={e=>setForm({...form,type:e.target.value})}/>
 <button className="btn btn-primary" disabled={action.busy}>Add space</button></form>}
 <Table rows={state.data||[]} columns={[{key:'lot_name',label:'Lot'},{key:'space_number',label:'Space'},{key:'type',label:'Type'},{key:'status',label:'Physical status'}]}
 actions={r=><div className="d-flex gap-2">{r.status!=='Occupied'&&<button className="btn btn-sm btn-outline-info" disabled={action.busy} onClick={()=>action.run(()=>api('/spots/'+r.id,{method:'PUT',body:{status:r.status==='Maintenance'?'Available':'Maintenance'}}))}>{r.status==='Maintenance'?'Make available':'Maintenance'}</button>}
 {admin&&<button className="btn btn-sm btn-outline-danger" disabled={action.busy} onClick={()=>{if(confirm('Delete this unused space?'))action.run(()=>api('/spots/'+r.id,{method:'DELETE'}));}}>Delete</button>}</div>}/>
 </Panel>;
}
