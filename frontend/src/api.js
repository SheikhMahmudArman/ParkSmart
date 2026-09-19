const base = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '');
export async function api(path, options = {}) {
 const token = localStorage.getItem('token');
 let response;
 try {
  response = await fetch(base + path, {
   ...options,
   headers: { Accept: 'application/json', ...(options.body ? {'Content-Type':'application/json'} : {}),
    ...(token ? {Authorization: `Bearer ${token}`} : {}), ...options.headers },
   body: options.body === undefined ? undefined : JSON.stringify(options.body),
  });
 } catch { throw new Error('Cannot reach the API. Start Laravel on port 8000 and check frontend/.env.'); }
 const data = await response.json().catch(() => ({}));
 if (!response.ok) {
  if(response.status === 401 && path !== '/login') window.dispatchEvent(new Event('auth-expired'));
  throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message || data.error || `Request failed (${response.status})`);
 }
 return data;
}
export const money = value => new Intl.NumberFormat('en-BD', {style:'currency',currency:'BDT'}).format(Number(value || 0));
