import type { User } from '../types'
export function moduleEnabled(user:User|null|undefined,id:string):boolean {
 if(!user)return false
 if(['team','business_documents'].includes(id)&&!['owner','admin'].includes(user.role))return false
 const mode=user.workspace_mode??'advanced'
 return (mode==='simple'?['quotations']:mode==='advanced'?['quotations','delivery_notes','products','team','statements','reports','business_documents']:(user.enabled_modules??[])).includes(id)
}
