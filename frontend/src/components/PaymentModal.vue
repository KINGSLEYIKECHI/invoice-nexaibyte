<script setup lang="ts">
import { ref } from 'vue'
import { X } from 'lucide-vue-next'
import { api,json } from '../api/client'
import { formatNaira,toKobo } from '../utils/formatNaira'
import { dateInput } from '../utils/formatDate'
import type { Invoice } from '../types'
const props=defineProps<{invoice:Invoice}>(),emit=defineEmits(['close','saved'])
const amount=ref(props.invoice.balance_kobo/100),method=ref('bank_transfer'),reference=ref(''),paid_on=ref(dateInput()),notes=ref(''),error=ref(''),busy=ref(false)
async function save(){busy.value=true;try{await api('/invoices/'+props.invoice.id+'/payments',json('POST',{amount_kobo:toKobo(amount.value),method:method.value,reference:reference.value,paid_on:paid_on.value,notes:notes.value}));emit('saved')}catch(e){error.value=(e as Error).message}finally{busy.value=false}}
</script>
<template><div class="modal-overlay" @click.self="emit('close')"><section class="modal" role="dialog" aria-modal="true" aria-labelledby="payment-title"><header><h2 id="payment-title">Record payment</h2><button class="icon-button" @click="emit('close')" aria-label="Close"><X/></button></header><p class="muted">{{ invoice.number }} · Outstanding {{ formatNaira(invoice.balance_kobo) }}</p><form @submit.prevent="save"><div class="form-grid"><label>Amount (₦)<input v-model.number="amount" type="number" required min=".01" :max="invoice.balance_kobo/100" step=".01"></label><label>Payment date<input v-model="paid_on" type="date" required :max="dateInput()"></label><label>Method<select v-model="method"><option value="bank_transfer">Bank transfer</option><option value="cash">Cash</option><option value="card">Card</option><option value="pos">POS</option><option value="other">Other</option></select></label><label>Reference<input v-model="reference" placeholder="Optional transfer reference"></label></div><label>Notes<textarea v-model="notes" rows="2"/></label><p v-if="error" class="error" role="alert">{{ error }}</p><footer><button type="button" class="button secondary" @click="emit('close')">Cancel</button><button class="button primary" :disabled="busy">{{ busy?'Saving…':'Record payment' }}</button></footer></form></section></div></template>