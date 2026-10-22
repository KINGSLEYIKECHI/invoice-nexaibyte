import { it,expect } from 'vitest'
import { moduleEnabled } from './workspace'
import type { User } from '../types'
it('preserves existing users and supports simple and custom module choices without granting team access',()=>{
 const old={role:'owner'} as User;expect(moduleEnabled(old,'products')).toBe(true)
 expect(moduleEnabled({...old,workspace_mode:'simple'},'products')).toBe(false)
 expect(moduleEnabled({...old,workspace_mode:'simple'},'quotations')).toBe(true)
 expect(moduleEnabled({...old,workspace_mode:'custom',enabled_modules:['statements']},'products')).toBe(false)
 expect(moduleEnabled({...old,workspace_mode:'custom',enabled_modules:['statements']},'statements')).toBe(true)
 expect(moduleEnabled({...old,role:'staff',enabled_modules:['team']},'team')).toBe(false)
 expect(moduleEnabled(null,'products')).toBe(false)
})

it('keeps company documents available only to managers regardless of selected modules',()=>{const manager={role:'owner',workspace_mode:'advanced'} as User;expect(moduleEnabled(manager,'business_documents')).toBe(true);expect(moduleEnabled({...manager,role:'staff'},'business_documents')).toBe(false);expect(moduleEnabled({...manager,workspace_mode:'simple'},'business_documents')).toBe(false);expect(moduleEnabled({...manager,workspace_mode:'custom',enabled_modules:['business_documents']},'business_documents')).toBe(true)})
