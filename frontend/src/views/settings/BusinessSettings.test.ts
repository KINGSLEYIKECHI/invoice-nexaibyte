import { mount,flushPromises } from '@vue/test-utils'
import { it,expect,vi } from 'vitest'
import BusinessSettings from './BusinessSettings.vue'
const {api}=vi.hoisted(()=>({api:vi.fn()}))
vi.mock('../../api/client',()=>({api,json:(_method:string,body:unknown)=>body}))
vi.mock('../../stores/auth',()=>({useAuth:()=>({user:{role:'owner',business:{}},manager:true})}))
vi.mock('vue-router',()=>({useRouter:()=>({push:vi.fn()})}))
it('offers global default currencies and manual bank account controls',async()=>{
 api.mockResolvedValue({name:'Business',currency:'NGN',bank_accounts:null,invoice_prefix:'INV',default_tax_percent:0,brand_color:'#123456'})
 const w=mount(BusinessSettings,{global:{stubs:{RouterLink:true}}});await flushPromises()
 expect(w.findAll('option').some(o=>o.attributes('value')==='USD')).toBe(true)
 await w.get('select').setValue('USD')
 const add=w.findAll('button').find(b=>b.text()==='Add bank account')!;await add.trigger('click')
 const account=w.findAll('input').find(i=>i.element.parentElement?.textContent?.includes('Account number / IBAN'))!;await account.setValue('0012345678')
 await w.get('form').trigger('submit');await flushPromises()
 expect(api.mock.calls.at(-1)?.[1]).toMatchObject({currency:'USD',bank_accounts:[{account_number:'0012345678',show_on_invoice:true,show_on_quotation:true}]})
})
