import { beforeEach,afterEach,it,expect } from 'vitest'
import { createPinia,setActivePinia } from 'pinia'
import { usePlatform } from './platform'
beforeEach(()=>{setActivePinia(createPinia())})
afterEach(()=>{document.querySelector('link[data-platform-favicon]')?.remove()})
it('replaces the browser favicon after branding changes without duplicate links',()=>{
 const store=usePlatform();store.settings.favicon_url='https://cdn.example.test/icon-1.png';store.apply()
 expect(document.querySelector<HTMLLinkElement>('link[data-platform-favicon]')?.href).toBe('https://cdn.example.test/icon-1.png')
 store.settings.favicon_url='https://cdn.example.test/icon-2.png';store.apply()
 expect(document.querySelectorAll('link[data-platform-favicon]')).toHaveLength(1)
 expect(document.querySelector<HTMLLinkElement>('link[data-platform-favicon]')?.href).toBe('https://cdn.example.test/icon-2.png')
 store.settings.favicon_url='';store.apply();expect(document.querySelector('link[data-platform-favicon]')).toBeNull()
})
