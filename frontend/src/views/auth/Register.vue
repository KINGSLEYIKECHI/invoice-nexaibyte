<script setup lang="ts">
import ProductBrand from '../../components/ProductBrand.vue'
import { usePlatform } from '../../stores/platform'
import { ref,reactive } from 'vue'
import { ArrowRight } from 'lucide-vue-next'
import { useAuth } from '../../stores/auth'
import { useRouter } from 'vue-router'
const platform=usePlatform()
const form=reactive({business_name:'',name:'',email:'',password:'',password_confirmation:''}),error=ref(''),busy=ref(false),auth=useAuth(),router=useRouter()
async function register(){busy.value=true;try{await auth.login(form,true);router.push('/')}catch(e){error.value=(e as Error).message}finally{busy.value=false}}
</script>
<template><div class="register-page"><RouterLink to="/login" class="brand"><ProductBrand/></RouterLink><section class="panel register-card"><span class="eyebrow">YOUR BUSINESS STARTS HERE</span><h1>{{ platform.settings.register_title }}</h1><p class="muted">{{ platform.settings.register_description }}</p><form @submit.prevent="register"><label>Business name<input v-model="form.business_name" required maxlength="150" placeholder="Your business name"></label><div class="form-grid"><label>Your name<input v-model="form.name" required autocomplete="name"></label><label>Email address<input v-model="form.email" type="email" required autocomplete="email"></label><label>Password<input v-model="form.password" type="password" minlength="8" required autocomplete="new-password"></label><label>Confirm password<input v-model="form.password_confirmation" type="password" minlength="8" required autocomplete="new-password"></label></div><p v-if="error" class="error" role="alert">{{ error }}</p><button class="button primary wide" :disabled="busy">{{ busy?'Creating workspace…':'Create workspace' }}<ArrowRight :size="16"/></button></form><p class="auth-switch">Already have an account? <RouterLink to="/login">Sign in</RouterLink></p></section></div></template>