<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6">Place Order</h2>

    <form @submit.prevent="submitOrder" class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Symbol</label>
          <select v-model="form.symbol" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Side</label>
          <select v-model="form.side" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="buy">Buy</option>
            <option value="sell">Sell</option>
          </select>
        </div>
      </div>

      <!-- Price -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Price (USD)</label>
        <input 
          v-model.number="form.price" 
          type="number" 
          step="0.01"
          min="0.00000001"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
          placeholder="50000.00"
          required
        />
      </div>

      <!-- Amount -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
        <input 
          v-model.number="form.amount" 
          type="number" 
          step="0.00000001"
          min="0.00000001"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
          placeholder="0.1"
          required
        />
      </div>

      <!-- Calculations -->
      <div v-if="totalValue" class="bg-gray-50 p-4 rounded-lg">
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Total Value:</span>
          <span class="font-semibold">${{ totalValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
        </div>
        <div class="flex justify-between text-sm mt-1">
          <span class="text-gray-600">Commission (1.5%):</span>
          <span class="font-semibold">${{ commission.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
        </div>
        <div class="flex justify-between text-sm mt-1 pt-2 border-t">
          <span class="text-gray-700 font-medium">Total Cost:</span>
          <span class="font-bold">${{ totalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
        </div>
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        :disabled="loading"
        class="w-full py-3 rounded-lg font-semibold transition"
        :class="form.side === 'buy' ? 'bg-green-600 hover:bg-green-700 text-white disabled:opacity-50 disabled:cursor-not-allowed' 
                                    : 'bg-red-600 hover:bg-red-700 text-white disabled:opacity-50 disabled:cursor-not-allowed'"
      >
        {{ loading ? 'Placing Order...' : `Place ${form.side === 'buy' ? 'Buy' : 'Sell'} Order` }}
      </button>

      <!-- Error / Success Messages -->
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        {{ error }}
      </div>
      <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
        {{ success }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import http from '../api/http' 

const emit = defineEmits(['order-placed'])

const form = reactive({
  symbol: 'BTC',
  side: 'buy',
  price: null,
  amount: null
})

const loading = ref(false)
const error = ref(null)
const success = ref(null)

const totalValue = computed(() => form.price && form.amount ? form.price * form.amount : 0)
const commission = computed(() => totalValue.value * 0.015)
const totalCost = computed(() => form.side === 'buy' ? totalValue.value + commission.value : totalValue.value)

const submitOrder = async () => {
  loading.value = true
  error.value = null
  success.value = null

  try {
    const { data } = await http.post('/orders', form)
    success.value = 'Order placed successfully!'
    emit('order-placed')

    // Reset form
    form.price = null
    form.amount = null
    form.symbol = 'BTC'
    form.side = 'buy'

    setTimeout(() => success.value = null, 3000)
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to place order'
  } finally {
    loading.value = false
  }
}
</script>
