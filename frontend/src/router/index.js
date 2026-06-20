import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue'),
        meta: { title: '仪表盘' }
      },
      {
        path: 'residents',
        name: 'Residents',
        component: () => import('@/views/residents/Index.vue'),
        meta: { title: '住户管理' }
      },
      {
        path: 'residents/:id',
        name: 'ResidentDetail',
        component: () => import('@/views/residents/Detail.vue'),
        meta: { title: '住户详情' }
      },
      {
        path: 'leases',
        name: 'Leases',
        component: () => import('@/views/leases/Index.vue'),
        meta: { title: '租约管理' }
      },
      {
        path: 'arrears',
        name: 'Arrears',
        component: () => import('@/views/arrears/Index.vue'),
        meta: { title: '欠费管理' }
      },
      {
        path: 'maintenance',
        name: 'Maintenance',
        component: () => import('@/views/maintenance/Index.vue'),
        meta: { title: '维修工单' }
      },
      {
        path: 'qualification',
        name: 'Qualification',
        component: () => import('@/views/qualification/Index.vue'),
        meta: { title: '资格复核' }
      },
      {
        path: 'renewals',
        name: 'Renewals',
        component: () => import('@/views/leases/Renewals.vue'),
        meta: { title: '续租申请' }
      },
      {
        path: 'config',
        name: 'Config',
        component: () => import('@/views/Config.vue'),
        meta: { title: '系统配置' }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return '/login'
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    return '/'
  }
})

export default router
