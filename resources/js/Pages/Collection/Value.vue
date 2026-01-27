<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'
import axios from 'axios'

const props = defineProps({
  stats: Object,
  cards: Array,
  availableGames: Array,
  availableSets: Array,
})

// State
const searchQuery = ref('')
const selectedGame = ref('')
const selectedSet = ref('')
const selectedRarity = ref('')
const sortField = ref('name')
const sortDirection = ref('asc')

// Selection state
const selectedCards = ref(new Set())
const lastClickedIndex = ref(null)
const showBulkConditionModal = ref(false)
const bulkCondition = ref('')
const isAssigningCondition = ref(false)

// Available conditions for bulk assignment
const availableConditions = [
  'Near Mint',
  'Lightly Played',
  'Moderately Played',
  'Heavily Played',
  'Damaged'
]

// Get unique sets and rarities for filters (these are now from backend)
// But we keep these computed for backward compatibility if needed
const uniqueSets = computed(() => {
  return props.availableSets || []
})

const uniqueGames = computed(() => {
  return props.availableGames || []
})

const uniqueRarities = computed(() => {
  const rarities = new Set(props.cards.map(card => card.rarity).filter(Boolean))
  return [...rarities].sort()
})

// Filtered and sorted cards
const filteredCards = computed(() => {
  let result = props.cards

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(card =>
      card.name?.toLowerCase().includes(query) ||
      card.number?.toLowerCase().includes(query) ||
      card.set?.toLowerCase().includes(query)
    )
  }
  
  // Game filter
  if (selectedGame.value) {
    result = result.filter(card => card.game === selectedGame.value)
  }

  // Set filter
  if (selectedSet.value) {
    result = result.filter(card => card.set_abbr === selectedSet.value)
  }

  // Rarity filter
  if (selectedRarity.value) {
    result = result.filter(card => card.rarity === selectedRarity.value)
  }

  // Sorting
  result = [...result].sort((a, b) => {
    let aVal = a[sortField.value]
    let bVal = b[sortField.value]

    // Handle null values
    if (aVal === null || aVal === undefined) return 1
    if (bVal === null || bVal === undefined) return -1

    // Convert to comparable types
    if (typeof aVal === 'string') aVal = aVal.toLowerCase()
    if (typeof bVal === 'string') bVal = bVal.toLowerCase()

    const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0
    return sortDirection.value === 'asc' ? comparison : -comparison
  })

  return result
})

// Dynamic stats based on filters
const filteredStats = computed(() => {
  const cards = filteredCards.value
  const totalCards = cards.length
  const cardsWithMarketData = cards.filter(c => c.has_market_data).length
  const totalValue = cards.reduce((sum, c) => sum + (parseFloat(c.estimated_value) || 0), 0)
  
  return {
    total_cards: totalCards,
    cards_with_market_data: cardsWithMarketData,
    cards_without_market_data: totalCards - cardsWithMarketData,
    total_value: totalValue,
    match_rate: totalCards > 0 ? ((cardsWithMarketData / totalCards) * 100).toFixed(2) : 0
  }
})

// Sorting handler
const sort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

// Formatters
const formatCurrency = (value) => {
  if (value === null || value === undefined) return 'N/A'
  return `$${parseFloat(value).toFixed(2)}`
}

const formatPercentage = (value) => {
  if (value === null || value === undefined) return 'N/A'
  const num = parseFloat(value)
  return `${num >= 0 ? '+' : ''}${num.toFixed(2)}%`
}

const getProfitClass = (value) => {
  if (value === null || value === undefined) return 'text-gray-400'
  return parseFloat(value) >= 0 ? 'text-green-400' : 'text-red-400'
}

const getSortIcon = (field) => {
  if (sortField.value !== field) return '↕'
  return sortDirection.value === 'asc' ? '↑' : '↓'
}

// Update condition for a card
const updateCondition = (cardId, condition) => {
  if (!condition) return
  
  router.post(`/cards/${cardId}/condition`, {
    condition: condition
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Refresh page to show updated data
      router.reload({ only: ['cards', 'stats'] })
    },
    onError: (errors) => {
      console.error('Failed to update condition:', errors)
      alert('Failed to update condition. Please try again.')
    }
  })
}

// Selection functions
const toggleCardSelection = (cardId, checked, event, index) => {
  if (event?.shiftKey && lastClickedIndex.value !== null && checked) {
    const start = Math.min(lastClickedIndex.value, index)
    const end = Math.max(lastClickedIndex.value, index)
    
    for (let i = start; i <= end; i++) {
      if (filteredCards.value[i]) {
        selectedCards.value.add(filteredCards.value[i].id)
      }
    }
  } else {
    if (checked) {
      selectedCards.value.add(cardId)
    } else {
      selectedCards.value.delete(cardId)
    }
  }
  
  if (checked) {
    lastClickedIndex.value = index
  }
}

