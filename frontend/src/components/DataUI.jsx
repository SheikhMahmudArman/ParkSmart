/* oxlint-disable react/only-export-components -- Shared data hooks and small UI helpers. */
import {useCallback,useEffect,useState} from 'react';
import {api} from '../api';
export function useData(path) {
 const [data,setData]=useState(null),[error,setError]=useState(''),[loading,setLoading]=useState(true);
 const reload=useCallback(async()=>{
  setLoading(true);setError('');
  try{setData(await api(path));}catch(e){setError(e.message);}finally{setLoading(false);}
 },[path]);
 useEffect(()=>{reload();},[reload]);
 return {data,error,loading,reload};
}
export function Notice({error,loading}) {
 return <>{error && <div className="alert alert-danger" role="alert">{error}</div>}{loading && <p role="status">Loading…</p>}</>;
}
export function Table({rows=[],columns,actions}) {
 return <div className="table-responsive"><table className="table table-dark table-striped align-middle">
 <thead><tr>{columns.map(c=><th key={c.key}>{c.label}</th>)}{actions&&<th>Actions</th>}</tr></thead>
 <tbody>{rows.map((row,i)=><tr key={row.id??i}>{columns.map(c=><td key={c.key}>{c.render?c.render(row):String(row[c.key]??'—')}</td>)}{actions&&<td>{actions(row)}</td>}</tr>)}
 {!rows.length&&<tr><td colSpan={columns.length+(actions?1:0)}>No records yet.</td></tr>}</tbody></table></div>;
}
export function Field({label,...props}) {
 const id=props.name || label.toLowerCase().replace(/\W/g,'-');
 return <div className="mb-3"><label className="form-label" htmlFor={id}>{label}</label><input id={id} className="form-control" {...props}/></div>;
}
export function Select({label,children,...props}) {
 const id=props.name||label.toLowerCase().replace(/\W/g,'-');
 return <div className="mb-3"><label className="form-label" htmlFor={id}>{label}</label><select id={id} className="form-select" {...props}>{children}</select></div>;
}
export function Panel({title,children}) {return <section className="card p-4 mb-4"><h2 className="h4 mb-3">{title}</h2>{children}</section>;}
export function useAction(reload) {
 const [error,setError]=useState(''),[busy,setBusy]=useState(false),[message,setMessage]=useState('');
 async function run(work,success='Saved successfully') {
  setError('');setMessage('');setBusy(true);
  try {await work();setMessage(success);if(reload)await reload();return true;}catch(e){setError(e.message);return false;}finally{setBusy(false);}
 }
 return {run,busy,error,message};
}
export function ActionNotice({error,message}) {return <><Notice error={error}/>{message&&<div role="status" className="alert alert-success">{message}</div>}</>;}
