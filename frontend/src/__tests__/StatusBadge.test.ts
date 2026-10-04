import { mount } from '@vue/test-utils'
import { it,expect } from 'vitest'
import StatusBadge from '../components/StatusBadge.vue'
it('renders a readable partial-payment status',()=>{
 const wrapper=mount(StatusBadge,{props:{status:'partially_paid'}})
 expect(wrapper.text()).toBe('partially paid');expect(wrapper.classes()).toContain('partially_paid')
})