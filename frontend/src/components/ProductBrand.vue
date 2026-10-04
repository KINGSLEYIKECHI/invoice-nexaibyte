<script setup lang="ts">
import { Layers } from 'lucide-vue-next'
import { usePlatform } from '../stores/platform'
const platform=usePlatform()
</script>
<template><span class="brand"><img v-if="platform.settings.logo_url" :src="platform.settings.logo_url" alt="" class="product-logo"><span v-else class="brand-icon"><Layers :size="23"/></span><span>{{ platform.settings.product_name }}</span></span></template>
