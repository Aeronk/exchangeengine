<template>
  <div class="min-h-screen bg-gray-50 p-6 space-y-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold">Welcome, {{ user.name }}</h1>
      <button 
        @click="handleLogout" 
        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
      >
        Logout
      </button>
    </div>

    <!-- Profile / Wallet -->
    <div class="bg-white rounded-lg shadow-md p-6 grid grid-cols-2 gap-6">
      <div>
        <h2 class="text-xl font-semibold mb-2">USD Balance</h2>
        <p class="text-2xl font-bold">${{ formatNumber(profile.balance) }}</p>
      </div>
      <div>
        <h2 class="text-xl font-semibold mb-2">Assets</h2>
        <div class="space-y-1">
          <div v-for="asset in profile.assets" :key="asset.symbol" class="flex justify-between text-sm">
            <span>{{ asset.symbol }}</span>
            <span>{{ formatNumber(asset.amount) }} (Locked: {{ formatNumber(asset.locked_amount) }})</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Form -->
    <order-form 
      @order-placed="handleOrderPlaced"
      @show-notification="showNotification"
    />

    <!-- Orderbook -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-2xl font-bold mb-4">Orderbook - {{ selectedSymbol }}</h2>
      <div class="grid grid-cols-2 gap-6">
        <div>
          <h3 class="text-lg font-semibold mb-2">Buy Orders</h3>
          <ul class="space-y-1">
            <li v-for="order in buyOrders" :key="order.id" class="flex justify-between text-sm">
              <span>{{ formatNumber(order.price) }}</span>
              <span>{{ formatNumber(order.amount) }}</span>
            </li>
          </ul>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-2">Sell Orders</h3>
          <ul class="space-y-1">
            <li v-for="order in sellOrders" :key="order.id" class="flex justify-between text-sm">
              <span>{{ formatNumber(order.price) }}</span>
              <span>{{ formatNumber(order.amount) }}</span>
            </li>
          </ul>
        </div>
      </div>
      <div v-if="spread !== null" class="mt-4 text-sm text-gray-600">
        Spread: ${{ formatNumber(spread) }}
      </div>
    </div>

    <!-- My Orders -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">My Orders</h2>
        <div class="flex gap-3 items-center">
          <!-- Filters -->
          <select 
            v-model="filters.symbol" 
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          >
            <option value="">All Symbols</option>
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
          </select>

          <select 
            v-model="filters.side" 
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          >
            <option value="">All Sides</option>
            <option value="buy">Buy</option>
            <option value="sell">Sell</option>
          </select>

          <select 
            v-model="filters.status" 
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          >
            <option value="">All Status</option>
            <option value="1">Open</option>
            <option value="2">Filled</option>
            <option value="3">Cancelled</option>
          </select>

          <button @click="loadMyOrders" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
            Refresh
          </button>
        </div>
      </div>

      <!-- Volume Stats -->
      <div v-if="filteredOrders.length > 0" class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-blue-50 rounded-lg p-3">
          <p class="text-xs text-blue-600 font-medium">Total Orders</p>
          <p class="text-xl font-bold text-blue-900">{{ filteredOrders.length }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3">
          <p class="text-xs text-green-600 font-medium">Total Volume</p>
          <p class="text-xl font-bold text-green-900">${{ formatNumber(totalVolume) }}</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-3">
          <p class="text-xs text-purple-600 font-medium">Open Orders</p>
          <p class="text-xl font-bold text-purple-900">{{ openOrdersCount }}</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-3">
          <p class="text-xs text-orange-600 font-medium">Filled Orders</p>
          <p class="text-xl font-bold text-orange-900">{{ filledOrdersCount }}</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b-2 border-gray-200">
              <th class="text-left p-3 font-semibold text-gray-700">Time</th>
              <th class="text-left p-3 font-semibold text-gray-700">Symbol</th>
              <th class="text-left p-3 font-semibold text-gray-700">Side</th>
              <th class="text-left p-3 font-semibold text-gray-700">Price</th>
              <th class="text-left p-3 font-semibold text-gray-700">Amount</th>
              <th class="text-left p-3 font-semibold text-gray-700">Total</th>
              <th class="text-left p-3 font-semibold text-gray-700">Status</th>
              <th class="text-left p-3 font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredOrders.length === 0">
              <td colspan="8" class="text-center py-8 text-gray-400">No orders found</td>
            </tr>
            <tr 
              v-for="order in filteredOrders" 
              :key="order.id"
              class="border-b border-gray-100 hover:bg-gray-50 transition"
            >
              <td class="p-3 text-sm text-gray-600">{{ formatTime(order.created_at) }}</td>
              <td class="p-3"><span class="font-semibold">{{ order.symbol }}</span></td>
              <td class="p-3">
                <span 
                  class="px-2 py-1 rounded text-xs font-semibold"
                  :class="order.side === 'buy' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ order.side.toUpperCase() }}
                </span>
              </td>
              <td class="p-3 font-mono text-sm">${{ formatNumber(order.price) }}</td>
              <td class="p-3 text-sm">{{ formatNumber(order.amount) }}</td>
              <td class="p-3 font-semibold text-sm">${{ formatNumber(order.price * order.amount) }}</td>
              <td class="p-3">
                <span class="px-2 py-1 rounded text-xs font-semibold" :class="getStatusClass(order.status)">
                  {{ getStatusText(order.status) }}
                </span>
              </td>
              <td class="p-3">
                <button 
                  v-if="order.status === 1"
                  @click="cancelOrder(order.id)"
                  class="text-red-600 hover:text-red-700 text-sm font-medium hover:underline"
                >
                  Cancel
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Notification Toast -->
    <div v-if="notification" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 animate-slide-in z-50">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <p class="font-semibold">Order Matched!</p>
        <p class="text-sm opacity-90">{{ notification }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>

import api from '../api/axios.js'
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import OrderForm from './OrderForm.vue'

const props = defineProps({ user: Object })
const emit = defineEmits(['logout'])

const profile = reactive({ balance: 0, assets: [] })
const selectedSymbol = ref('BTC')
const orderbook = ref([])
const myOrders = ref([])
const notification = ref(null)
const echo = ref(null)

// Filters
const filters = reactive({
  symbol: '',
  side: '',
  status: ''
})

const buyOrders = computed(() => orderbook.value.filter(o => o.side === 'buy').sort((a, b) => parseFloat(b.price) - parseFloat(a.price)))
const sellOrders = computed(() => orderbook.value.filter(o => o.side === 'sell').sort((a, b) => parseFloat(a.price) - parseFloat(b.price)))
const spread = computed(() => (sellOrders.value.length && buyOrders.value.length) ? parseFloat(sellOrders.value[0].price) - parseFloat(buyOrders.value[0].price) : null)

// Filtered orders
const filteredOrders = computed(() => {
  if (!myOrders.value || myOrders.value.length === 0) {
    return []
  }
  
  return myOrders.value.filter(order => {
    if (filters.symbol && order.symbol !== filters.symbol) return false
    if (filters.side && order.side !== filters.side) return false
    if (filters.status && order.status.toString() !== filters.status) return false
    return true
  })
})

// Volume calculations
const totalVolume = computed(() => {
  return filteredOrders.value.reduce((sum, order) => {
    return sum + (order.price * order.amount)
  }, 0)
})

const openOrdersCount = computed(() => {
  return filteredOrders.value.filter(o => o.status === 1).length
})

const filledOrdersCount = computed(() => {
  return filteredOrders.value.filter(o => o.status === 2).length
})

const loadProfile = async () => {
  try {
    const { data } = await api.get('/profile')
    profile.balance = data.balance
    profile.assets = data.assets
  } catch (err) { 
    console.error('Failed to load profile:', err) 
  }
}

const loadOrderbook = async () => {
  try { 
    const { data } = await api.get(`/orders?symbol=${selectedSymbol.value}`)
    orderbook.value = data.orders 
  } catch (err) { 
    console.error('Failed to load orderbook:', err) 
  }
}

const loadMyOrders = async () => {
  try { 
    const { data } = await api.get('/my-orders')
    myOrders.value = data.orders 
  } catch (err) { 
    console.error('Failed to load my orders:', err) 
  }
}

const cancelOrder = async (orderId) => {
  if (!confirm('Are you sure you want to cancel this order?')) return
  
  try { 
    await api.post(`/orders/${orderId}/cancel`)
    loadAll()
    showNotification('Order cancelled successfully') 
  } catch (err) { 
    alert(err.response?.data?.message || 'Failed to cancel order') 
  }
}

const handleLogout = async () => {
  if (!confirm('Are you sure you want to logout?')) return
  
  try {
    await api.post('/logout')
    localStorage.removeItem('token')
    
    if (echo.value) {
      echo.value.disconnect()
    }
    
    emit('logout')
  } catch (err) { 
    console.error('Logout error:', err)
    localStorage.removeItem('token')
    emit('logout')
  }
}

const loadAll = () => { loadProfile(); loadOrderbook(); loadMyOrders() }
const handleOrderPlaced = () => loadAll()

const showNotification = (message) => { 
  notification.value = message
  setTimeout(() => notification.value = null, 5000) 
}

const getStatusClass = status => ({ 1:'bg-blue-100 text-blue-700',2:'bg-green-100 text-green-700',3:'bg-gray-100 text-gray-700' }[status])
const getStatusText = status => ({1:'OPEN',2:'FILLED',3:'CANCELLED'}[status])
const formatNumber = num => num ? parseFloat(num).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:8}) : '0.00'
const formatTime = timestamp => new Date(timestamp).toLocaleString('en-US',{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})

const setupEcho = () => {
  try {
    window.Pusher = Pusher
    echo.value = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      forceTLS: true,
      encrypted: true,
      authEndpoint: '/broadcasting/auth',
      auth: { headers: { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' } },
    })
    if (props.user?.id) {
      echo.value.private(`user.${props.user.id}`).listen('OrderMatched', e => {
        showNotification(`Trade executed at ${formatNumber(e.trade.price)} for ${formatNumber(e.trade.amount)} ${e.trade.symbol}`)
        loadAll()
      })
    }
  } catch (err) {
    console.log('Pusher not configured - real-time updates disabled')
  }
}

onMounted(() => {
  loadAll()
  setupEcho()
  const interval = setInterval(loadOrderbook, 10000)
  onUnmounted(() => { clearInterval(interval); echo.value?.disconnect() })
})
</script>

<style scoped>
@keyframes slide-in { from { transform: translateX(100%); opacity:0 } to { transform: translateX(0); opacity:1 } }
.animate-slide-in { animation: slide-in 0.3s ease-out; }
</style>