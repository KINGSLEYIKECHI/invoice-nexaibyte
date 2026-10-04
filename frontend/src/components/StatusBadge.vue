<script setup lang="ts">defineProps<{status:string}>()</script>
<template><span :class="['status',status]"><span></span>{{ status.replace('_',' ') }}</span></template>