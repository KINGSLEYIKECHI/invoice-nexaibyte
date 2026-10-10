import { mount,flushPromises } from '@vue/test-utils'
import { it,expect,vi } from 'vitest'
import DocumentForm from './DocumentForm.vue'
vi.mock('../../api/client',()=>({api:vi.fn().mockResolvedValue({data:[],last_page:1}),json:vi.fn()}))
vi.mock('../../stores/auth',()=>({useAuth:()=>({user:{business:{currency:'USD',default_tax_percent:0}}})}))
vi.mock('vue-router',()=>({useRoute:()=>({meta:{documentKind:'quotations'},params:{}}),useRouter:()=>({push:vi.fn()})}))
it('updates quotation totals immediately after price quantity tax and discount changes',async()=>{
 const w=mount(DocumentForm,{global:{stubs:{RouterLink:true}}});await flushPromises()
 await w.get('input[aria-label="Unit price"]').setValue('100')
 await w.get('input[min=".01"]').setValue('2')
 expect(w.text()).not.toContain('PO reference');expect(w.get('.grand-total').text()).toContain('200.00')
 const tax=w.findAll('input').find(i=>i.element.parentElement?.textContent?.startsWith('Tax (%)'))!;await tax.setValue('10')
 expect(w.get('.grand-total').text()).toContain('220.00')
 const discount=w.findAll('input').find(i=>i.element.parentElement?.textContent?.startsWith('Discount ('))!;await discount.setValue('20')
 expect(w.get('.grand-total').text()).toContain('198.00')
 await tax.setValue('0');expect(w.get('.grand-total').text()).toContain('180.00')
})
