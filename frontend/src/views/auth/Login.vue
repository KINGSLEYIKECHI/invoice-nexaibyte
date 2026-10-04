<script setup lang="ts">
import ProductBrand from '../../components/ProductBrand.vue'
import { usePlatform } from '../../stores/platform'
import { ref } from 'vue'
import { ArrowRight,Check } from 'lucide-vue-next'
import { useAuth } from '../../stores/auth'
import { useRouter } from 'vue-router'
const platform=usePlatform()
const email=ref(''),password=ref(''),error=ref(''),busy=ref(false),auth=useAuth(),router=useRouter()
async function login(){busy.value=true;error.value='';try{await auth.login({email:email.value,password:password.value});router.push('/')}catch(e){error.value=(e as Error).message}finally{busy.value=false}}
</script>
<template><div class="auth-layout"><section class="auth-story"><ProductBrand/><div><span class="eyebrow">{{ platform.settings.tagline }}</span><h1>{{ platform.settings.hero_title }}</h1><p>{{ platform.settings.hero_description }}</p><div class="auth-benefits"><span><Check :size="18"/>Invoices that mean business</span><span><Check :size="18"/>Your whole team, one workspace</span><span><Check :size="18"/>Made for Nigerian businesses</span></div></div><small>By {{ platform.settings.company_name }}</small></section><section class="auth-form"><div><span class="eyebrow">WELCOME BACK</span><h2>{{ platform.settings.login_title }}</h2><p class="muted">{{ platform.settings.login_description }}</p><form @submit.prevent="login"><label>Email address<input v-model="email" type="email" autocomplete="username" required></label><label>Password<input v-model="password" type="password" autocomplete="current-password" required></label><p v-if="error" class="error" role="alert">{{ error }}</p><button class="button primary wide" :disabled="busy">{{ busy?'Signing in…':'Sign in to your workspace' }}<ArrowRight :size="17"/></button></form><p class="auth-switch">New here? <RouterLink to="/register">Create your business account</RouterLink></p></div></section></div></template>