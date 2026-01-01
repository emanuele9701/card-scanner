<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = computed(() => page.url)

const isActive = (path) => {
  return currentUrl.value.startsWith(path)
}
</script>

<template>
  <div class="min-h-screen" style="background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);">
    <!-- Navbar Pokemon Style -->
    <nav class="navbar-pokemon">
      <div class="container">
        <div class="navbar-content">
          <!-- Logo -->
          <Link href="/collection/value" class="navbar-brand">
            <span class="pokeball-logo"></span>
            <span class="brand-text">Pokemon Card Scanner</span>
          </Link>

          <!-- Nav Links -->
          <div class="nav-links">
            <a href="/cards/upload" :class="['nav-link', { active: isActive('/cards/upload') }]">
              <i class="bi bi-camera-fill"></i> Scansiona
            </a>
            <a href="/cards" :class="['nav-link', { active: isActive('/cards') }]">
              <i class="bi bi-collection-fill"></i> Collezione
            </a>
            <Link href="/collection/value" :class="['nav-link', { active: isActive('/collection') }]">
              <i class="bi bi-currency-dollar"></i> Valore
            </Link>
            <Link href="/matching" :class="['nav-link', { active: isActive('/matching') }]">
              <i class="bi bi-link-45deg"></i> Matching
            </Link>
            <Link href="/market-data" :class="['nav-link', { active: isActive('/market-data') }]">
              <i class="bi bi-cloud-upload"></i> Market Data
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="main-container">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.navbar-pokemon {
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem 0;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}

.navbar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  color: #FFCB05;
  font-size: 1.4rem;
  text-decoration: none;
  transition: transform 0.3s ease;
}

.navbar-brand:hover {
  transform: scale(1.02);
}

.pokeball-logo {
  width: 36px;
  height: 36px;
  position: relative;
  display: inline-block;
}

.pokeball-logo::before {
  content: '';
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(to bottom, #CC0000 45%, #222 45%, #222 55%, #fff 55%);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.pokeball-logo::after {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  background: #fff;
  border: 3px solid #222;
  border-radius: 50%;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.nav-links {
  display: flex;
  gap: 0.5rem;
}

.nav-link {
  color: rgba(255, 255, 255, 0.8);
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.3s ease;
}

.nav-link:hover {
  color: #FFCB05;
  background: rgba(255, 203, 5, 0.1);
}

.nav-link.active {
  color: #FFCB05;
  background: rgba(255, 203, 5, 0.15);
}

.main-container {
  padding-top: 100px;
  padding-bottom: 80px;
  color: #fff;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .nav-links {
    display: none;
  }
}
</style>
