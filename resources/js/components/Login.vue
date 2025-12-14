<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Exchange Engine</h1>
        <p class="text-gray-600 mt-2">Login to start trading</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="buyer@test.com"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input 
            v-model="form.password" 
            type="password" 
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="password"
            required
          />
        </div>
        
        <button 
          type="submit" 
          :disabled="auth.isLoading.value"
          class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-semibold"
        >
          {{ auth.isLoading.value ? 'Logging in...' : 'Login' }}
        </button>
        
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ error }}
        </div>
      </form>
      
      <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <p class="text-xs font-semibold text-gray-700 mb-2">Demo Accounts:</p>
        <div class="space-y-1 text-xs text-gray-600">
          <p><strong>Buyer:</strong> buyer@test.com / password</p>
          <p><strong>Seller:</strong> seller@test.com / password</p>
          <p><strong>Trader:</strong> trader@test.com / password</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuth } from '../composables/useAuth.js'

const emit = defineEmits(['login-success'])

const form = reactive({ 
  email: '', 
  password: '' 
})

const error = ref(null)
const auth = useAuth()

const handleLogin = async () => {
  error.value = null
  
  try {
    await auth.login(form)
    emit('login-success')
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.response?.data?.message || 'Login failed. Please check your credentials.'
  }
}
</script>