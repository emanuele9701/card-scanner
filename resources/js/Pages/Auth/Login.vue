<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const passwordVisible = ref(false);

const togglePassword = () => {
    passwordVisible.value = !passwordVisible.value;
};

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Card Scanner - Accedi" />
        
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="scanner-logo-wrapper mb-4 text-center">
                        <div class="scanner-logo"></div>
                    </div>
                    <h1 class="auth-title">Bentornato!</h1>
                    <p class="auth-subtitle">Accedi alla tua collezione di carte da gioco.</p>
                </div>

                <div v-if="$page.props.flash?.success" class="alert-auth">
                    <i class="bi bi-check-circle me-2"></i>{{ $page.props.flash.success }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-floating">
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.email }"
                            placeholder="email@example.com"
                            required
                            autofocus
                        >
                        <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
                        <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
                    </div>

                    <div class="form-floating position-relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="passwordVisible ? 'text' : 'password'"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.password }"
                            placeholder="Password"
                            required
                        >
                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                        <button type="button" class="password-toggle" @click="togglePassword">
                            <i class="bi" :class="passwordVisible ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                        <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                    </div>

                    <div class="remember-check">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                        >
                        <label for="remember">Ricordami</label>
                    </div>

                    <button type="submit" class="btn btn-pokemon w-100 py-3" :disabled="form.processing">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Accedi
                    </button>
                </form>

                <div class="auth-divider">oppure</div>

                <p class="text-center text-white-50 mb-0">
                    Non hai un account?
                    <Link href="/register" class="auth-link">Registrati</Link>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.auth-container {
    max-width: 450px;
    margin: 0 auto;
    padding-top: 2rem;
}

.auth-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2.5rem;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.scanner-logo-wrapper {
    display: flex;
    justify-content: center;
}

.scanner-logo {
  width: 60px;
  height: 75px;
  position: relative;
  display: inline-block;
  background: rgba(255,255,255,0.1);
  border-radius: 12px;
  border: 4px solid #FFCB05;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(255, 203, 5, 0.4);
}

.scanner-logo::before {
  content: '';
  position: absolute;
  top: 6px;
  left: 6px;
  right: 6px;
  bottom: 6px;
  background: rgba(255,255,255,0.05);
  border-radius: 4px;
}

.scanner-logo::after {
  content: '';
  position: absolute;
  width: 120%;
  height: 4px;
  background: #CC0000;
  box-shadow: 0 0 15px #CC0000;
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

.auth-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

.form-floating {
    margin-bottom: 1rem;
}

.form-floating > .form-control {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    color: #fff;
    height: 56px;
    padding: 1rem 1rem;
}

.form-floating > .form-control:focus {
    background: rgba(0, 0, 0, 0.4);
    border-color: #FFCB05;
    box-shadow: 0 0 0 3px rgba(255, 203, 5, 0.2);
}

.form-floating > label {
    color: rgba(255, 255, 255, 0.5);
    padding: 1rem;
}

.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: #FFCB05;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    z-index: 10;
}

.password-toggle:hover {
    color: #FFCB05;
}

.remember-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.remember-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(0, 0, 0, 0.3);
    cursor: pointer;
    accent-color: #FFCB05;
}

.remember-check label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    cursor: pointer;
}

.auth-divider {
    display: flex;
    align-items: center;
    margin: 1.5rem 0;
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.85rem;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
}

.auth-divider::before { margin-right: 1rem; }
.auth-divider::after { margin-left: 1rem; }

.auth-link {
    color: #FFCB05;
    text-decoration: none;
    font-weight: 500;
}

.auth-link:hover {
    color: #FFE066;
    text-decoration: underline;
}

.invalid-feedback {
    color: #ff4444;
    display: block;
    font-size: 0.875em;
    margin-top: 0.25rem;
}

.alert-auth {
    background: rgba(40, 167, 69, 0.2);
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 12px;
    color: #28a745;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.btn-pokemon {
    background: linear-gradient(135deg, #FFCB05 0%, #f39c12 100%);
    border: none;
    color: #000;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 203, 5, 0.3);
    border-radius: 50px; /* Matching AppLayout buttons */
}

.btn-pokemon:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 6px 25px rgba(255, 203, 5, 0.5);
}

.btn-pokemon:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>
