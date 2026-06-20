import axios from 'axios'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const request = axios.create({
  baseURL: '/api',
  timeout: 30000
})

request.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    config.headers['Accept'] = 'application/json'
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

request.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    const authStore = useAuthStore()
    
    if (error.response) {
      const status = error.response.status
      const message = error.response.data?.message || '请求失败'
      
      if (status === 401) {
        authStore.logout()
        router.push('/login')
        ElMessage.error('登录已过期，请重新登录')
      } else if (status === 403) {
        ElMessage.error('没有权限执行此操作')
      } else if (status === 422) {
        const errors = error.response.data?.errors
        if (errors) {
          const firstError = Object.values(errors)[0][0]
          ElMessage.error(firstError)
        } else {
          ElMessage.error(message)
        }
      } else if (status === 400) {
        ElMessage.error(message)
      } else {
        ElMessage.error(`服务器错误: ${status}`)
      }
    } else if (error.request) {
      ElMessage.error('网络错误，请检查网络连接')
    } else {
      ElMessage.error(error.message)
    }
    
    return Promise.reject(error)
  }
)

export default request
