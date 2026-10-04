<script setup lang="ts">
import { Plus,Trash2 } from 'lucide-vue-next'
import type { Item } from '../types'
import { formatNaira,toKobo } from '../utils/formatNaira'
const items=defineModel<Item[]>({required:true})
function add(){items.value.push({description:'',quantity:1,unit_price_kobo:0})}
function price(event:Event,item:Item){item.unit_price_kobo=toKobo(Number((event.target as HTMLInputElement).value))}
</script>
<template><div class="line-items-editor"><div class="table-wrap"><table class="line-editor"><thead><tr><th>Description</th><th>Quantity</th><th>Rate (₦)</th><th>Amount</th><th></th></tr></thead><tbody><tr v-for="(item,index) in items" :key="index"><td><input v-model="item.description" required maxlength="255" placeholder="Service or product description"></td><td><input v-model.number="item.quantity" type="number" min=".01" max="100000" step=".01" required></td><td><input :value="item.unit_price_kobo/100" @input="price($event,item)" type="number" min="0" step=".01" required></td><td class="money">{{ formatNaira(Math.round(item.quantity*item.unit_price_kobo)) }}</td><td><button type="button" class="icon-button danger-text" :disabled="items.length===1" @click="items.splice(index,1)" aria-label="Remove line"><Trash2 :size="17"/></button></td></tr></tbody></table></div><button type="button" class="button ghost" @click="add"><Plus :size="16"/>Add line item</button></div></template>