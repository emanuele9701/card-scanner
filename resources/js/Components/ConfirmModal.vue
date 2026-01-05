<script setup>
import { computed } from 'vue'
import { useModal } from '@/composables/useModal'

const { modalState, closeModal } = useModal()

const iconClass = computed(() => {
  switch (modalState.value.type) {
    case 'success':
      return 'bi-check-circle-fill text-success'
    case 'error':
      return 'bi-exclamation-circle-fill text-danger'
    case 'warning':
      return 'bi-exclamation-triangle-fill text-warning'
    case 'confirm':
      return 'bi-question-circle-fill text-warning'
    default:
      return 'bi-info-circle-fill text-info'
  }
})

const handleConfirm = () => {
  if (modalState.value.onConfirm) {
    modalState.value.onConfirm()
  }
}

const handleCancel = () => {
  if (modalState.value.onCancel) {
    modalState.value.onCancel()
  } else {
    closeModal()
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="modalState.isOpen" class="modal-overlay" @click.self="handleCancel">
        <Transition name="modal-slide">
          <div v-if="modalState.isOpen" class="modal-container">
            <div class="modal-header">
              <div class="modal-icon">
                <i :class="['bi', iconClass]"></i>
              </div>
              <h3 class="modal-title">{{ modalState.title }}</h3>
            </div>
            
            <div class="modal-body">
              <p>{{ modalState.message }}</p>
            </div>
            
            <div class="modal-footer">
              <button 
                v-if="modalState.cancelText" 
                class="btn btn-secondary" 
                @click="handleCancel"
              >
                {{ modalState.cancelText }}
              </button>
              <button 
                class="btn btn-primary" 
                :class="{
                  'btn-success': modalState.type === 'success',
                  'btn-danger': modalState.type === 'error',
                  'btn-warning': modalState.type === 'warning' || modalState.type === 'confirm'
                }"
                @click="handleConfirm"
              >
                {{ modalState.confirmText }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-container {
  background: linear-gradient(135deg, #1e233c 0%, #2a3050 100%);
  border: 1px solid rgba(255, 203, 5, 0.3);
  border-radius: 16px;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.modal-header {
  padding: 24px 24px 16px;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.modal-title {
  margin: 0;
  color: #FFCB05;
  font-size: 1.5rem;
  font-weight: 600;
}

.modal-body {
  padding: 24px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  line-height: 1.6;
  text-align: center;
}

.modal-body p {
  margin: 0;
}

.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  gap: 12px;
  justify-content: center;
}

.btn {
  padding: 10px 24px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 100px;
}

.btn-primary {
  background: linear-gradient(135deg, #FFCB05 0%, #f39c12 100%);
  color: #000;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(255, 203, 5, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: #fff;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

.btn-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: #fff;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}

.btn-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #fff;
}

.btn-warning:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-slide-enter-active,
.modal-slide-leave-active {
  transition: all 0.3s ease;
}

.modal-slide-enter-from {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.modal-slide-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
