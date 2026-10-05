export const API_URL=import.meta.env.VITE_API_URL || '/backend/api'
export class ApiError extends Error { constructor(message:string,public status:number,public errors:Record<string,string[]>={}){super(message)} }
export async function api<T>(path:string,options:RequestInit={}):Promise<T> {
  const headers=new Headers(options.headers)
  headers.set('Accept','application/json')
  const token=localStorage.getItem('invoice_token')
  if(token) headers.set('Authorization','Bearer '+token)
  if(options.body && !(options.body instanceof FormData)) headers.set('Content-Type','application/json')
  const res=await fetch(API_URL+path,{...options,headers})
  if(res.status===401){localStorage.removeItem('invoice_token');window.dispatchEvent(new Event('session-expired'))}
  if(!res.ok){const data=await res.json().catch(()=>({message:'Request failed. Please try again.'}));throw new ApiError(Object.values(data.errors || {}).flat().join(' ') || data.message,res.status,data.errors)}
  return res.status===204 ? undefined as T : res.json()
}
export const json=(method:string,body:unknown):RequestInit=>({method,body:JSON.stringify(body)})
export async function downloadPdf(id:number,number:string){
  const res=await fetch(API_URL+'/invoices/'+id+'/pdf',{headers:{Authorization:'Bearer '+localStorage.getItem('invoice_token')}})
  if(!res.ok) throw new Error('Unable to download PDF.')
  const url=URL.createObjectURL(await res.blob());const a=document.createElement('a');a.href=url;a.download=number+'.pdf';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000)
}
export async function downloadFile(path:string,filename:string){const res=await fetch(API_URL+path,{headers:{Authorization:'Bearer '+localStorage.getItem('invoice_token')}});if(!res.ok){const data=await res.json().catch(()=>({message:'Download failed.'}));throw new Error(data.message)}const url=URL.createObjectURL(await res.blob());const a=document.createElement('a');a.href=url;a.download=filename;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000)}
