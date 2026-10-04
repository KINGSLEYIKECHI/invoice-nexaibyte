<script setup lang="ts">
import { ArrowUpRight } from 'lucide-vue-next'
import { formatNaira } from '../utils/formatNaira'
defineProps<{label:string;value:number;note:string;accent?:string}>()
</script>
<template><div :class="['stat-card',accent]"><div class="stat-label">{{ label }}<ArrowUpRight :size="17"/></div><strong>{{ formatNaira(value) }}</strong><small>{{ note }}</small></div></template>