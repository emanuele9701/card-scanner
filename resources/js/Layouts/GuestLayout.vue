<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <div class="guest-wrapper">
    <!-- Demo Warning Banner -->
    <div class="demo-banner">
      <div class="container banner-content">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>ATTENZIONE: Ogni giorno a mezzanotte viene eseguita la pulizia del DB e dello storage (Ambiente Demo).</span>
      </div>
    </div>

    <!-- Main Content -->
    <main class="guest-container">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="app-footer">
      <div class="container">
        <div class="footer-content">
          <p>
            <i class="bi bi-stars"></i>
            <strong>Card Scanner</strong>
          </p>
          <p class="footer-version">
            v1.0.0 • Made with <i class="bi bi-heart-fill text-danger"></i> for collectors
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.guest-wrapper {
  background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Demo Banner */
.demo-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: linear-gradient(90deg, #ff9966, #ff5e62);
  color: white;
  z-index: 1001;
  font-size: 0.85rem;
  font-weight: 600;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.banner-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-align: center;
  line-height: 1.2;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Guest Content */
.guest-container {
  padding-top: 60px; /* Only banner offset */
  padding-bottom: 80px;
  color: #fff;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Footer */
.app-footer {
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1.5rem 0;
  margin-top: auto;
}

.footer-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
}

.app-footer p {
  margin: 0;
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.875rem;
}

.footer-version {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.3);
}

.text-danger {
  color: #ff4444;
}

@media (max-width: 576px) {
  .footer-content {
    padding: 0 1rem;
  }
}
</style>
