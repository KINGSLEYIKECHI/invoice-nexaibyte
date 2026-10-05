<script setup lang="ts">
import { ref,onMounted,computed } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../../api/client'
import { formatMoney } from '../../utils/money'
import { formatDate } from '../../utils/formatDate'
import type { CommercialDocument,Page } from '../../types'
const route=useRoute(),kind=computed(()=>String(route.meta.documentKind)),title=computed(()=>kind.value==='quotations'?'Quotations':'Delivery notes'),page=ref<Page<CommercialDocument>>(),error=ref(''),search=ref('')
async function load(n=1){try{page.value=await api('/documents/'+kind.value+'?page='+n+'&search='+encodeURIComponent(search.value))}catch(e){error.value=(e as Error).message}}
onMounted(()=>load())
</script>
<template><div class="page-heading"><div><h1>{{ title }}</h1><p class="muted">{{ kind==='quotations'?'Prepare an offer and convert accepted quotations to invoices.':'Record quantities delivered and acknowledgement of receipt.' }}</p></div><RouterLink :to="'/'+kind+'/new'" class="button primary">Create {{ kind==='quotations'?'quotation':'delivery note' }}</RouterLink></div><p v-if="error" class="error">{{ error }}</p><section class="panel admin-panel"><form class="admin-filter" @submit.prevent="load()"><label>Document number<input v-model="search" maxlength="100"></label><button class="button secondary">Search</button></form><div class="table-scroll"><table><thead><tr><th>Number</th><th>Client</th><th>Date</th><th v-if="kind==='quotations'">Total</th><th>Status</th></tr></thead><tbody><tr v-for="d in page?.data" :key="d.id"><td><RouterLink :to="'/'+kind+'/'+d.id">{{ d.number }}</RouterLink></td><td>{{ d.client.name }}</td><td>{{ formatDate(d.issue_date) }}</td><td v-if="kind==='quotations'">{{ formatMoney(d.total_kobo,d.currency,d.currency_minor_units) }}</td><td>{{ d.status }}</td></tr><tr v-if="!page?.data.length"><td colspan="5">No documents yet.</td></tr></tbody></table></div><div v-if="page" class="admin-pagination"><button class="button secondary" :disabled="page.current_page===1" @click="load(page!.current_page-1)">Previous</button><span>{{ page.total }} documents · Page {{ page.current_page }}</span><button class="button secondary" :disabled="page.current_page===page.last_page" @click="load(page!.current_page+1)">Next</button></div></section></template>
