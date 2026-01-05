<script setup>
import { useForm} from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object
});

const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    birth_date: props.user.birth_date || '',
    phone: props.user.phone || '',
});

const submit = () => {
    form.put(route('profile.update'));
};
</script>

<template>
    <AppLayout>
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="page-title">Modifica Profilo</h1>
                <p class="page-subtitle">Aggiorna le tue informazioni personali</p>
            </div>

            <div class="glass-card p-4" style="max-width: 800px; margin: 0 auto;">
                <form @submit.prevent="submit">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-warning">Nome</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" v-model="form.first_name">
                            <div v-if="form.errors.first_name" class="text-danger mt-1">{{ form.errors.first_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Cognome</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" v-model="form.last_name">
                            <div v-if="form.errors.last_name" class="text-danger mt-1">{{ form.errors.last_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Data di nascita</label>
                            <input type="date" class="form-control bg-dark text-white border-secondary" v-model="form.birth_date">
                            <div v-if="form.errors.birth_date" class="text-danger mt-1">{{ form.errors.birth_date }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-warning">Telefono</label>
                            <input type="tel" class="form-control bg-dark text-white border-secondary" v-model="form.phone">
                            <div v-if="form.errors.phone" class="text-danger mt-1">{{ form.errors.phone }}</div>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <a :href="route('profile.show')" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-left"></i> Annulla
                            </a>
                            <button type="submit" class="btn btn-pokemon" :disabled="form.processing">
                                <i class="bi bi-check-lg"></i> Salva Modifiche
                            </button>
                        </div>
                    </div>
                </form>
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
