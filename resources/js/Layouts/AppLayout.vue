<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const currentUrl = computed(() => page.url)
const user = computed(() => page.props.auth?.user || null)

const isActive = (path) => {
  return currentUrl.value.startsWith(path)
}

// Mobile menu state
const mobileMenuOpen = ref(false)
const userDropdownOpen = ref(false)

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
  if (mobileMenuOpen.value) userDropdownOpen.value = false
}

const toggleUserDropdown = () => {
  userDropdownOpen.value = !userDropdownOpen.value
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}
</script>

<template>
  <div class="min-h-screen app-wrapper">
    <!-- Navbar Pokemon Style -->
    <nav class="navbar-pokemon">
      <div class="container">
        <div class="navbar-content">
          <!-- Logo -->
          <Link href="/cards/upload" class="navbar-brand">
            <span class="scanner-logo"></span>
            <span class="brand-text">Card Scanner</span>
          </Link>

          <!-- Hamburger Button (Mobile) -->
          <button class="navbar-toggler" @click="toggleMobileMenu" :class="{ active: mobileMenuOpen }">
            <span class="toggler-icon"></span>
          </button>

          <!-- Nav Links (Desktop) -->
          <div class="nav-links desktop-only">
            <a href="/cards/upload" :class="['nav-link', { active: isActive('/cards/upload') }]">
              <i class="bi bi-camera-fill"></i> Scansiona
            </a>
            <a href="/cards" :class="['nav-link', { active: currentUrl === '/cards' || currentUrl.startsWith('/cards?') }]">
              <i class="bi bi-collection-fill"></i> Collezione
            </a>
            <Link href="/collection/value" :class="['nav-link', { active: isActive('/collection') }]">
              <i class="bi bi-currency-dollar"></i> Valore
            </Link>
            <Link href="/matching" :class="['nav-link', { active: isActive('/matching') }]">
              <i class="bi bi-link-45deg"></i> Matching
            </Link>
            <a href="/market-data" :class="['nav-link', { active: isActive('/market-data') }]">
              <i class="bi bi-cloud-upload"></i> Market Data
            </a>
          </div>

          <!-- User Dropdown (Desktop) -->
          <div v-if="user" class="user-dropdown desktop-only" @click="toggleUserDropdown">
            <div class="user-avatar">
              <img v-if="user.avatar" :src="user.avatar_url" :alt="user.name" class="avatar-img">
              <div v-else class="avatar-placeholder">
                <i class="bi bi-person-fill"></i>
              </div>
            </div>
            <span class="user-name">{{ user.name }}</span>
            <i class="bi bi-chevron-down"></i>
            
            <!-- Dropdown Menu -->
            <div v-show="userDropdownOpen" class="dropdown-menu">
              <a href="/profile" class="dropdown-item">
                <i class="bi bi-person"></i> Il Mio Profilo
              </a>
              <hr class="dropdown-divider">
              <Link href="/logout" method="post" as="button" class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right"></i> Esci
              </Link>
            </div>
          </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
          <a href="/cards/upload" :class="['nav-link', { active: isActive('/cards/upload') }]" @click="closeMobileMenu">
            <i class="bi bi-camera-fill"></i> Scansiona
          </a>
          <a href="/cards" :class="['nav-link', { active: currentUrl === '/cards' }]" @click="closeMobileMenu">
            <i class="bi bi-collection-fill"></i> Collezione
          </a>
          <Link href="/collection/value" :class="['nav-link', { active: isActive('/collection') }]" @click="closeMobileMenu">
            <i class="bi bi-currency-dollar"></i> Valore
          </Link>
          <Link href="/matching" :class="['nav-link', { active: isActive('/matching') }]" @click="closeMobileMenu">
            <i class="bi bi-link-45deg"></i> Matching
          </Link>
          <a href="/market-data" :class="['nav-link', { active: isActive('/market-data') }]" @click="closeMobileMenu">
            <i class="bi bi-cloud-upload"></i> Market Data
          </a>
          
          <hr class="mobile-divider" v-if="user">
          
          <template v-if="user">
            <a href="/profile" class="nav-link" @click="closeMobileMenu">
              <i class="bi bi-person"></i> Il Mio Profilo
            </a>
            <Link href="/logout" method="post" as="button" class="nav-link text-danger" @click="closeMobileMenu">
              <i class="bi bi-box-arrow-right"></i> Esci
            </Link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="main-container">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="app-footer">
      <div class="container">
        <div class="footer-content">
          <p>
            <i class="bi bi-stars"></i>
            <strong>Card Scanner</strong> - AI powered by Google Gemini
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
.app-wrapper {
  background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

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

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
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

.scanner-logo {
  width: 32px;
  height: 40px;
  position: relative;
  display: inline-block;
  background: rgba(255,255,255,0.1);
  border-radius: 6px;
  border: 2px solid #FFCB05;
  overflow: hidden;
  box-shadow: 0 0 10px rgba(255, 203, 5, 0.2);
}

.scanner-logo::before {
  content: '';
  position: absolute;
  top: 4px;
  left: 4px;
  right: 4px;
  bottom: 4px;
  background: rgba(255,255,255,0.05);
  border-radius: 2px;
}

.scanner-logo::after {
  content: '';
  position: absolute;
  width: 120%;
  height: 2px;
  background: #CC0000;
  box-shadow: 0 0 8px #CC0000;
  top: 0;
  left: -10%;
  animation: scan-vertical 2s linear infinite;
}

@keyframes scan-vertical {
  0% { top: 0; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 100%; opacity: 0; }
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
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-link:hover {
  color: #FFCB05;
  background: rgba(255, 203, 5, 0.1);
}

.nav-link.active {
  color: #FFCB05;
  background: rgba(255, 203, 5, 0.15);
}

.nav-link.text-danger {
  color: #ff4444;
}

/* User Dropdown */
.user-dropdown {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 10px;
  transition: background 0.3s;
}

.user-dropdown:hover {
  background: rgba(255, 255, 255, 0.1);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: #FFCB05;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #000;
}

.user-name {
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: rgba(30, 35, 60, 0.98);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  min-width: 180px;
  padding: 8px 0;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: background 0.2s;
  border: none;
  background: none;
  width: 100%;
  cursor: pointer;
  font-size: 0.95rem;
}

.dropdown-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}

.dropdown-item.text-danger {
  color: #ff4444;
}

.dropdown-divider {
  border: none;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin: 8px 0;
}

/* Hamburger Toggle */
.navbar-toggler {
  display: none;
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  width: 40px;
  height: 36px;
  position: relative;
}

.toggler-icon,
.toggler-icon::before,
.toggler-icon::after {
  display: block;
  width: 22px;
  height: 2px;
  background: #FFCB05;
  position: absolute;
  transition: all 0.3s;
}

.toggler-icon {
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.toggler-icon::before {
  content: '';
  top: -7px;
  left: 0;
}

.toggler-icon::after {
  content: '';
  top: 7px;
  left: 0;
}

.navbar-toggler.active .toggler-icon {
  background: transparent;
}

.navbar-toggler.active .toggler-icon::before {
  top: 0;
  transform: rotate(45deg);
}

.navbar-toggler.active .toggler-icon::after {
  top: 0;
  transform: rotate(-45deg);
}

/* Mobile Menu */
.mobile-menu {
  display: none;
  flex-direction: column;
  padding: 1rem 0;
  margin-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.mobile-menu.open {
  display: flex;
}

.mobile-divider {
  border: none;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin: 10px 0;
}

/* Main Content */
.main-container {
  padding-top: 100px;
  padding-bottom: 80px;
  color: #fff;
  flex: 1;
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

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .desktop-only {
    display: none !important;
  }
  
  .navbar-toggler {
    display: block;
  }
  
  .mobile-menu .nav-link {
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }
}

@media (min-width: 769px) {
  .mobile-menu {
    display: none !important;
  }
}

@media (max-width: 576px) {
  .footer-content {
    padding: 0 1rem;
  }
}
</style>
