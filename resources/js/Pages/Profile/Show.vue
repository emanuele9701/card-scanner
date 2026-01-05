<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    user: Object
});
</script>

<template>
    <AppLayout>
        <Head title="Card Scanner - Il Mio Profilo" />
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="page-title">Il Mio Profilo</h1>
                <p class="page-subtitle">Visualizza le tue informazioni personali</p>
            </div>

            <div class="glass-card p-4" style="max-width: 800px; margin: 0 auto;">
                <div class="row g-4">
                    <div class="col-12 text-center">
                        <div class="avatar-circle-large mb-3">
                            {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                        <h2 class="text-white">{{ user.name }}</h2>
                        <p class="text-white-50">{{ user.email }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-warning fw-bold">Nome</label>
                        <p class="text-white">{{ user.first_name || 'Non impostato' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-warning fw-bold">Cognome</label>
                        <p class="text-white">{{ user.last_name || 'Non impostato' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-warning fw-bold">Data di nascita</label>
                        <p class="text-white">{{ user.birth_date || 'Non impostata' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-warning fw-bold">Telefono</label>
                        <p class="text-white">{{ user.phone || 'Non impostato' }}</p>
                    </div>
                    <div class="col-12 text-center mt-4">
                        <Link href="/profile/edit" class="btn btn-pokemon">
                            <i class="bi bi-pencil"></i> Modifica Profilo
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: #FFCB05;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.6);
}

.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
}

.avatar-circle-large {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #FFCB05, #f39c12);
    color: #000;
    font-weight: bold;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.btn-pokemon {
    background: linear-gradient(135deg, #FFCB05 0%, #f39c12 100%);
    border: none;
    color: #000;
    font-weight: 600;
    padding: 10px 25px;
    border-radius: 50px;
    transition: transform 0.3s ease;
}

.btn-pokemon:hover {
    transform: scale(1.05);
}
</style>
