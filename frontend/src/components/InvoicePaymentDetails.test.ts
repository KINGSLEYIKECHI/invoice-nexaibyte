import { mount } from '@vue/test-utils'
import { it,expect } from 'vitest'
import InvoicePaymentDetails from './InvoicePaymentDetails.vue'
it('shows invoice-enabled bank details and instructions, excluding quotation-only accounts',()=>{
 const bank={bank_name:'Receiving bank',account_name:'Owner',account_number:'0012345',routing_code:'CODE',details:'Use invoice reference',show_on_invoice:true,show_on_quotation:false}
 const w=mount(InvoicePaymentDetails,{props:{business:{payment_instructions:'Pay by transfer',bank_accounts:[bank,{...bank,bank_name:'Quotation only',show_on_invoice:false}]} as any}})
 expect(w.text()).toContain('0012345');expect(w.text()).toContain('Pay by transfer');expect(w.text()).not.toContain('Quotation only')
})
