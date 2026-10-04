import { defineStore } from 'pinia'
import { api,json } from '../api/client'
export const defaults={product_name:'Ledger',company_name:'Nexaibyte LTD',company_url:'https://nexaibyte.com',support_email:'hello@nexaibyte.com',primary_color:'#0C7062',background_color:'#123D32',accent_color:'#B9CF9C',logo_url:'',favicon_url:'',tagline:'A little order. A lot of possibility.',hero_title:'Good business starts with clear numbers.',hero_description:'Send beautiful invoices, keep payments in view, and get back to the work you love.',login_title:'A fresh view of your business.',login_description:'Sign in to your workspace to get started.',register_title:'Make room for growth.',register_description:'Create your free workspace. Keep every invoice in order.',announcement:''}
export type PlatformSettings=typeof defaults
export const usePlatform=defineStore('platform',{
 state:()=>({settings:{...defaults},loaded:false,loadError:''}),
 actions:{
  apply(){let icon=document.querySelector<HTMLLinkElement>('link[data-platform-favicon]');if(this.settings.favicon_url){if(!icon){icon=document.createElement('link');icon.rel='icon';icon.type='image/png';icon.dataset.platformFavicon='true';document.head.appendChild(icon)}icon.href=this.settings.favicon_url}else{icon?.remove()}document.title=this.settings.product_name; const root=document.documentElement; root.style.setProperty('--green',this.settings.primary_color);root.style.setProperty('--brand-background',this.settings.background_color);root.style.setProperty('--brand-accent',this.settings.accent_color)},
  async load(){try{this.settings=await api<PlatformSettings>('/platform/settings');this.loadError=''}catch{this.loadError='Unable to load platform settings. Try again.'}this.loaded=true;this.apply()},
  async save(settings:PlatformSettings){this.settings=await api<PlatformSettings>('/platform/settings',json('PUT',settings));this.apply()}
 }
})
