import { defineConfig } from '@playwright/test'
export default defineConfig({testDir:'./e2e',timeout:120000,expect:{timeout:20000},fullyParallel:false,workers:1,retries:process.env.CI?1:0,reporter:[['list'],['html',{open:'never'}]],use:{channel:process.env.E2E_BROWSER_CHANNEL,baseURL:process.env.E2E_URL||'http://127.0.0.1:8080',trace:'retain-on-failure',screenshot:'only-on-failure'}})

