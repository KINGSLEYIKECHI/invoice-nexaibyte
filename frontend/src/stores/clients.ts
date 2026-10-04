import { defineStore } from 'pinia'
import { api } from '../api/client'
import type { Client,Page } from '../types'
export const useClients=defineStore('clients',{state:()=>({page:null as Page<Client>|null}),actions:{async load(query=''){this.page=await api<Page<Client>>('/clients'+query)}}})