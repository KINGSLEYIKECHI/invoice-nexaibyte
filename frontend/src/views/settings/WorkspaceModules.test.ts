import { mount,flushPromises } from '@vue/test-utils'
import { beforeEach,it,expect,vi } from 'vitest'
import WorkspaceModules from './WorkspaceModules.vue'
const {api,user}=vi.hoisted(()=>({api:vi.fn(),user:{workspace_mode:'advanced',enabled_modules:['quotations','products']}}))
vi.mock('../../api/client',()=>({api,json:(_method:string,body:unknown)=>body}))
vi.mock('../../stores/auth',()=>({useAuth:()=>({user})}))
const prefs={mode:'advanced',enabled_modules:['quotations','products'],modules:[{id:'quotations',name:'Quotations',description:'Offers',route:'/quotations'},{id:'products',name:'Products & services',description:'Catalogue',route:'/products'}],presets:{simple:['quotations'],advanced:['quotations','products']}}
beforeEach(()=>{api.mockReset();api.mockImplementation(async(_path,body)=>body?{...prefs,...body}:structuredClone(prefs))})
it('applies a preset and saves user-specific choices',async()=>{
 const w=mount(WorkspaceModules);await flushPromises();await w.findAll('button').find(b=>b.text()==='Simple mode')!.trigger('click');await w.get('form').trigger('submit');await flushPromises()
 expect(api.mock.calls.at(-1)?.[1]).toEqual({mode:'simple',enabled_modules:['quotations']});expect(user.workspace_mode).toBe('simple')
 await w.findAll('input[type=checkbox]')[1]!.setValue(true);expect(w.text()).toContain('Custom selection');await w.get('form').trigger('submit');await flushPromises()
 expect(api.mock.calls.at(-1)?.[1]).toEqual({mode:'custom',enabled_modules:['quotations','products']});expect(w.text()).toContain('Existing records and team permissions are unchanged')
})
