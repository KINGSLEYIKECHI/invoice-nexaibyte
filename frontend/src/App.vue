<script setup lang="ts">
import { onMounted } from 'vue'
import { usePlatform } from './stores/platform'
import Navbar from './components/Navbar.vue'
import { useAuth } from './stores/auth'
import { useRouter } from 'vue-router'
const platform=usePlatform()
onMounted(()=>platform.load())
const auth=useAuth(),router=useRouter()
window.addEventListener('session-expired',()=>{auth.user=null;router.push('/login')})
</script>
<template><div v-if="platform.settings.announcement" class="platform-announcement" role="status">{{ platform.settings.announcement }}</div><div v-if="auth.user&&!$route.meta.public" class="app-shell"><Navbar/><main class="workspace"><header class="topbar"><span class="workspace-label"><span class="online-dot"></span>{{ auth.user.business.name }} <span class="chip">Workspace</span></span><span class="topbar-date">Your business, in balance.</span></header><div class="page"><RouterView :key="$route.fullPath"/></div></main></div><RouterView v-else/><footer class="platform-credit"><a :href="platform.settings.company_url" target="_blank" rel="noopener noreferrer">By {{ platform.settings.company_name }}</a> Â· <a :href="'mailto:'+platform.settings.support_email">Support</a> · <RouterLink to="/about">About</RouterLink> · <RouterLink to="/privacy">Privacy</RouterLink></footer></template>