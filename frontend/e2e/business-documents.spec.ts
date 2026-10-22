import { test,expect } from '@playwright/test'
import { readFileSync } from 'node:fs'
test('company documents save privately and salary totals update in the compiled UI',async({page})=>{
 const templates=JSON.parse(readFileSync('../backend/resources/data/business-documents.json','utf8'))
 let profile={company_name:'Example Workshop',company_address:'10 Sample Road',company_email:'office@example.test',company_phone:'+2348000000000',registration_number:'RC EXAMPLE',company_definition:'Maintenance and installations',values:'Care',policies:'Handbook',signatory_name:'Example Manager',signatory_title:'Director'}
 let draft:any=null
 await page.route(/fonts\.(googleapis|gstatic)\.com/,r=>r.abort())
 await page.addInitScript(()=>localStorage.setItem('invoice_token','test-only-mocked-token'))
 await page.route('**/backend/api/**',async route=>{const path=new URL(route.request().url()).pathname.replace('/backend/api','');let body:any={}
 if(path==='/me')body={id:1,name:'Example Manager',role:'owner',workspace_mode:'advanced',business:{id:1,name:'Example Workshop',currency:'USD'}}
 else if(path==='/platform/settings')body={product_name:'ledger.',company_name:'Example',primary_color:'#0c7062',background_color:'#123d32',accent_color:'#b9cf9c'}
 else if(path==='/currencies')body=[{code:'USD',name:'US Dollar',minor_units:2},{code:'JPY',name:'Yen',minor_units:0}]
 else if(path==='/business-documents/setup')body={templates,profile}
 else if(path==='/business-documents/profile'){profile=route.request().postDataJSON();body=profile}
 else if(path==='/business-documents'&&route.request().method()==='POST'){draft={...route.request().postDataJSON(),id:1};body=draft}
 else if(path==='/business-documents')body={data:draft?[{id:1,title:draft.title,template:draft.template}]:[],last_page:1}
 else if(path==='/business-documents/1')body=draft
 await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(body)})
 })
 await page.goto('/business-documents');await expect(page.getByRole('heading',{name:'Business documents',exact:true})).toBeVisible()
 await expect(page.locator('.document-catalog button')).toHaveCount(14)
 await page.getByRole('button',{name:'Company profile',exact:true}).click();await page.getByLabel('Company Definition',{exact:true}).fill('Engineering and training');await page.getByRole('button',{name:'Save company profile'}).click();await expect(page.getByRole('status')).toContainText('profile saved')
 await page.getByRole('button',{name:'Job offer letter Employment'}).click();await page.getByLabel('Employee Name',{exact:true}).fill('Example Employee');await page.getByLabel('Job Title',{exact:true}).fill('Engineer');await page.getByRole('button',{name:'Prepare editable draft'}).click();await expect(page.getByLabel('Document wording')).toHaveValue(/Example Workshop/);await expect(page.getByRole('button',{name:'Download DOCX'})).toBeDisabled()
 await page.getByRole('button',{name:'Save document',exact:true}).click();await expect(page.getByRole('button',{name:'Download DOCX'})).toBeEnabled();await page.getByLabel('Document wording').fill('Revised draft');await expect(page.getByRole('button',{name:'Download DOCX'})).toBeDisabled();await page.getByRole('button',{name:'Save document',exact:true}).click()
 await page.getByRole('button',{name:'Salary structure Salary planning'}).click();await page.getByRole('button',{name:'Prepare editable draft'}).click();await page.getByLabel('Role',{exact:true}).fill('Engineer');await page.getByLabel('Starting amount').fill('100.05');await page.getByLabel('Annual increase (%)').fill('20');await expect(page.locator('tbody tr').last()).toContainText('120.06');await expect(page.locator('tbody tr').last()).toContainText('144.07');await page.getByRole('button',{name:'Save document',exact:true}).click();await expect(page.getByRole('button',{name:'Download XLSX'})).toBeEnabled()
 await page.screenshot({path:'../artifacts/business-documents-desktop.png',fullPage:true});await page.setViewportSize({width:390,height:844});expect(await page.evaluate(()=>document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);await expect(page.getByRole('button',{name:'Sign out',exact:true})).toBeVisible();await page.screenshot({path:'../artifacts/business-documents-mobile.png',fullPage:true})
})
