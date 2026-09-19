import {useState} from 'react';
import {Link,useNavigate} from 'react-router-dom';
import {api} from '../api';
import {Field} from '../components/DataUI';
export default function Register(){
 const [form,setForm]=useState({name:'',email:'',password:'',password_confirmation:''}),[error,setError]=useState(''),[busy,setBusy]=useState(false);
 const navigate=useNavigate();
 async function submit(e){e.preventDefault();setBusy(true);setError('');try{await api('/register',{method:'POST',body:form});navigate('/login');}catch(e){setError(e.message);}finally{setBusy(false);}}
 return <main className="container py-5" style={{maxWidth:480}}><div className="card p-4"><h1 className="h3">Create driver account</h1><p>Staff accounts are created by an administrator.</p>
 {error&&<div role="alert" className="alert alert-danger">{error}</div>}<form onSubmit={submit}>
 {Object.keys(form).map(k=><Field key={k} label={k==='password_confirmation'?'Confirm password':k} name={k} required minLength={k.includes('password')?8:undefined} type={k.includes('password')?'password':k==='email'?'email':'text'} value={form[k]} onChange={e=>setForm({...form,[k]:e.target.value})}/>)}
 <button className="btn btn-primary w-100" disabled={busy}>{busy?'Creating account…':'Register'}</button></form><Link className="mt-3" to="/login">Already registered? Sign in</Link></div></main>;
}