const selectAll = (checked) => {
  if (checked) {
    filteredCards.value.forEach(card => selectedCards.value.add(card.id))
  } else {
    clearSelection()
  }
}

const clearSelection = () => {
  selectedCards.value = new Set()
  lastClickedIndex.value = null
}

// Bulk condition functions
const openBulkConditionModal = () => {
  showBulkConditionModal.value = true
}

const closeBulkConditionModal = () => {
  showBulkConditionModal.value = false
  bulkCondition.value = ''
}

const saveBulkCondition = async () => {
  const cardIds = Array.from(selectedCards.value)
  if (cardIds.length === 0) {
    alert('Nessuna carta selezionata')
    return
  }

  if (!bulkCondition.value) {
    alert('Seleziona una condizione')
    return
  }

  if (isAssigningCondition.value) return
  isAssigningCondition.value = true

  try {
    // Update each card's condition
    await Promise.all(cardIds.map(id => 
      axios.post(`/cards/${id}/condition`, { condition: bulkCondition.value })
    ))
    
    alert(`Condizione aggiornata per ${cardIds.length} carte!`)
    closeBulkConditionModal()
    clearSelection()
    router.reload({ only: ['cards', 'stats'] })
  } catch (error) {
    console.error('Error assigning condition:', error)
    alert('Errore durante l\'aggiornamento')
  } finally {
    isAssigningCondition.value = false
  }
}

</script>

