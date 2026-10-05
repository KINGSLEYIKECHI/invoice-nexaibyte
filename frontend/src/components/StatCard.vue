<script setup lang="ts">
import { ArrowUpRight } from 'lucide-vue-next'
import { formatMoney } from '../utils/money'
defineProps<{label:string;value:number;note:string;accent?:string;currency?:string;digits?:number}>()
</script>
<template><div :class="['stat-card',accent]"><div class="stat-label">{{ label }}<ArrowUpRight :size="17"/></div><strong>{{ formatMoney(value,currency,digits) }}</strong><small>{{ note }}</small></div></template>