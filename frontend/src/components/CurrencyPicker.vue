<script setup lang="ts">
import { computed,ref,nextTick } from 'vue'
import { currencyList } from '../utils/money'
const currency=defineModel<string>({required:true}),search=ref('')
const options=computed(()=>currencyList.filter(c=>c.code===currency.value||[c.code,c.name,...c.countries].join(' ').toLowerCase().includes(search.value.toLowerCase())))
async function choose(event:Event){const input=event.target as HTMLSelectElement;currency.value=input.value;await nextTick();input.value=currency.value}
</script>
<template><div><input v-model="search" aria-label="Search currencies" placeholder="Search country, currency name or code"><select :value="currency" @change="choose" aria-label="Currency" required><option v-for="c in options" :key="c.code" :value="c.code">{{ c.code }} · {{ c.name }}</option></select><small>{{ currencyList.find(c=>c.code===currency)?.countries.join(', ') }}</small></div></template>
