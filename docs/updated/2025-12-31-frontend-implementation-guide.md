# Sprint 3 - Frontend Implementation Guide

**Stack:** Inertia.js + Vue.js 3 + Composition API  
**Design:** Dark Theme + Mobile Responsive  
**Features:** Card Matching + Collection Value Dashboard

---

## Backend API - Completato ✅

### 1. Collection Value API

**Endpoint:** `GET /collection/value`  
**Controller:** `CollectionController@value`  
**Response:**

```json
{
  "stats": {
    "total_cards": 5,
    "cards_with_market_data": 5,
    "cards_without_market_data": 0,
    "total_value": 1.67,
    "total_cost": 0,
    "cards_with_value": 5,
    "cards_with_cost": 0,
    "average_value": 0.33,
    "total_profit_loss": 1.67,
    "profit_loss_percentage": 0,
    "match_rate": 100
  },
  "cards": [
    {
      "id": 1,
      "name": "Absol",
      "number": "063/094",
      "set": "ME02: Phantasmal Flames",
      "set_abbr": "PFL",
      "rarity": "Common",
      "condition": "Near Mint",
      "printing": "Normal",
      "acquisition_price": null,
      "acquisition_date": null,
      "estimated_value": 0.13,
      "profit_loss": null,
      "profit_loss_percentage": null,
      "has_market_data": true,
      "image": "test/absol.jpg"
    }
  ]
}
```

### 2. Card Matching API

**Endpoint:** `GET /matching`  
**Controller:** `CardMatchingController@index`  
**Response:**

```json
{
  "unmatchedCards": [],
  "stats": {
    "total_cards": 5,
    "matched_cards": 5,
    "unmatched_cards": 0
  }
}
```

**Endpoint:** `POST /matching/auto-match`  
**Action:** Auto-match all unmatched cards  
**Response:** Redirect with success message

**Endpoint:** `GET /matching/cards/{card}/suggestions`  
**Action:** Get match suggestions for a specific card  
**Response:**

```json
{
  "card": {
    "id": 1,
    "name": "Absol",
    "number": "063/094",
    "set": "ME02: Phantasmal Flames",
    "image": "test/absol.jpg"
  },
  "suggestions": [
    {
      "id": 1,
      "name": "Absol",
      "number": "063/094",
      "set": "PFL",
      "rarity": "Common",
      "price": {
        "market": 0.13,
        "low": 0.01,
        "condition": "Near Mint"
      }
    }
  ]
}
```

**Endpoint:** `POST /matching/cards/{card}/match`  
**Payload:** `{ "market_card_id": 123 }`  
**Action:** Manually match a card  
**Response:** Redirect with success message

**Endpoint:** `POST /matching/cards/{card}/unmatch`  
**Action:** Remove match from a card  
**Response:** Redirect with success message

---

## Frontend Components da Creare

### 1. Collection Value Dashboard

**File:** `resources/js/Pages/Collection/Value.vue`

**Features:**
- ✅ Stats cards (4 principali metrics)
- ✅ Datatable con carte e valori
- ✅ Sorting per colonna
- ✅ Filtering per set/rarity
- ✅ Search bar
- ✅ Export CSV (optional)
- ✅ Dark theme
- ✅ Mobile responsive

**Layout:**
```
┌─────────────────────────────────────┐
│  Collection Value Dashboard         │
├─────────┬─────────┬─────────┬───────┤
│ Total   │ Total   │ P&L     │ Match │
│ Cards   │ Value   │ +$1.67  │ 100%  │
│   5     │ $1.67   │ (+0%)   │       │
└─────────┴─────────┴─────────┴───────┘
┌─────────────────────────────────────┐
│ 🔍 Search...          [Filter ▼]    │
├──────┬────────┬───────┬──────┬──────┤
│ Card │ Set    │ Value │ P&L  │ %    │
├──────┼────────┼───────┼──────┼──────┤
│ Abs..│ PFL    │ $0.13 │  -   │  -   │
│ Char.│ PFL    │ $0.13 │  -   │  -   │
└──────┴────────┴───────┴──────┴──────┘
```

