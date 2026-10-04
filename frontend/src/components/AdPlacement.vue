<script setup lang="ts">
import { onMounted,onUnmounted,nextTick,ref } from 'vue'
import { api } from '../api/client'
const props=defineProps<{publicPage:boolean}>()
const settings=ref({enabled:false,approved:false,consent_ready:false,publisher_id:'',slot_id:''}),visible=ref(false)
let requested=false,alive=true
// Only a verified CMP integration should emit this event after obtaining advertising consent.
async function consent(event:Event){
 const allowed=(event as CustomEvent<{advertising:boolean}>).detail?.advertising===true
 const s=settings.value
 if(!allowed){if(requested)window.location.reload();visible.value=false;return}
 if(!alive||!props.publicPage||!s.enabled||!s.approved||!s.consent_ready||requested)return
 requested=true;visible.value=true;document.documentElement.dataset.invoiceAdsActive='true';await nextTick();if(!alive||!visible.value)return
 let script=document.querySelector<HTMLScriptElement>('script[data-invoice-ads]')
 const push=()=>{const w=window as unknown as {adsbygoogle:unknown[]};(w.adsbygoogle=w.adsbygoogle||[]).push({})}
 if(!script){script=document.createElement('script');script.async=true;script.crossOrigin='anonymous';script.dataset.invoiceAds='true';script.src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='+s.publisher_id;script.addEventListener('load',push,{once:true});document.head.append(script)}else push()
}
onMounted(async()=>{if(!props.publicPage)return;try{settings.value=await api('/advertising');if(!alive)return;window.addEventListener('invoice-ad-consent',consent);window.dispatchEvent(new Event('invoice-ad-consent-ready'))}catch{/* No ads on unavailable settings. */}})
onUnmounted(()=>{alive=false;window.removeEventListener('invoice-ad-consent',consent)})
</script>
<template><aside v-if="visible" class="ad-placement" aria-label="Advertisement"><small>Advertisement</small><ins class="adsbygoogle" style="display:block" :data-ad-client="settings.publisher_id" :data-ad-slot="settings.slot_id" data-ad-format="auto" data-full-width-responsive="true"></ins></aside></template>
