<script setup>
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: Boolean,
  maxWidth: {
    type: String,
    default: '2xl'
  }
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
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <!-- Backdrop -->
          <div 
            class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" 
            @click="close"
          />

          <!-- Modal Content -->
          <Transition
            enter-active-class="transition duration-200 ease-out transform"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition duration-150 ease-in transform"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div 
              v-if="show"
              :class="[
                'relative inline-block align-bottom bg-gray-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full',
                maxWidth === 'sm' ? 'sm:max-w-sm' : '',
                maxWidth === 'md' ? 'sm:max-w-md' : '',
                maxWidth === 'lg' ? 'sm:max-w-lg' : '',
                maxWidth === 'xl' ? 'sm:max-w-xl' : '',
                maxWidth === '2xl' ? 'sm:max-w-2xl' : '',
                maxWidth === '4xl' ? 'sm:max-w-4xl' : '',
              ]"
            >
              <!-- Close button -->
              <button
                @click="close"
                class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors z-10"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <!-- Modal slot -->
              <div class="bg-gray-900 px-4 pt-5 pb-4 sm:p-6">
                <slot />
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
