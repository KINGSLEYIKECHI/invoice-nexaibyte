import { defineStore } from 'pinia'
import { api } from '../api/client'
import type { Invoice,Page } from '../types'
export const useInvoices=defineStore('invoices',{state:()=>({page:null as Page<Invoice>|null}),actions:{async load(query=''){this.page=await api<Page<Invoice>>('/invoices'+query)}}})