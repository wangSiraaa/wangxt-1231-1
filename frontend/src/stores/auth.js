import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login, logout, getUserInfo } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userName = computed(() => user.value?.name || '')
  const userRole = computed(() => user.value?.role || '')

  async function handleLogin(credentials) {
    const response = await login(credentials)
    token.value = response.token
    user.value = response.user
    
    localStorage.setItem('token', response.token)
    localStorage.setItem('user', JSON.stringify(response.user))
    
    return response
  }

  async function handleLogout() {
    try {
      await logout()
    } catch (e) {
      console.error('Logout error:', e)
    } finally {
      clearAuth()
    }
  }

  async function fetchUserInfo() {
    try {
      const response = await getUserInfo()
      user.value = response
      localStorage.setItem('user', JSON.stringify(response))
      return response
    } catch (e) {
      clearAuth()
      throw e
    }
  }

  function clearAuth() {
    token.value = ''
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return {
    token,
    user,
    isAuthenticated,
    userName,
    userRole,
    handleLogin,
    handleLogout,
    fetchUserInfo,
    clearAuth
  }
})
