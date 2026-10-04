import { mount,flushPromises } from '@vue/test-utils'
import { vi,it,expect,beforeEach } from 'vitest'
import PlatformAdmin from './PlatformAdmin.vue'
import { api } from '../../api/client'
vi.mock('../../api/client',()=>({api:vi.fn(),json:(method:string,body:unknown)=>({method,body:JSON.stringify(body)})}))
const config={mail_driver:'log',smtp_host:'smtp.hostinger.com',smtp_port:587,smtp_security:'tls',smtp_username:'billing@example.test',mail_from_address:'billing@example.test',mail_from_name:'Example',media_driver:'local',cloudinary_cloud_name:'',cloudinary_folder:'invoice-saas',whatsapp_enabled:false,whatsapp_phone_id:'',whatsapp_version:'v23.0',payment_owner_email:true,payment_client_email:true,has_smtp_password:true,has_cloudinary_api_key:false,has_cloudinary_api_secret:false,has_whatsapp_token:false}
beforeEach(()=>{vi.mocked(api).mockReset();vi.mocked(api).mockImplementation(async(path)=>{
 if(path==='/platform/overview')return {users:4,businesses:2,active_last_5_minutes:1,timezone:'Africa/Lagos',daily:[]}
 if(path==='/platform/integrations')return config
 if(path==='/advertising')return {enabled:false,approved:false,consent_ready:false,publisher_id:'',slot_id:''}
 return {data:[],current_page:1,last_page:1,total:0}
})})
it('shows a clear favicon upload entry and keeps configured secrets out of input values',async()=>{
 const wrapper=mount(PlatformAdmin,{global:{stubs:{RouterLink:{template:'<a><slot/></a>'}}}});await flushPromises();expect(wrapper.text()).toContain('Branding & favicon upload');await wrapper.findAll('nav button').find(b=>b.text()==='Integrations')!.trigger('click');expect(wrapper.findAll('input[type=password]')).toHaveLength(4);expect(wrapper.findAll('input[type=password]').every(i=>(i.element as HTMLInputElement).value==='')).toBe(true);expect(wrapper.text()).toContain('A secret is configured.');wrapper.unmount()
})
it('saves blank secret fields without returning configured secret values and excludes presence flags',async()=>{
 const wrapper=mount(PlatformAdmin,{global:{stubs:{RouterLink:{template:'<a><slot/></a>'}}}});await flushPromises();await wrapper.findAll('nav button').find(b=>b.text()==='Integrations')!.trigger('click');await wrapper.find('form').trigger('submit');await flushPromises();const call=vi.mocked(api).mock.calls.find(([path,options])=>path==='/platform/integrations'&&options?.method==='PUT');expect(call).toBeDefined();const payload=JSON.parse(call![1]!.body as string);expect(payload.smtp_password).toBe('');expect(payload.clear_smtp_password).toBe(false);expect(payload).not.toHaveProperty('has_smtp_password');wrapper.unmount()
})