<template>
  <Head title="Card Scanner - Valore Collezione" />

  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Collection Value</h1>
        <p class="text-gray-400">Track the estimated value of your card collection</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <StatsCard
          title="Total Cards"
          :value="filteredStats.total_cards"
          :subtitle="`${filteredStats.cards_with_market_data} matched`"
        />
        <StatsCard
          title="Total Value"
          :value="formatCurrency(filteredStats.total_value)"
          :color="filteredStats.total_value > 0 ? 'text-green-400' : 'text-white'"
        />
        <StatsCard
          title="Match Rate"
          :value="`${filteredStats.match_rate}%`"
          :subtitle="`${filteredStats.cards_without_market_data} unmatched`"
          :color="filteredStats.match_rate >= 80 ? 'text-green-400' : filteredStats.match_rate >= 50 ? 'text-yellow-400' : 'text-red-400'"
        />
      </div>

      <!-- Filters -->
      <div class="bg-gray-800 rounded-lg p-6 mb-6 border border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search -->
          <div>
            <label class="block text-sm text-gray-400 mb-2">Search</label>
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search cards..."
              class="w-full bg-gray-900 border border-gray-700 rounded px-4 py-2 text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none"
            />
          </div>
          
          <!-- Game Filter -->
          <div>
            <label class="block text-sm text-gray-400 mb-2">Game</label>
            <select
              v-model="selectedGame"
              class="w-full bg-gray-900 border border-gray-700 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
            >
              <option value="">Tutti</option>
              <option v-for="game in uniqueGames" :key="game" :value="game">{{ game }}</option>
            </select>
          </div>

          <!-- Set Filter -->
          <div>
            <label class="block text-sm text-gray-400 mb-2">Set</label>
            <select
              v-model="selectedSet"
              class="w-full bg-gray-900 border border-gray-700 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
            >
              <option value="">Tutti</option>
              <option v-for="set in uniqueSets" :key="set" :value="set">{{ set }}</option>
            </select>
          </div>

          <!-- Rarity Filter -->
          <div>
            <label class="block text-sm text-gray-400 mb-2">Rarity</label>
            <select
              v-model="selectedRarity"
              class="w-full bg-gray-900 border border-gray-700 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
            >
              <option value="">All Rarities</option>
              <option v-for="rarity in uniqueRarities" :key="rarity" :value="rarity">{{ rarity }}</option>
            </select>
          </div>
        </div>

        <!-- Results count -->
        <div class="mt-4 text-sm text-gray-400">
          Showing {{ filteredCards.length }} of {{ cards.length }} cards
        </div>
      </div>

      <!-- Bulk Actions Bar -->
      <div v-if="selectedCards.size > 0" class="mb-4 p-4 rounded-lg" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(59, 130, 246, 0.5);">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div class="flex items-center gap-3">
            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">
              {{ selectedCards.size }} carte selezionate
            </span>
            <button class="text-gray-300 hover:text-white text-sm" @click="clearSelection">
              ✕ Deseleziona
            </button>
          </div>
          <div class="flex gap-2">
            <button 
              class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors"
              @click="openBulkConditionModal"
            >
              🏷️ Assegna Condizione
            </button>
          </div>
        </div>
      </div>

      <!-- Cards Table -->
      <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-center" style="width: 40px;">
                  <input 
                    type="checkbox" 
                    @change="selectAll($event.target.checked)"
                    :checked="filteredCards.length > 0 && selectedCards.size === filteredCards.length"
                    class="rounded bg-gray-600 border-gray-500"
                  >
                </th>
                <th @click="sort('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Card Name {{ getSortIcon('name') }}
                </th>
                <th @click="sort('number')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Number {{ getSortIcon('number') }}
                </th>
                <th @click="sort('set')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Set {{ getSortIcon('set') }}
                </th>
                <th @click="sort('rarity')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Rarity {{ getSortIcon('rarity') }}
                </th>
                <th @click="sort('game')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Game {{ getSortIcon('game') }}
                </th>
                <th @click="sort('condition')" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Condition {{ getSortIcon('condition') }}
                </th>
                <th @click="sort('estimated_value')" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600">
                  Est. Value {{ getSortIcon('estimated_value') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
              <tr v-for="(card, index) in filteredCards" :key="card.id" class="hover:bg-gray-750 transition-colors">
                <td class="px-4 py-4 text-center">
                  <input 
                    type="checkbox" 
                    :checked="selectedCards.has(card.id)"
                    @change="toggleCardSelection(card.id, $event.target.checked, $event, index)"
                    class="rounded bg-gray-600 border-gray-500"
                  >
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div v-if="card.image" class="flex-shrink-0 h-16 w-12 mr-4">
                        <img :src="card.image" class="h-16 w-12 rounded object-cover border border-gray-600" :alt="card.name">
                    </div>
                    <div class="text-sm font-medium text-white">{{ card.name || 'Unknown' }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                  {{ card.number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                  <div>{{ card.set || 'N/A' }}</div>
                  <div v-if="card.set_abbr" class="text-xs text-gray-500">{{ card.set_abbr }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                  {{ card.rarity || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                  {{ card.game || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                  <span v-if="card.condition">{{ card.condition }}</span>
                  <select 
                    v-else-if="card.has_market_data && card.available_conditions && card.available_conditions.length > 0"
                    @change="updateCondition(card.id, $event.target.value)"
                    class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-sm text-white focus:border-blue-500 focus:outline-none"
                  >
                    <option value="">Select condition...</option>
                    <option v-for="condition in card.available_conditions" :key="condition" :value="condition">
                      {{ condition }}
                    </option>
                  </select>
                  <span v-else-if="!card.has_market_data" class="text-yellow-500 text-xs">
                    Not matched
                  </span>
                  <span v-else class="text-gray-500">N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <span v-if="card.estimated_value !== null" class="text-green-400">
                    {{ formatCurrency(card.estimated_value) }}
                  </span>
                  <span v-else class="text-gray-500">N/A</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-if="filteredCards.length === 0" class="text-center py-12">
          <p class="text-gray-400">No cards found matching your filters.</p>
        </div>
      </div>
    </div>

    <!-- Bulk Condition Modal -->
    <div v-if="showBulkConditionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeBulkConditionModal">
      <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md border border-gray-700">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-white">Assegna Condizione</h3>
          <button @click="closeBulkConditionModal" class="text-gray-400 hover:text-white">
            ✕
          </button>
        </div>
        <div class="mb-4">
          <p class="text-gray-400 mb-4">
            Seleziona la condizione da assegnare a <span class="font-bold text-white">{{ selectedCards.size }}</span> carte:
          </p>
          <select 
            v-model="bulkCondition"
            class="w-full bg-gray-900 border border-gray-700 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
          >
            <option value="">Seleziona condizione...</option>
            <option v-for="condition in availableConditions" :key="condition" :value="condition">
              {{ condition }}
            </option>
          </select>
        </div>
        <div class="flex justify-end gap-3">
          <button 
            @click="closeBulkConditionModal"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors"
          >
            Annulla
          </button>
          <button 
            @click="saveBulkCondition" 
            :disabled="isAssigningCondition || !bulkCondition"
            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded transition-colors"
          >
            <span v-if="isAssigningCondition">Salvataggio...</span>
            <span v-else>✓ Assegna Condizione</span>
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
