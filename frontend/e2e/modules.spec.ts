import { test,expect } from '@playwright/test'
test('module presets custom selection persistence and reporting work in the compiled UI',async({page})=>{
 const catalog=[['quotations','Quotations'],['delivery_notes','Delivery notes'],['products','Products & services'],['team','Team'],['statements','Client statements'],['reports','Due & overdue reports']].map(([id,name])=>({id,name,description:'Choose whether to show this tool in your workspace.',route:'/modules'}))
 const presets={simple:['quotations'],advanced:catalog.map(m=>m.id!)}
 let mode='advanced',enabled=[...presets.advanced]
 const user=()=>({id:1,name:'Module Test',email:'module@example.test',role:'owner',is_platform_admin:false,workspace_mode:mode,enabled_modules:enabled,business:{id:1,name:'Module workspace',currency:'USD'}})
 await page.route(/fonts\.(googleapis|gstatic)\.com/,route=>route.abort())
 await page.addInitScript(()=>localStorage.setItem('invoice_token','test-only-mocked-token'))
 await page.route('**/backend/api/**',async route=>{const url=new URL(route.request().url()),path=url.pathname.replace('/backend/api','');let body:unknown={}
 if(path==='/me')body=user()
 else if(path==='/workspace/preferences'){if(route.request().method()==='PUT'){const data=route.request().postDataJSON();mode=data.mode;enabled=[...data.enabled_modules]}body={mode,enabled_modules:enabled,modules:catalog,presets}}
 else if(path==='/clients')body={data:[{id:1,name:'Example client'}],last_page:1}
 else if(path==='/reports/statement')body={kind:'statement',from:'2026-01-01',to:'2026-01-31',client:{id:1,name:'Example client'},totals:[{currency:'USD',minor_units:2,opening_minor:10000,invoiced_minor:0,paid_minor:2000,closing_minor:8000}],rows:[]}
 else if(path==='/platform/settings')body={product_name:'ledger.',company_name:'Example',company_url:'https://example.test',support_email:'support@example.test',primary_color:'#0c7062',background_color:'#123d32',accent_color:'#b9cf9c',announcement:'',logo_url:null,favicon_url:null}
 await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(body)})})
 await page.goto('/modules');await expect(page.getByRole('heading',{name:'Choose your modules'})).toBeVisible()
 await page.getByRole('button',{name:'Simple mode',exact:true}).click();await page.getByRole('button',{name:'Save my modules'}).click();await expect(page.getByRole('status')).toContainText('layout is saved');await expect(page.getByRole('link',{name:'Products',exact:true})).toHaveCount(0)
 await page.goto('/products');await expect(page).toHaveURL(/\/modules$/)
 await page.getByRole('button',{name:'Advanced mode',exact:true}).click();await page.locator('input[value="products"]').uncheck();await expect(page.getByText('Custom selection',{exact:true})).toBeVisible();await page.getByRole('button',{name:'Save my modules'}).click();await expect(page.getByRole('status')).toContainText('layout is saved')
 await page.reload();await expect(page.getByText('Custom selection',{exact:true})).toBeVisible();await expect(page.locator('input[value="products"]')).not.toBeChecked()
 await page.getByRole('link',{name:'Statements',exact:true}).click();await page.getByRole('combobox',{name:'Client',exact:true}).selectOption('1');await page.getByRole('button',{name:'Generate report'}).click();await expect(page.getByText('Closing balance:')).toContainText('80.00')
 await page.goto('/modules');await page.screenshot({path:'../artifacts/modules-desktop.png',fullPage:true});await page.setViewportSize({width:390,height:844});await expect(page.getByRole('button',{name:'Sign out',exact:true})).toBeVisible();expect(await page.evaluate(()=>document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);await page.screenshot({path:'../artifacts/modules-mobile.png',fullPage:true})
})
