<script setup>
defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  trend: String, // 'up', 'down', or null
  color: String // Tailwind color class
})
</script>

<template>
  <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-gray-600 transition-colors">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-gray-400 text-sm mb-2">{{ title }}</p>
        <p :class="['text-3xl font-bold', color || 'text-white']">{{ value }}</p>
        <p v-if="subtitle" class="text-gray-500 text-xs mt-1">{{ subtitle }}</p>
      </div>
      <div v-if="trend" class="ml-4">
        <span v-if="trend === 'up'" class="text-green-400 text-2xl">↑</span>
        <span v-if="trend === 'down'" class="text-red-400 text-2xl">↓</span>
      </div>
    </div>
  </div>
</template>
