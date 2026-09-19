import {useState} from 'react';
import {Link,useNavigate} from 'react-router-dom';
import {useAuth} from '../context/AuthContext';
import {api} from '../api';
import {Field} from '../components/DataUI';
export default function Login(){
 const [email,setEmail]=useState(''),[password,setPassword]=useState(''),[error,setError]=useState(''),[busy,setBusy]=useState(false);
 const {login}=useAuth(),navigate=useNavigate();
 async function submit(e){e.preventDefault();setBusy(true);setError('');try{const data=await api('/login',{method:'POST',body:{email,password}});login({...data.user,token:data.token});navigate('/'+data.user.role+'/dashboard');}catch(e){setError(e.message);}finally{setBusy(false);}}
 return <main className="container py-5" style={{maxWidth:480}}><div className="card p-4"><h1 className="h3">Sign in to ParkSmart</h1>{error&&<div role="alert" className="alert alert-danger">{error}</div>}
 <form onSubmit={submit}><Field label="Email" type="email" required autoComplete="username" value={email} onChange={e=>setEmail(e.target.value)}/><Field label="Password" type="password" required autoComplete="current-password" value={password} onChange={e=>setPassword(e.target.value)}/>
 <button className="btn btn-primary w-100" disabled={busy}>{busy?'Signing in…':'Sign in'}</button></form>
 <Link className="mt-3" to="/register">Create a driver account</Link><Link className="mt-2" to="/">Back to home</Link></div></main>;
}
