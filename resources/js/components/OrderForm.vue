<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Place Order</h2>

    <form @submit.prevent="submitOrder" class="space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Symbol</label>
          <select 
            v-model="form.symbol" 
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
          >
            <option value="BTC">BTC/USD</option>
            <option value="ETH">ETH/USD</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Side</label>
          <select 
            v-model="form.side" 
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
          >
            <option value="buy">Buy</option>
            <option value="sell">Sell</option>
          </select>
        </div>
      </div>

      <!-- Price -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Price (USD)
          <span class="text-gray-500 text-xs ml-1">per unit</span>
        </label>
        <div class="relative">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
          <input 
            v-model.number="form.price" 
            type="number" 
            step="any"
            min="0"
            class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            placeholder="50000.00"
            required
          />
        </div>
      </div>

      <!-- Amount -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Amount
          <span class="text-gray-500 text-xs ml-1">{{ form.symbol }}</span>
        </label>
        <input 
          v-model.number="form.amount" 
          type="number" 
          step="0.00000001"
          min="0.00000001"
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
          placeholder="0.1"
          required
        />
      </div>

      <!-- Volume Preview -->
      <div v-if="totalValue" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-200">
        <div class="flex items-center gap-2 mb-3">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          <h3 class="text-sm font-semibold text-blue-900">Order Summary</h3>
        </div>
        
        <div class="space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-700">Subtotal</span>
            <span class="font-semibold text-gray-900">
              ${{ totalValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
          </div>
          
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-700">Commission (1.5%)</span>
            <span class="font-semibold text-gray-900">
              ${{ commission.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
          </div>
          
          <div class="h-px bg-blue-200 my-2"></div>
          
          <div class="flex justify-between items-center">
            <span class="text-sm font-bold text-blue-900">
              {{ form.side === 'buy' ? 'Total Cost' : 'You Receive' }}
            </span>
            <span class="text-lg font-bold text-blue-900">
              ${{ totalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
          </div>

          <!-- Volume Info -->
          <div class="mt-3 pt-3 border-t border-blue-200">
            <div class="flex justify-between items-center text-xs text-gray-600">
              <span>Order Volume</span>
              <span class="font-medium">{{ form.amount }} {{ form.symbol }}</span>
            </div>
            <div class="flex justify-between items-center text-xs text-gray-600 mt-1">
              <span>Avg. Price</span>
              <span class="font-medium">${{ form.price?.toLocaleString() }}/{{ form.symbol }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        :disabled="loading || !form.price || !form.amount"
        class="w-full py-3.5 rounded-lg font-semibold transition shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        :class="form.side === 'buy' 
          ? 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white' 
          : 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white'"
      >
        <svg v-if="loading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span v-if="loading">Placing Order...</span>
        <span v-else>
          <span v-if="form.side === 'buy'">Place Buy Order</span>
          <span v-else>Place Sell Order</span>
        </span>
      </button>

      <!-- Quick Actions -->
      <div class="flex gap-2 text-xs">
         <button 
          type="button"
          @click="setAmount(10)"
          class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium text-gray-700"
        >
          10%
        </button>

        <button 
          type="button"
          @click="setAmount(25)"
          class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium text-gray-700"
        >
          25%
        </button>
        <button 
          type="button"
          @click="setAmount(50)"
          class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium text-gray-700"
        >
          50%
        </button>
        <button 
          type="button"
          @click="setAmount(75)"
          class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium text-gray-700"
        >
          75%
        </button>
        <button 
          type="button"
          @click="setAmount(100)"
          class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium text-gray-700"
        >
          100%
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import api from '../api/axios.js'

const emit = defineEmits(['order-placed', 'show-notification'])

const form = reactive({
  symbol: 'BTC',
  side: 'buy',
  price: null,
  amount: null
})

const loading = ref(false)

const totalValue = computed(() => 
  form.price && form.amount ? form.price * form.amount : 0
)

const commission = computed(() => 
  totalValue.value * 0.015
)

const totalCost = computed(() => 
  form.side === 'buy' ? totalValue.value + commission.value : totalValue.value - commission.value
)

const setAmount = (percentage) => {
 
  const maxAmount = form.side === 'buy' ? 1 : 10
  form.amount = (maxAmount * percentage / 100).toFixed(8)
}

const submitOrder = async () => {
  loading.value = true

  try {
    const { data } = await api.post('/orders', form)
    
    emit('show-notification', 
      `${form.side.toUpperCase()} order placed: ${form.amount} ${form.symbol} at ${form.price}`
    )
    
    emit('order-placed')

    // Reset form
    form.price = null
    form.amount = null
  } catch (err) {
    emit('show-notification', 
      err.response?.data?.message || 'Failed to place order'
    )
  } finally {
    loading.value = false
  }
}
</script>