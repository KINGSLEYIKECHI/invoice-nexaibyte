import { defineStore } from 'pinia'
import { api } from '../api/client'
import type { Dashboard } from '../types'
export const useDashboard=defineStore('dashboard',{state:()=>({data:null as Dashboard|null}),actions:{async load(){this.data=await api<Dashboard>('/dashboard')}}})