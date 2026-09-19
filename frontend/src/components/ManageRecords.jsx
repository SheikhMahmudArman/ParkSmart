import {useState} from 'react';
import {api,money} from '../api';
import {useData,useAction,ActionNotice,Notice,Panel,Field,Select,Table} from './DataUI';
export default function ManageRecords({kind}) {
 const isLot=kind==='lots',isStaff=kind==='staff';
 const empty=isLot?{name:'',location:'',total_spaces:10,hourly_rate:50,type:'Standard',features:''}:{name:'',email:'',password:'',role:isStaff?'staff':'driver',phone:'',assigned_lot:''};
 const state=useData('/'+kind),action=useAction(state.reload);
 const [form,setForm]=useState(null),[editId,setEditId]=useState(null);
 function start(row=null){setEditId(row?.id??null);setForm(row?{...empty,...row,features:Array.isArray(row.features)?row.features.join(', '):'',password:''}:{...empty});}
 async function submit(e){
  e.preventDefault();
  const body=isLot?{...form,features:form.features.split(',').map(s=>s.trim()).filter(Boolean)}:{...form};
  if(editId){delete body.password;delete body.total_spaces;}
  if(await action.run(()=>api('/'+kind+(editId?'/'+editId:''),{method:editId?'PUT':'POST',body})))setForm(null);
 }
 const columns=isLot?[{key:'name',label:'Name'},{key:'location',label:'Location'},{key:'total_spots',label:'Spaces'},{key:'available_spots',label:'Free now'},{key:'hourly_rate',label:'Rate',render:r=>money(r.hourly_rate)}]:
 [{key:'name',label:'Name'},{key:'email',label:'Email'},{key:'role',label:'Role'},{key:'assigned_lot',label:'Assignment note'}];
 return <Panel title={isLot?'Parking lots':isStaff?'Staff accounts':'User accounts'}>
 <Notice {...state}/><ActionNotice {...action}/>
 <button className="btn btn-primary mb-3" onClick={()=>start()}>Add {isLot?'lot':'account'}</button>
 {form&&<form onSubmit={submit} className="border rounded p-3 mb-4">
 <h3 className="h5">{editId?'Edit':'Add'} {isLot?'lot':'account'}</h3>
 {Object.keys(empty).filter(k=>!(editId&&['password','total_spaces'].includes(k))).map(k=>{
  if(k==='role')return <Select key={k} label="Role" name={k} value={form[k]} onChange={e=>setForm({...form,[k]:e.target.value})}>
   {(isStaff?['staff','admin']:['driver','staff','admin']).map(r=><option key={r}>{r}</option>)}</Select>;
  return <Field key={k} label={k.replaceAll('_',' ')} name={k} value={form[k]??''}
   type={k==='email'?'email':k==='password'?'password':['total_spaces','hourly_rate'].includes(k)?'number':'text'}
   min={k==='total_spaces'?1:0} max={k==='total_spaces'?1000:undefined} step={k==='hourly_rate'?'0.01':undefined} minLength={k==='password'?8:undefined}
   required={!['phone','assigned_lot','features'].includes(k)}
   onChange={e=>setForm({...form,[k]:e.target.value})}/>;
 })}
 {isLot&&<p>Features: comma-separated. Creating a lot generates its spaces. Add or remove unused spaces from Parking Spots.</p>}
 <button className="btn btn-success me-2" disabled={action.busy}>Save</button><button type="button" className="btn btn-secondary" onClick={()=>setForm(null)}>Close</button>
 </form>}
 <Table rows={state.data||[]} columns={columns} actions={r=><div className="d-flex gap-2">
 <button className="btn btn-sm btn-outline-info" onClick={()=>start(r)}>Edit</button>
 <button className="btn btn-sm btn-outline-danger" disabled={action.busy} onClick={()=>{if(confirm('Delete this record? Records with parking history are retained.'))action.run(()=>api('/'+kind+'/'+r.id,{method:'DELETE'}),'Record deleted');}}>Delete</button>
 </div>}/></Panel>;
}
