import { mount,flushPromises } from '@vue/test-utils'
import { vi,describe,it,expect,beforeEach,afterEach } from 'vitest'
import AdPlacement from './AdPlacement.vue'
import { api } from '../api/client'
vi.mock('../api/client',()=>({api:vi.fn()}))
const enabled={enabled:true,approved:true,consent_ready:true,publisher_id:'ca-pub-1234567890123456',slot_id:'1234'}
beforeEach(()=>{vi.mocked(api).mockReset();document.querySelectorAll('script[data-invoice-ads]').forEach(x=>x.remove())})
afterEach(()=>{delete document.documentElement.dataset.invoiceAdsActive;document.querySelectorAll('script[data-invoice-ads]').forEach(x=>x.remove())})
describe('public advertising placement',()=>{
 it('does not load any ad script while disabled or before consent',async()=>{
  vi.mocked(api).mockResolvedValue({...enabled,enabled:false});const wrapper=mount(AdPlacement,{props:{publicPage:true}});await flushPromises();window.dispatchEvent(new CustomEvent('invoice-ad-consent',{detail:{advertising:true}}));await flushPromises();expect(document.querySelector('script[data-invoice-ads]')).toBeNull();wrapper.unmount()
 })
 it('loads a single fixed Google script only after consent on an enabled public page',async()=>{
  vi.mocked(api).mockResolvedValue(enabled);const wrapper=mount(AdPlacement,{props:{publicPage:true}});await flushPromises();expect(document.querySelector('script[data-invoice-ads]')).toBeNull();window.dispatchEvent(new CustomEvent('invoice-ad-consent',{detail:{advertising:true}}));await flushPromises();expect(document.querySelectorAll('script[data-invoice-ads]')).toHaveLength(1);expect(document.documentElement.dataset.invoiceAdsActive).toBe('true');expect(document.querySelector<HTMLScriptElement>('script[data-invoice-ads]')?.src).toBe('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='+enabled.publisher_id);window.dispatchEvent(new CustomEvent('invoice-ad-consent',{detail:{advertising:true}}));await flushPromises();expect(document.querySelectorAll('script[data-invoice-ads]')).toHaveLength(1);wrapper.unmount()
 })
 it('does not request settings or ads for private pages',async()=>{
  const wrapper=mount(AdPlacement,{props:{publicPage:false}});await flushPromises();window.dispatchEvent(new CustomEvent('invoice-ad-consent',{detail:{advertising:true}}));await flushPromises();expect(api).not.toHaveBeenCalled();expect(document.querySelector('script[data-invoice-ads]')).toBeNull();wrapper.unmount()
 })
 it('does not attach a consent listener if the component unmounts before settings return',async()=>{
  let resolve!:(value:unknown)=>void;vi.mocked(api).mockImplementation(()=>new Promise(r=>{resolve=r}) as ReturnType<typeof api>);const wrapper=mount(AdPlacement,{props:{publicPage:true}});wrapper.unmount();resolve(enabled);await flushPromises();window.dispatchEvent(new CustomEvent('invoice-ad-consent',{detail:{advertising:true}}));await flushPromises();expect(document.querySelector('script[data-invoice-ads]')).toBeNull()
 })
})
