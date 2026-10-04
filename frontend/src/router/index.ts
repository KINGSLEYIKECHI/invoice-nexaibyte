import { createRouter,createWebHistory } from 'vue-router'
import { useAuth } from '../stores/auth'
const router=createRouter({history:createWebHistory(),routes:[
 {path:'/login',component:()=>import('../views/auth/Login.vue'),meta:{guest:true}},
 {path:'/register',component:()=>import('../views/auth/Register.vue'),meta:{guest:true}},
 {path:'/',component:()=>import('../views/Dashboard.vue')},
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
router.beforeEach(async to=>{const auth=useAuth();if(!auth.loaded) await auth.restore();if(!to.meta.guest&&!auth.user)return '/login';if(to.meta.guest&&auth.user)return '/';if(to.meta.platformAdmin&&!auth.user?.is_platform_admin)return '/';if(to.meta.manager&&!auth.manager)return '/'})
export default router