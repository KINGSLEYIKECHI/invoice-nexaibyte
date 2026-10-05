import { createRouter,createWebHistory } from 'vue-router'
import { useAuth } from '../stores/auth'
const router=createRouter({history:createWebHistory(),routes:[
 {path:'/about',component:()=>import('../views/About.vue'),meta:{public:true}},
 {path:'/privacy',component:()=>import('../views/Privacy.vue'),meta:{public:true}},
 {path:'/admin',component:()=>import('../views/settings/PlatformAdmin.vue'),meta:{platformAdmin:true}},
 {path:'/login',component:()=>import('../views/auth/Login.vue'),meta:{guest:true}},
 {path:'/register',component:()=>import('../views/auth/Register.vue'),meta:{guest:true}},
 {path:'/',component:()=>import('../views/Dashboard.vue')},
 {path:'/products',component:()=>import('../views/products/ProductList.vue')},
 {path:'/quotations',component:()=>import('../views/documents/DocumentList.vue'),meta:{documentKind:'quotations'}},
 {path:'/quotations/new',component:()=>import('../views/documents/DocumentForm.vue'),meta:{documentKind:'quotations'}},
 {path:'/quotations/:id/edit',component:()=>import('../views/documents/DocumentForm.vue'),meta:{documentKind:'quotations'}},
 {path:'/quotations/:id',component:()=>import('../views/documents/DocumentDetail.vue'),meta:{documentKind:'quotations'}},
 {path:'/delivery-notes',component:()=>import('../views/documents/DocumentList.vue'),meta:{documentKind:'delivery-notes'}},
 {path:'/delivery-notes/new',component:()=>import('../views/documents/DocumentForm.vue'),meta:{documentKind:'delivery-notes'}},
 {path:'/delivery-notes/:id/edit',component:()=>import('../views/documents/DocumentForm.vue'),meta:{documentKind:'delivery-notes'}},
 {path:'/delivery-notes/:id',component:()=>import('../views/documents/DocumentDetail.vue'),meta:{documentKind:'delivery-notes'}},
 {path:'/clients',component:()=>import('../views/clients/ClientList.vue')},
 {path:'/clients/new',component:()=>import('../views/clients/ClientForm.vue')},
 {path:'/clients/:id/edit',component:()=>import('../views/clients/ClientForm.vue')},
 {path:'/invoices',component:()=>import('../views/invoices/InvoiceList.vue')},
 {path:'/invoices/new',component:()=>import('../views/invoices/InvoiceForm.vue')},
 {path:'/invoices/:id/edit',component:()=>import('../views/invoices/InvoiceForm.vue')},
 {path:'/invoices/:id',component:()=>import('../views/invoices/InvoiceDetail.vue')},
 {path:'/team',component:()=>import('../views/team/TeamList.vue'),meta:{manager:true}},
 {path:'/platform',component:()=>import('../views/settings/PlatformSettings.vue'),meta:{platformAdmin:true}},
 {path:'/settings',component:()=>import('../views/settings/BusinessSettings.vue'),meta:{manager:true}},
 {path:'/:pathMatch(.*)*',redirect:'/'}
]})
router.beforeEach(async to=>{if(document.documentElement.dataset.invoiceAdsActive==='true'||document.querySelector('script[data-invoice-ads]')){window.location.assign(to.fullPath);return false;}const auth=useAuth();if(!auth.loaded) await auth.restore();if(!to.meta.guest&&!to.meta.public&&!auth.user)return '/login';if(to.meta.guest&&auth.user)return '/';if(to.meta.platformAdmin&&!auth.user?.is_platform_admin)return '/';if(to.meta.manager&&!auth.manager)return '/'})
export default router