**Component Structure:**
```vue
<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  stats: Object,
  cards: Array
})

const searchQuery = ref('')
const selectedSet = ref(null)
const sortField = ref('name')
const sortDirection = ref('asc')

// Computed filtered cards
const filteredCards = computed(() => {
  // Filter + Sort logic
})
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white">
    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <StatsCard title="Total Cards" :value="stats.total_cards" />
      <StatsCard title="Total Value" :value="`$${stats.total_value}`" />
      <StatsCard 
        title="Profit/Loss" 
        :value="`$${stats.total_profit_loss}`"
        :class="stats.total_profit_loss >= 0 ? 'text-green-400' : 'text-red-400'"
      />
      <StatsCard title="Match Rate" :value="`${stats.match_rate}%`" />
    </div>

    <!-- Filters -->
    <div class="mb-6 flex gap-4">
      <input 
        v-model="searchQuery"
        type="search"
        placeholder="Search cards..."
        class="flex-1 bg-gray-800 rounded px-4 py-2"
      />
      <select v-model="selectedSet" class="bg-gray-800 rounded px-4 py-2">
        <option value="">All Sets</option>
        <!-- Dynamic sets -->
      </select>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-700">
          <tr>
            <th @click="sort('name')">Card Name</th>
            <th @click="sort('number')">Number</th>
            <th @click="sort('set')">Set</th>
            <th @click="sort('estimated_value')">Value</th>
            <th>P&L</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="card in filteredCards" :key="card.id" class="border-t border-gray-700">
            <td>{{ card.name }}</td>
            <td>{{ card.number }}</td>
            <td>{{ card.set_abbr }}</td>
            <td>${{ card.estimated_value?.toFixed(2) || 'N/A' }}</td>
            <td :class="getProfitColor(card.profit_loss)">
              {{ formatProfit(card.profit_loss) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
```

### 2. Card Matching Interface

**File:** `resources/js/Pages/Matching/Index.vue`

**Features:**
- ✅ Lista carte non matchate
- ✅ Auto-match button
- ✅ Manual match per card
- ✅ Suggestions modal
- ✅ Match preview
- ✅ Dark theme
- ✅ Mobile responsive

**Layout:**
```
┌─────────────────────────────────────┐
│  Card Matching                      │
│  ┌─────────┬─────────┬─────────┐   │
│  │ Total:5 │ Matched │Unmatched│   │
│  │         │    5    │    0    │   │
│  └─────────┴─────────┴─────────┘   │
│                                     │
│  [Auto-Match All Cards]             │
├─────────────────────────────────────┤
│  No unmatched cards!                │
│  All cards are matched ✓            │
└─────────────────────────────────────┘

<!-- When cards exist: -->
┌─────────────────────────────────────┐
│ Card: Absol #063/094                │
│ ┌─────────┐                         │
│ │  IMG    │  Set: PFL               │
│ │         │  Rarity: Common         │
│ └─────────┘                         │
│  [Find Match] [View Suggestions]    │
└─────────────────────────────────────┘
```

**Component Structure:**
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  unmatchedCards: Array,
  stats: Object
})

const showingSuggestionsFor = ref(null)
const suggestions = ref([])

const autoMatch = () => {
  router.post(route('matching.auto'), {}, {
    onSuccess: () => {
      // Success toast
    }
  })
}

const getSuggestions = async (card) => {
  const response = await fetch(route('matching.suggestions', card.id))
  const data = await response.json()
  suggestions.value = data.suggestions
  showingSuggestionsFor.value = card
}

