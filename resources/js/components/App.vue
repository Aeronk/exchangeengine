<template>
  <div>
    <login 
      v-if="!isAuthenticated"
      @login-success="handleLoginSuccess"
    />
    
    <dashboard 
      v-else
      :user="user"
      @logout="handleLogout"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Login from './Login.vue'
import Dashboard from './Dashboard.vue'
import http from '../api/http'

const isAuthenticated = ref(false)
const user = ref(null)

const handleLoginSuccess = async () => {
  const { data } = await http.get('/profile') // Laravel Sanctum
  user.value = data
  isAuthenticated.value = true
}

const handleLogout = async () => {
  await http.post('/logout')
  user.value = null
  isAuthenticated.value = false
}

onMounted(async () => {
  try {
    const { data } = await http.get('/profile')
    user.value = data
    isAuthenticated.value = true
  } catch (err) {
    isAuthenticated.value = false
  }
})
</script>
