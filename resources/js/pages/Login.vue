<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Exchange Engine</h1>
        <p class="text-gray-600 mt-2">Login</p>
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
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-semibold"
        >
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
        
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ error }}
        </div>
      </form>
      
      <!-- Demo Accounts for Assessment -->
      <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm">
        <h3 class="text-gray-700 font-semibold mb-2">Demo Accounts (For Testing)</h3>
        <ul class="space-y-1 text-gray-600">
          <li><strong>Buyer:</strong> buyer@test.com / password</li>
          <li><strong>Seller:</strong> seller@test.com / password</li>
          <li><strong>Trader:</strong> trader@test.com / password</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useRouter } from 'vue-router'

const router = useRouter()
const { login } = useAuth()
const loading = ref(false)
const error = ref(null)

const form = reactive({
  email: '',
  password: ''
})

const handleLogin = async () => {
  loading.value = true
  error.value = null

  try {
    await login({ email: form.email, password: form.password })
    router.push('/')
  } catch (err) {
    console.error(err)
    error.value = err.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
