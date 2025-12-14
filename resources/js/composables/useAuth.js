import { ref, readonly } from 'vue'
import api from '../api/axios.js'

const user = ref(null)
const isAuthenticated = ref(false)
const isLoading = ref(false)

export function useAuth() {
  
  const login = async (credentials) => {
    isLoading.value = true
    try {
      await axios.get('/sanctum/csrf-cookie')
      
      const { data } = await api.post('/login', credentials)
      
      localStorage.setItem('token', data.token)
      
      user.value = data.user
      isAuthenticated.value = true
      
      return data.user
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    isLoading.value = true
    try {
      await api.post('/logout')
    } catch (err) {
      console.warn('Logout API failed', err)
    } finally {
      localStorage.removeItem('token')
      user.value = null
      isAuthenticated.value = false
      isLoading.value = false
    }
  }

  const checkAuth = async () => {
    const token = localStorage.getItem('token')
    
    if (!token) {
      isAuthenticated.value = false
      user.value = null
      return false
    }

    isLoading.value = true
    try {
      const { data } = await api.get('/me')
      user.value = data
      isAuthenticated.value = true
      return true
    } catch (err) {
      console.error('Auth check failed:', err)
      localStorage.removeItem('token')
      user.value = null
      isAuthenticated.value = false
      return false
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State (read-only to prevent direct mutation)
    user: readonly(user),
    isAuthenticated: readonly(isAuthenticated),
    isLoading: readonly(isLoading),
    
    // Methods
    login,
    logout,
    checkAuth,
  }
}