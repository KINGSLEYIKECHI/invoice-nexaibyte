<script setup lang="ts">
import ProductBrand from './ProductBrand.vue'
import { LayoutDashboard,FileText,Users,Settings,LogOut,ArrowUpRight,Layers,UserRound } from 'lucide-vue-next'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'
const auth=useAuth(),router=useRouter()
async function logout(){await auth.logout();router.push('/login')}
</script>
<template><aside class="sidebar"><RouterLink to="/" class="brand"><ProductBrand/></RouterLink><div class="nav-caption">WORKSPACE</div><nav><RouterLink to="/"><LayoutDashboard :size="19"/>Overview</RouterLink><RouterLink to="/invoices"><FileText :size="19"/>Invoices</RouterLink><RouterLink to="/clients"><Users :size="19"/>Clients</RouterLink><RouterLink v-if="auth.manager" to="/team"><UserRound :size="19"/>Team</RouterLink><RouterLink v-if="auth.manager" to="/settings"><Settings :size="19"/>Settings</RouterLink><RouterLink v-if="auth.user?.is_platform_admin" to="/admin"><Settings :size="19"/>Platform admin</RouterLink></nav><div class="sidebar-bottom"><div class="sidebar-tip"><span class="eyebrow">LESS ADMIN. MORE BUSINESS.</span><p>Make your next invoice<br>your easiest one.</p><RouterLink to="/invoices/new">Create an invoice <ArrowUpRight :size="16"/></RouterLink></div><div class="profile"><span class="avatar">{{ auth.user?.name.split(' ').map(x=>x[0]).join('') }}</span><div><strong>{{ auth.user?.name }}</strong><small>{{ auth.user?.role }}</small></div><button class="icon-button" aria-label="Sign out" @click="logout"><LogOut :size="17"/></button></div></div></aside></template>