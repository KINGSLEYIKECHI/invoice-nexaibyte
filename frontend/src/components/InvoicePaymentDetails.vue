<script setup lang="ts">
import { computed } from 'vue'
import type { Business } from '../types'
const props=defineProps<{business?:Business}>()
const accounts=computed(()=>(props.business?.bank_accounts||[]).filter(a=>a.show_on_invoice&&[a.bank_name,a.account_name,a.account_number,a.routing_code,a.details].some(Boolean)))
</script>
<template><h3>Payment details</h3><section v-for="(account,index) in accounts" :key="index" class="spaced"><h3 v-if="account.bank_name">{{ account.bank_name }}</h3><p v-if="account.account_name">Account holder: {{ account.account_name }}</p><p v-if="account.account_number">Account number / IBAN: {{ account.account_number }}</p><p v-if="account.routing_code">SWIFT / routing: {{ account.routing_code }}</p><p v-if="account.details">{{ account.details }}</p></section><p v-if="business?.payment_instructions">{{ business.payment_instructions }}</p><p v-else-if="!accounts.length">Contact the business for payment instructions.</p></template>
