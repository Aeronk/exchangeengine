<template>
  <div>
    <div v-if="auth.isLoading.value" class="min-h-screen flex items-center justify-center">
      <div class="text-xl text-gray-600">Loading...</div>
    </div>
    
    <Login 
      v-else-if="!auth.isAuthenticated.value" 
      @login-success="handleLoginSuccess" 
    />
    
    <Dashboard 
      v-else 
      :user="auth.user.value" 
      @logout="handleLogout" 
    />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuth } from '../composables/useAuth.js'
import Login from './Login.vue'
import Dashboard from './Dashboard.vue'

const auth = useAuth()

const handleLoginSuccess = () => {
  // User is already set in login method, nothing to do
}

const handleLogout = async () => {
  await auth.logout()
}

// Check auth status on mount
onMounted(async () => {
  await auth.checkAuth()
})
</script>