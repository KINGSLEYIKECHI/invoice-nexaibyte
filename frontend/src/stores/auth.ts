import { defineStore } from 'pinia'
import { api,json } from '../api/client'
import type { User } from '../types'
export const useAuth=defineStore('auth',{
  state:()=>({user:null as User|null,loaded:false}),
  getters:{manager:s=>s.user?.role==='owner'||s.user?.role==='admin'},
  actions:{
    async restore(){if(localStorage.getItem('invoice_token')){try{this.user=await api<User>('/me')}catch{localStorage.removeItem('invoice_token')}}this.loaded=true},
    async login(body:unknown,register=false){const result=await api<{token:string;user:User}>('/auth/'+(register?'register':'login'),json('POST',body));localStorage.setItem('invoice_token',result.token);this.user=result.user;this.loaded=true},
    async logout(){try{await api('/auth/logout',{method:'POST'})}finally{localStorage.removeItem('invoice_token');this.user=null}}
  }
})