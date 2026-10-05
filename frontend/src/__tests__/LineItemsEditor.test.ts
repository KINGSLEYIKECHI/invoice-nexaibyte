import { mount } from '@vue/test-utils'
import { it,expect,vi } from 'vitest'
import LineItemsEditor from '../components/LineItemsEditor.vue'
vi.mock('../api/client',()=>({api:vi.fn().mockResolvedValue({data:[]})}))
it('adds a line and converts input naira to integer kobo',async()=>{
 const items=[{description:'Installation',quantity:1,unit_price_kobo:10000}]
 const wrapper=mount(LineItemsEditor,{props:{modelValue:items}})
 await wrapper.get('input[aria-label="Unit price"]').setValue('150.25')
 expect(items[0].unit_price_kobo).toBe(15025)
 await wrapper.findAll('button').at(-1)!.trigger('click')
 expect(items).toHaveLength(2)
})