const matchCard = (pokemonCardId, marketCardId) => {
  router.post(route('matching.match', pokemonCardId), {
    market_card_id: marketCardId
  }, {
    onSuccess: () => {
      showingSuggestionsFor.value = null
    }
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white p-6">
    <h1 class="text-3xl font-bold mb-8">Card Matching</h1>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-8">
      <StatsCard title="Total" :value="stats.total_cards" />
      <StatsCard title="Matched" :value="stats.matched_cards" class="text-green-400" />
      <StatsCard title="Unmatched" :value="stats.unmatched_cards" class="text-yellow-400" />
    </div>

    <!-- Auto-match button -->
    <button 
      v-if="unmatchedCards.length > 0"
      @click="autoMatch"
      class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold mb-6"
    >
      Auto-Match All Cards
    </button>

    <!-- Unmatched cards list -->
    <div v-if="unmatchedCards.length === 0" class="text-center py-12">
      <p class="text-2xl text-green-400">✓ All cards are matched!</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="card in unmatchedCards" 
        :key="card.id"
        class="bg-gray-800 rounded-lg overflow-hidden"
      >
        <img 
          v-if="card.image" 
          :src="`/storage/${card.image}`" 
          :alt="card.name"
          class="w-full h-48 object-cover"
        />
        <div class="p-4">
          <h3 class="font-bold text-lg">{{ card.name }}</h3>
          <p class="text-gray-400 text-sm">{{ card.number }}</p>
          <p class="text-gray-400 text-sm">{{ card.set }}</p>
          
          <button 
            @click="getSuggestions(card)"
            class="mt-4 w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded"
          >
            Find Match
          </button>
        </div>
      </div>
    </div>

    <!-- Suggestions Modal -->
    <Modal :show="showingSuggestionsFor !== null" @close="showingSuggestionsFor = null">
      <div class="bg-gray-800 p-6 rounded-lg">
        <h2 class="text-2xl font-bold mb-4">Match Suggestions</h2>
        <div class="space-y-4">
          <div 
            v-for="suggestion in suggestions" 
            :key="suggestion.id"
            class="border border-gray-700 p-4 rounded hover:bg-gray-700 cursor-pointer"
            @click="matchCard(showingSuggestionsFor.id, suggestion.id)"
          >
            <h3 class="font-bold">{{ suggestion.name }}</h3>
            <p class="text-sm text-gray-400">{{ suggestion.number }} - {{ suggestion.set }}</p>
            <p v-if="suggestion.price" class="text-sm text-green-400">
              Market: ${{ suggestion.price.market }} | Low: ${{ suggestion.price.low }}
            </p>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>
```

---

## Shared Components

### StatsCard.vue
```vue
<script setup>
defineProps({
  title: String,
  value: [String, Number],
  icon: String
})
</script>

<template>
  <div class="bg-gray-800 rounded-lg p-6">
    <p class="text-gray-400 text-sm mb-2">{{ title }}</p>
    <p class="text-3xl font-bold">{{ value }}</p>
  </div>
</template>
```

### Modal.vue
```vue
<script setup>
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}

const closeOnEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape))
</script>

<template>
  <Teleport to="body">
    <Transition>
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
          <div class="fixed inset-0 bg-black opacity-75" @click="close"></div>
          <div class="relative bg-gray-900 rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
            <slot></slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity 0.3s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
```

---

## Tailwind Configuration (Dark Theme)

**File:** `tailwind.config.js`

```javascript
export default {
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        gray: {
          900: '#0f172a',
          800: '#1e293b',
          700: '#334155',
        },
      },
    },
  },
}
```

---

## Navigation Update

Add links to app layout:

```vue
<nav class="space-y-2">
  <Link href="/ocr/upload" class="nav-link">Scan Cards</Link>
  <Link href="/ocr/cards" class="nav-link">My Cards</Link>
  <Link href="/collection/value" class="nav-link">Collection Value</Link>
  <Link href="/matching" class="nav-link">Card Matching</Link>
  <Link href="/market-data" class="nav-link">Market Data</Link>
</nav>
```

---

## Installation Steps

1. **Install dependencies** (if not already):
```bash
npm install
```

2. **Create component structure**:
```
resources/js/
├── Pages/
│   ├── Collection/
│   │   └── Value.vue
│   ├── Matching/
│   │   └── Index.vue
│   └── MarketData/
│       └── Index.vue
└── Components/
    ├── StatsCard.vue
    └── Modal.vue
```

3. **Build assets**:
```bash
npm run dev
```

4. **Test endpoints**:
- `/collection/value`
- `/matching`

---

**Questo documento fornisce la struttura completa per implementare il frontend Vue.js!** 🚀
