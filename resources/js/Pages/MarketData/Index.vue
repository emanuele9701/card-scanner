<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'

const props = defineProps({
  stats: Object
})

// State
const fileInput = ref(null)
const isDragging = ref(false)
const inputMode = ref('file') // 'file' or 'raw'
const rawJsonInput = ref('')
const jsonError = ref('')

// Form
const form = useForm({
  json_file: null,
  raw_json: null
})

// Computed
const rawJsonCharCount = computed(() => rawJsonInput.value.length)
const isRawJsonValid = computed(() => {
  if (!rawJsonInput.value.trim()) return false
  try {
    const parsed = JSON.parse(rawJsonInput.value)
    return parsed && typeof parsed === 'object'
  } catch {
    return false
  }
})

const canSubmit = computed(() => {
  if (inputMode.value === 'file') {
    return !!form.json_file
  } else {
    return isRawJsonValid.value
  }
})

// Methods
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.json_file = file
    jsonError.value = ''
  }
}

const handleDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file && file.type === 'application/json') {
    form.json_file = file
    jsonError.value = ''
  } else {
    alert('Please upload a JSON file')
  }
}

const handleDragOver = (event) => {
  isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const removeFile = () => {
  form.json_file = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const clearRawJson = () => {
  rawJsonInput.value = ''
  jsonError.value = ''
}

const validateRawJson = () => {
  jsonError.value = ''
  if (!rawJsonInput.value.trim()) {
    jsonError.value = 'Please enter JSON data'
    return false
  }
  
  try {
    const parsed = JSON.parse(rawJsonInput.value)
    
    if (!parsed || typeof parsed !== 'object') {
      jsonError.value = 'Invalid JSON: must be an object'
      return false
    }
    
    if (!parsed.result || !Array.isArray(parsed.result)) {
      jsonError.value = 'Invalid JSON structure: missing "result" array'
      return false
    }
    
    if (parsed.result.length === 0) {
      jsonError.value = 'The "result" array is empty'
      return false
    }
    
    return true
  } catch (e) {
    jsonError.value = `Invalid JSON syntax: ${e.message}`
    return false
  }
}

const switchMode = (mode) => {
  inputMode.value = mode
  jsonError.value = ''
}

const submitImport = () => {
  if (inputMode.value === 'file') {
    if (!form.json_file) {
      alert('Please select a JSON file to import')
      return
    }
    
    form.raw_json = null
    form.post('/market-data/import', {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
        if (fileInput.value) {
          fileInput.value.value = ''
        }
      }
    })
  } else {
    if (!validateRawJson()) {
      return
    }
    
    form.json_file = null
    form.raw_json = rawJsonInput.value
    form.post('/market-data/import', {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
        rawJsonInput.value = ''
        jsonError.value = ''
      }
    })
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Never'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>

<template>
  <Head title="Card Scanner - Market Data" />

  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Market Data Management</h1>
        <p class="text-gray-400">Import and manage card market price data</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatsCard
          title="Total Sets"
          :value="stats.total_sets"
        />
        <StatsCard
          title="Market Cards"
          :value="stats.total_cards"
          color="text-blue-400"
        />
        <StatsCard
          title="Price Records"
          :value="stats.total_prices"
          color="text-green-400"
        />
        <StatsCard
          title="Import Sessions"
          :value="stats.unique_import_dates"
          :subtitle="formatDate(stats.latest_import)"
        />
      </div>

      <!-- Import Section -->
      <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-8">
        <h2 class="text-2xl font-bold mb-6">Import Market Data</h2>

        <!-- Input Mode Tabs -->
        <div class="flex border-b border-gray-700 mb-6">
          <button
            @click="switchMode('file')"
            :class="[
              'px-6 py-3 font-medium text-sm transition-all duration-200 border-b-2 -mb-px',
              inputMode === 'file' 
                ? 'text-blue-400 border-blue-400' 
                : 'text-gray-400 border-transparent hover:text-gray-300'
            ]"
          >
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              Upload File
            </span>
          </button>
          <button
            @click="switchMode('raw')"
            :class="[
              'px-6 py-3 font-medium text-sm transition-all duration-200 border-b-2 -mb-px',
              inputMode === 'raw' 
                ? 'text-blue-400 border-blue-400' 
                : 'text-gray-400 border-transparent hover:text-gray-300'
            ]"
          >
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
              Paste JSON
            </span>
          </button>
        </div>

        <!-- File Upload Mode -->
        <div v-if="inputMode === 'file'">
          <div
            @drop.prevent="handleDrop"
            @dragover.prevent="handleDragOver"
            @dragleave="handleDragLeave"
            :class="[
              'border-2 border-dashed rounded-lg p-12 text-center transition-all duration-200',
              isDragging ? 'border-blue-500 bg-blue-500 bg-opacity-10' : 'border-gray-600 hover:border-gray-500',
              form.json_file ? 'bg-gray-700' : ''
            ]"
          >
            <!-- File Selected State -->
            <div v-if="form.json_file" class="space-y-4">
              <svg class="w-16 h-16 mx-auto text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p class="text-lg font-semibold mb-1">{{ form.json_file.name }}</p>
                <p class="text-sm text-gray-400">{{ (form.json_file.size / 1024).toFixed(2) }} KB</p>
              </div>
              <button
                @click="removeFile"
                type="button"
                class="text-red-400 hover:text-red-300 text-sm"
              >
                Remove file
              </button>
            </div>

            <!-- Empty State -->
            <div v-else class="space-y-4">
              <svg class="w-16 h-16 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <div>
                <p class="text-lg mb-2">Drag and drop your JSON file here</p>
                <p class="text-sm text-gray-400 mb-4">or</p>
                <label class="cursor-pointer">
                  <span class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg inline-block transition-colors duration-200">
                    Browse Files
                  </span>
                  <input
                    ref="fileInput"
                    type="file"
                    accept=".json,application/json"
                    @change="handleFileSelect"
                    class="hidden"
                  />
                </label>
              </div>
              <p class="text-xs text-gray-500">Maximum file size: 10MB</p>
            </div>
          </div>
        </div>

        <!-- Raw JSON Mode -->
        <div v-else>
          <div class="space-y-4">
            <div class="relative">
              <textarea
                v-model="rawJsonInput"
                @blur="validateRawJson"
                placeholder='{"result": [...]}'
                :class="[
                  'w-full h-64 bg-gray-900 border rounded-lg px-4 py-3 text-white font-mono text-sm resize-none focus:outline-none transition-colors',
                  jsonError ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-blue-500'
                ]"
                spellcheck="false"
              ></textarea>
              
              <!-- Character count and actions -->
              <div class="flex items-center justify-between mt-2">
                <div class="flex items-center gap-4">
                  <span class="text-xs text-gray-500">
                    {{ rawJsonCharCount.toLocaleString() }} characters
                  </span>
                  <span v-if="isRawJsonValid" class="text-xs text-green-400 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Valid JSON
                  </span>
                </div>
                <button
                  v-if="rawJsonInput"
                  @click="clearRawJson"
                  type="button"
                  class="text-red-400 hover:text-red-300 text-sm"
                >
                  Clear
                </button>
              </div>
            </div>

            <!-- Validation Error -->
            <div v-if="jsonError" class="bg-red-900 bg-opacity-30 border border-red-700 rounded-lg px-4 py-3">
              <p class="text-red-400 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ jsonError }}
              </p>
            </div>

            <p class="text-xs text-gray-500">
              Paste your JSON data directly. Must contain a "result" array with card objects.
            </p>
          </div>
        </div>

        <!-- Import Button -->
        <div class="mt-6 flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-400">
              The JSON should contain market card data with the "result" array structure.
            </p>
          </div>
          <button
            @click="submitImport"
            :disabled="!canSubmit || form.processing"
            :class="[
              'px-8 py-3 rounded-lg font-semibold transition-all duration-200',
              canSubmit && !form.processing
                ? 'bg-green-600 hover:bg-green-700 text-white'
                : 'bg-gray-700 text-gray-500 cursor-not-allowed'
            ]"
          >
            <span v-if="form.processing" class="flex items-center gap-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              Importing...
            </span>
            <span v-else>Import Data</span>
          </button>
        </div>

        <!-- Progress Bar (when processing) -->
        <div v-if="form.processing" class="mt-4">
          <div class="w-full bg-gray-700 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-blue-500 animate-pulse" style="width: 100%"></div>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h3 class="text-lg font-bold mb-4">Import Instructions</h3>
        <ol class="space-y-3 text-gray-300">
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold">1</span>
            <div>
              <p class="font-semibold">Prepare your JSON file</p>
              <p class="text-sm text-gray-400">The file must contain a "result" array with card data objects</p>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold">2</span>
            <div>
              <p class="font-semibold">Upload the file</p>
              <p class="text-sm text-gray-400">Drag and drop or browse to select your JSON file</p>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold">3</span>
            <div>
              <p class="font-semibold">Import data</p>
              <p class="text-sm text-gray-400">Click "Import Data" to process the file. This may take a few moments.</p>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-sm font-bold">4</span>
            <div>
              <p class="font-semibold">Verify import</p>
              <p class="text-sm text-gray-400">Check the statistics above to confirm the data was imported successfully</p>
            </div>
          </li>
        </ol>

        <!-- Expected JSON Structure -->
        <div class="mt-6 bg-gray-900 rounded p-4">
          <p class="text-sm text-gray-400 mb-2">Expected JSON structure:</p>
          <pre class="text-xs text-gray-500 overflow-x-auto"><code>{
  "count": 544,
  "total": 544,
  "result": [
    {
      "productID": 123456,
      "productName": "Charizard",
      "number": "006/165",
      "set": "Stellar Crown",
      "setAbbrv": "SCR",
      "rarity": "Rare Holo",
      "marketPrice": 25.99,
      "lowPrice": 20.00,
      "condition": "Near Mint",
      "printing": "Holofoil",
      ...
    }
  ]
}</code></pre>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
