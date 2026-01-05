<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  unmatchedCards: Array,
  stats: Object
})

// State
const showingSuggestionsFor = ref(null)
const suggestions = ref([])
const loadingSuggestions = ref(false)
const matchingCard = ref(null)

// Computed
const hasUnmatchedCards = computed(() => props.unmatchedCards.length > 0)
const matchRate = computed(() => {
  if (props.stats.total_cards === 0) return 0
  return ((props.stats.matched_cards / props.stats.total_cards) * 100).toFixed(2)
})

// Methods
const autoMatch = () => {
  if (!confirm('Auto-match all unmatched cards? This will attempt to automatically find matches for all cards.')) {
    return
  }

  router.post('/matching/auto-match', {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled by flash message
    },
    onError: (errors) => {
      console.error('Auto-match failed:', errors)
    }
  })
}

const getSuggestions = async (card) => {
  showingSuggestionsFor.value = card
  loadingSuggestions.value = true
  suggestions.value = []

  try {
    const response = await fetch(`/matching/cards/${card.id}/suggestions`)
    const data = await response.json()
    suggestions.value = data.suggestions || []
  } catch (error) {
    console.error('Failed to load suggestions:', error)
    alert('Failed to load suggestions. Please try again.')
  } finally {
    loadingSuggestions.value = false
  }
}

const matchCard = (marketCardId) => {
  if (!showingSuggestionsFor.value) return

  matchingCard.value = marketCardId

  router.post(`/matching/cards/${showingSuggestionsFor.value.id}/match`, {
    market_card_id: marketCardId
  }, {
    preserveScroll: true,
    onSuccess: () => {
      closeModal()
    },
    onError: (errors) => {
      console.error('Match failed:', errors)
      alert('Failed to match card. Please try again.')
    },
    onFinish: () => {
      matchingCard.value = null
    }
  })
}

const closeModal = () => {
  showingSuggestionsFor.value = null
  suggestions.value = []
  loadingSuggestions.value = false
}

const formatPrice = (price) => {
  if (!price) return 'N/A'
  return `$${parseFloat(price).toFixed(2)}`
}
</script>

<template>
  <Head title="Card Scanner - Matching" />

  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Card Matching</h1>
        <p class="text-gray-400">Match your cards to market data for accurate valuations</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <StatsCard
          title="Total Cards"
          :value="stats.total_cards"
        />
        <StatsCard
          title="Matched"
          :value="stats.matched_cards"
          :subtitle="`${matchRate}% match rate`"
          color="text-green-400"
        />
        <StatsCard
          title="Unmatched"
          :value="stats.unmatched_cards"
          :color="stats.unmatched_cards > 0 ? 'text-yellow-400' : 'text-green-400'"
        />
      </div>

      <!-- Auto-match button -->
      <div v-if="hasUnmatchedCards" class="mb-8">
        <button
          @click="autoMatch"
          class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          Auto-Match All Cards
        </button>
        <p class="text-sm text-gray-400 mt-2">
          Automatically match all {{ stats.unmatched_cards }} unmatched cards using intelligent matching algorithms
        </p>
      </div>

      <!-- Empty state (all matched) -->
      <div v-if="!hasUnmatchedCards" class="bg-gray-800 rounded-lg p-12 text-center border border-gray-700">
        <svg class="w-16 h-16 mx-auto mb-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h2 class="text-2xl font-bold mb-2 text-green-400">All Cards Matched! 🎉</h2>
        <p class="text-gray-400">Every card in your collection has been matched to market data.</p>
      </div>

      <!-- Unmatched cards grid -->
      <div v-else>
        <h2 class="text-2xl font-bold mb-4">Unmatched Cards ({{ unmatchedCards.length }})</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div
            v-for="card in unmatchedCards"
            :key="card.id"
            class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700 hover:border-gray-600 transition-all duration-200 hover:shadow-lg"
          >
            <!-- Card Image -->
            <div class="aspect-[2/3] bg-gray-900 flex items-center justify-center">
              <img
                v-if="card.image"
                :src="card.image"
                :alt="card.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-gray-600 text-4xl">🎴</div>
            </div>

            <!-- Card Info -->
            <div class="p-4">
              <h3 class="font-bold text-lg mb-1 truncate" :title="card.name">
                {{ card.name || 'Unknown' }}
              </h3>
              <p class="text-gray-400 text-sm mb-1">{{ card.number || 'N/A' }}</p>
              <p class="text-gray-500 text-xs mb-3">{{ card.set || 'Unknown Set' }}</p>

              <div class="flex items-center gap-2 text-xs">
                <span v-if="card.rarity" class="bg-gray-700 px-2 py-1 rounded">
                  {{ card.rarity }}
                </span>
                <span v-if="card.set_abbreviation" class="bg-gray-700 px-2 py-1 rounded">
                  {{ card.set_abbreviation }}
                </span>
              </div>

              <button
                @click="getSuggestions(card)"
                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded transition-colors duration-200"
              >
                Find Match
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Suggestions Modal -->
      <Modal :show="showingSuggestionsFor !== null" @close="closeModal" max-width="4xl">
        <div class="p-6">
          <!-- Modal Header -->
          <div v-if="showingSuggestionsFor" class="mb-6">
            <h2 class="text-2xl font-bold mb-2">Match Suggestions</h2>
            <div class="text-gray-400">
              <p>Finding matches for: <span class="text-white font-semibold">{{ showingSuggestionsFor.name }}</span></p>
              <p class="text-sm">{{ showingSuggestionsFor.number }} - {{ showingSuggestionsFor.set }}</p>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loadingSuggestions" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4"></div>
            <p class="text-gray-400">Loading suggestions...</p>
          </div>

          <!-- No Suggestions -->
          <div v-else-if="suggestions.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-xl font-bold mb-2">No Suggestions Found</h3>
            <p class="text-gray-400">Could not find any potential matches for this card.</p>
          </div>

          <!-- Suggestions List -->
          <div v-else class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="suggestion in suggestions"
              :key="suggestion.id"
              @click="matchCard(suggestion.id)"
              :class="[
                'border border-gray-700 rounded-lg p-4 cursor-pointer transition-all duration-200',
                'hover:bg-gray-800 hover:border-blue-500',
                matchingCard === suggestion.id ? 'opacity-50 cursor-wait' : ''
              ]"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h3 class="font-bold text-lg mb-1">{{ suggestion.name }}</h3>
                  <div class="flex items-center gap-3 text-sm text-gray-400 mb-2">
                    <span>{{ suggestion.number }}</span>
                    <span>•</span>
                    <span>{{ suggestion.set }}</span>
                    <span v-if="suggestion.rarity">•</span>
                    <span v-if="suggestion.rarity">{{ suggestion.rarity }}</span>
                  </div>

                  <!-- Price Info -->
                  <div v-if="suggestion.price" class="flex items-center gap-4 text-sm">
                    <div>
                      <span class="text-gray-500">Market:</span>
                      <span class="text-green-400 font-semibold ml-1">
                        {{ formatPrice(suggestion.price.market) }}
                      </span>
                    </div>
                    <div>
                      <span class="text-gray-500">Low:</span>
                      <span class="text-yellow-400 font-semibold ml-1">
                        {{ formatPrice(suggestion.price.low) }}
                      </span>
                    </div>
                    <div v-if="suggestion.price.condition">
                      <span class="text-gray-500">Condition:</span>
                      <span class="text-blue-400 font-semibold ml-1">
                        {{ suggestion.price.condition }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Match Icon -->
                <div class="ml-4">
                  <svg v-if="matchingCard !== suggestion.id" class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                  <div v-else class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>
