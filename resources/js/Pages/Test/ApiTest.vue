<script setup>
import { ref, reactive, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

// Token management
const authToken = ref(localStorage.getItem('api_test_token') || '');
const currentUser = ref(null);

// Active section
const activeSection = ref('auth');

// Loading states
const loading = ref({});

// Response states
const responses = ref({});

// Form data for each endpoint
const forms = reactive({
    register: {
        name: 'Test User',
        email: 'test@example.com',
        password: 'password123',
        password_confirmation: 'password123'
    },
    login: {
        email: 'test@example.com',
        password: 'password123'
    },
    analyze: {
        image: null,
        imagePreview: null
    },
    confirm: {
        card_id: '',
        card_name: '',
        hp: '',
        type: '',
        evolution_stage: '',
        rarity: '',
        set_number: '',
        illustrator: '',
        game: ''
    },
    deleteCard: {
        card_id: ''
    },
    updateCard: {
        card_id: '',
        card_name: '',
        hp: '',
        type: ''
    },
    updateCondition: {
        card_id: '',
        condition: 'Near Mint'
    },
    updateSet: {
        card_id: '',
        card_set_id: ''
    },
    removeSet: {
        card_id: ''
    },
    destroyCard: {
        card_id: ''
    },
    showSet: {
        set_id: ''
    },
    suggestions: {
        card_id: ''
    },
    match: {
        card_id: '',
        card_set_id: ''
    },
    importMarketData: {
        data: ''
    }
});

// Lists for display
const cardsList = ref([]);
const unmatchedCardsList = ref([]);
const setsList = ref([]);
const gamesList = ref([]);
const conditionsList = ref([]);

// Axios interceptor to add token
axios.interceptors.request.use(config => {
    if (authToken.value && config.url.startsWith('/api/')) {
        config.headers.Authorization = `Bearer ${authToken.value}`;
    }
    return config;
});

// Utility functions
const setToken = (token) => {
    authToken.value = token;
    localStorage.setItem('api_test_token', token);
};

const clearToken = () => {
    authToken.value = '';
    currentUser.value = null;
    localStorage.removeItem('api_test_token');
};

const handleApiCall = async (key, apiCall) => {
    loading.value[key] = true;
    responses.value[key] = null;
    
    try {
        const response = await apiCall();
        responses.value[key] = {
            success: true,
            status: response.status,
            data: response.data
        };
        return response;
    } catch (error) {
        responses.value[key] = {
            success: false,
            status: error.response?.status || 500,
            data: error.response?.data || { message: error.message }
        };
        throw error;
    } finally {
        loading.value[key] = false;
    }
};

// API Calls
// 1. Authentication
const register = async () => {
    await handleApiCall('register', () => axios.post('/api/auth/register', forms.register));
};

const login = async () => {
    const response = await handleApiCall('login', () => axios.post('/api/auth/login', forms.login));
    if (response.data.token) {
        setToken(response.data.token);
        currentUser.value = response.data.user;
    }
};

const getUser = async () => {
    const response = await handleApiCall('getUser', () => axios.get('/api/auth/user'));
    if (response.data.user) {
        currentUser.value = response.data.user;
    }
};

const logout = async () => {
    await handleApiCall('logout', () => axios.post('/api/auth/logout'));
    clearToken();
};

// 2. Card Analysis & Management
const analyzeCard = async () => {
    if (!forms.analyze.image) {
        alert('Seleziona un\'immagine');
        return;
    }
    
    const formData = new FormData();
    formData.append('image', forms.analyze.image);
    
    const response = await handleApiCall('analyze', () => axios.post('/api/card/analyze', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }));
    
    if (response.data.data) {
        // Pre-fill confirm form with analyzed data
        const data = response.data.data;
        forms.confirm.card_id = data.id || '';
        forms.confirm.card_name = data.card_name || '';
        forms.confirm.hp = data.hp || '';
        forms.confirm.type = data.type || '';
        forms.confirm.evolution_stage = data.evolution_stage || '';
        forms.confirm.rarity = data.rarity || '';
        forms.confirm.set_number = data.set_number || '';
        forms.confirm.illustrator = data.illustrator || '';
        forms.confirm.game = data.game || '';
    }
};

const confirmCard = async () => {
    await handleApiCall('confirm', () => axios.post('/api/card/confirm', forms.confirm));
};

const deleteAnalyzedCard = async () => {
    await handleApiCall('deleteCard', () => axios.delete('/api/card/delete', {
        data: { card_id: forms.deleteCard.card_id }
    }));
};

// 3. Collection
const getCards = async () => {
    const response = await handleApiCall('getCards', () => axios.get('/api/collection/cards'));
    if (response.data.data) {
        cardsList.value = response.data.data;
    }
};

const getUnmatchedCards = async () => {
    const response = await handleApiCall('getUnmatchedCards', () => axios.get('/api/collection/cards/unmatched'));
    if (response.data.data) {
        unmatchedCardsList.value = response.data.data;
    }
};

const getGames = async () => {
    const response = await handleApiCall('getGames', () => axios.get('/api/collection/games'));
    if (response.data.data) {
        gamesList.value = response.data.data;
    }
};

const updateCard = async () => {
    await handleApiCall('updateCard', () => axios.post(`/api/collection/cards/${forms.updateCard.card_id}`, forms.updateCard));
};

const getConditions = async () => {
    const response = await handleApiCall('getConditions', () => axios.get(`/api/collection/cards/${forms.updateCondition.card_id}/conditions`));
    if (response.data.data) {
        conditionsList.value = response.data.data;
    }
};

const updateCondition = async () => {
    await handleApiCall('updateCondition', () => axios.post(`/api/collection/cards/${forms.updateCondition.card_id}/condition`, {
        condition: forms.updateCondition.condition
    }));
};

const updateSetAssociation = async () => {
    await handleApiCall('updateSet', () => axios.post(`/api/collection/cards/${forms.updateSet.card_id}/set`, {
        card_set_id: forms.updateSet.card_set_id
    }));
};

const removeSetAssociation = async () => {
    await handleApiCall('removeSet', () => axios.delete(`/api/collection/cards/${forms.removeSet.card_id}/set`));
};

const destroyCard = async () => {
    await handleApiCall('destroyCard', () => axios.delete(`/api/collection/cards/${forms.destroyCard.card_id}`));
};

// 4. Card Sets
const getSets = async () => {
    const response = await handleApiCall('getSets', () => axios.get('/api/sets'));
    if (response.data.data) {
        setsList.value = response.data.data;
    }
};

const showSet = async () => {
    await handleApiCall('showSet', () => axios.get(`/api/sets/${forms.showSet.set_id}`));
};

// 5. Matching
const getSuggestions = async () => {
    await handleApiCall('getSuggestions', () => axios.get(`/api/matching/cards/${forms.suggestions.card_id}/suggestions`));
};

const matchCard = async () => {
    await handleApiCall('matchCard', () => axios.post(`/api/matching/cards/${forms.match.card_id}/match`, {
        card_set_id: forms.match.card_set_id
    }));
};

const autoMatch = async () => {
    await handleApiCall('autoMatch', () => axios.post('/api/matching/auto-match'));
};

// 6. Market Data
const importMarketData = async () => {
    try {
        const data = JSON.parse(forms.importMarketData.data);
        await handleApiCall('importMarketData', () => axios.post('/api/market-data/import', data));
    } catch (error) {
        alert('Invalid JSON format');
    }
};

// Image handling
const handleImageSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        forms.analyze.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            forms.analyze.imagePreview = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Copy card ID helper
const copyCardId = (cardId) => {
    navigator.clipboard.writeText(cardId);
    alert('Card ID copiato: ' + cardId);
};
</script>

<template>
    <AppLayout>
        <Head title="API Test Page" />
        
        <div class="container py-5">
            <div class="text-center mb-5">
                <h1 class="page-title">🧪 API Test Page</h1>
                <p class="page-subtitle">Test completo di tutte le API seguendo il flusso logico dell'applicazione</p>
                
                <!-- Auth Status -->
                <div class="glass-card p-3 mt-3 d-inline-block">
                    <div v-if="authToken" class="text-success">
                        🟢 Autenticato {{ currentUser ? `come ${currentUser.name}` : '' }}
                        <button class="btn btn-sm btn-outline-danger ms-2" @click="clearToken">Clear Token</button>
                    </div>
                    <div v-else class="text-warning">
                        🔴 Non autenticato
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Token: {{ authToken ? authToken.substring(0, 20) + '...' : 'N/A' }}</small>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="glass-card p-3 mb-4">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button class="btn btn-sm" :class="activeSection === 'auth' ? 'btn-pokemon' : 'btn-secondary'" @click="activeSection = 'auth'">
                        🔐 Auth
                    </button>
                    <button class="btn btn-sm" :class="activeSection === 'card-management' ? 'btn-pokemon' : 'btn-secondary'" @click="activeSection = 'card-management'">
                        📤 Card Management
                    </button>
                    <button class="btn btn-sm" :class="activeSection === 'collection' ? 'btn-pokemon' : 'btn-secondary'" @click="activeSection = 'collection'">
                        📚 Collection
                    </button>
                    <button class="btn btn-sm" :class="activeSection === 'sets' ? 'btn-pokemon' : 'btn-secondary'" @click="activeSection = 'sets'">
                        🎴 Sets
                    </button>
                    <button class="btn btn-sm" :class="activeSection === 'matching' ? 'btn-pokemon' : 'btn-secondary'" @click="activeSection = 'matching'">
                        🔗 Matching
                    </button>
                    <button class="btn btn-sm" :class="activeSection === 'market' ? 'btn-pokemon' : 'btn-secondary'" @click="activeSection = 'market'">
                        💰 Market Data
                    </button>
                </div>
            </div>

            <!-- 1. AUTHENTICATION SECTION -->
            <div v-show="activeSection === 'auth'" class="section">
                <h2 class="section-title">🔐 Authentication</h2>
                
                <!-- Register -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/auth/register</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input v-model="forms.register.name" type="text" class="form-control" placeholder="Nome">
                        </div>
                        <div class="col-md-6">
                            <input v-model="forms.register.email" type="email" class="form-control" placeholder="Email">
                        </div>
                        <div class="col-md-6">
                            <input v-model="forms.register.password" type="password" class="form-control" placeholder="Password">
                        </div>
                        <div class="col-md-6">
                            <input v-model="forms.register.password_confirmation" type="password" class="form-control" placeholder="Conferma Password">
                        </div>
                    </div>
                    <button class="btn btn-pokemon mt-3" @click="register" :disabled="loading.register">
                        <span v-if="loading.register" class="spinner-border spinner-border-sm me-2"></span>
                        Register
                    </button>
                    <div v-if="responses.register" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.register, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Login -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/auth/login</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input v-model="forms.login.email" type="email" class="form-control" placeholder="Email">
                        </div>
                        <div class="col-md-6">
                            <input v-model="forms.login.password" type="password" class="form-control" placeholder="Password">
                        </div>
                    </div>
                    <button class="btn btn-pokemon mt-3" @click="login" :disabled="loading.login">
                        <span v-if="loading.login" class="spinner-border spinner-border-sm me-2"></span>
                        Login
                    </button>
                    <div v-if="responses.login" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.login, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Get User -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/auth/user</h4>
                    <button class="btn btn-pokemon" @click="getUser" :disabled="loading.getUser || !authToken">
                        <span v-if="loading.getUser" class="spinner-border spinner-border-sm me-2"></span>
                        Get User Info
                    </button>
                    <div v-if="responses.getUser" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getUser, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Logout -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/auth/logout</h4>
                    <button class="btn btn-danger" @click="logout" :disabled="loading.logout || !authToken">
                        <span v-if="loading.logout" class="spinner-border spinner-border-sm me-2"></span>
                        Logout
                    </button>
                    <div v-if="responses.logout" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.logout, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- 2. CARD MANAGEMENT SECTION -->
            <div v-show="activeSection === 'card-management'" class="section">
                <h2 class="section-title">📤 Card Management (Upload Flow)</h2>
                
                <!-- Analyze -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/card/analyze</h4>
                    <div class="mb-3">
                        <input type="file" class="form-control" accept="image/*" @change="handleImageSelect">
                        <div v-if="forms.analyze.imagePreview" class="mt-3">
                            <img :src="forms.analyze.imagePreview" alt="Preview" style="max-width: 200px; border-radius: 8px;">
                        </div>
                    </div>
                    <button class="btn btn-pokemon" @click="analyzeCard" :disabled="loading.analyze || !authToken">
                        <span v-if="loading.analyze" class="spinner-border spinner-border-sm me-2"></span>
                        Analyze Card
                    </button>
                    <div v-if="responses.analyze" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.analyze, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Confirm -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/card/confirm</h4>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input v-model="forms.confirm.card_id" type="text" class="form-control" placeholder="Card ID">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.card_name" type="text" class="form-control" placeholder="Card Name">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.hp" type="text" class="form-control" placeholder="HP">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.type" type="text" class="form-control" placeholder="Type">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.evolution_stage" type="text" class="form-control" placeholder="Evolution Stage">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.rarity" type="text" class="form-control" placeholder="Rarity">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.set_number" type="text" class="form-control" placeholder="Set Number">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.illustrator" type="text" class="form-control" placeholder="Illustrator">
                        </div>
                        <div class="col-md-4">
                            <input v-model="forms.confirm.game" type="text" class="form-control" placeholder="Game">
                        </div>
                    </div>
                    <button class="btn btn-success mt-3" @click="confirmCard" :disabled="loading.confirm || !authToken">
                        <span v-if="loading.confirm" class="spinner-border spinner-border-sm me-2"></span>
                        Confirm Card
                    </button>
                    <div v-if="responses.confirm" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.confirm, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Delete -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">DELETE /api/card/delete</h4>
                    <input v-model="forms.deleteCard.card_id" type="text" class="form-control mb-3" placeholder="Card ID">
                    <button class="btn btn-danger" @click="deleteAnalyzedCard" :disabled="loading.deleteCard || !authToken">
                        <span v-if="loading.deleteCard" class="spinner-border spinner-border-sm me-2"></span>
                        Delete Card
                    </button>
                    <div v-if="responses.deleteCard" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.deleteCard, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- 3. COLLECTION SECTION -->
            <div v-show="activeSection === 'collection'" class="section">
                <h2 class="section-title">📚 Collection</h2>
                
                <!-- Get Cards -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/collection/cards</h4>
                    <button class="btn btn-pokemon" @click="getCards" :disabled="loading.getCards || !authToken">
                        <span v-if="loading.getCards" class="spinner-border spinner-border-sm me-2"></span>
                        Get All Cards
                    </button>
                    
                    <!-- Cards List -->
                    <div v-if="cardsList.length > 0" class="mt-3">
                        <h5 class="text-white mb-2">Carte trovate ({{ cardsList.length }}):</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Game</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="card in cardsList.slice(0, 10)" :key="card.id">
                                        <td>{{ card.id }}</td>
                                        <td>{{ card.card_name }}</td>
                                        <td>{{ card.game }}</td>
                                        <td>
                                            <button class="btn btn-xs btn-outline-light" @click="copyCardId(card.id)">
                                                📋 Copy ID
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div v-if="responses.getCards" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getCards, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Get Unmatched Cards -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/collection/cards/unmatched</h4>
                    <button class="btn btn-pokemon" @click="getUnmatchedCards" :disabled="loading.getUnmatchedCards || !authToken">
                        <span v-if="loading.getUnmatchedCards" class="spinner-border spinner-border-sm me-2"></span>
                        Get Unmatched Cards
                    </button>
                    <div v-if="responses.getUnmatchedCards" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getUnmatchedCards, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Get Games -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/collection/games</h4>
                    <button class="btn btn-pokemon" @click="getGames" :disabled="loading.getGames || !authToken">
                        <span v-if="loading.getGames" class="spinner-border spinner-border-sm me-2"></span>
                        Get Games
                    </button>
                    <div v-if="responses.getGames" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getGames, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Update Card -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/collection/cards/{card}</h4>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input v-model="forms.updateCard.card_id" type="text" class="form-control" placeholder="Card ID">
                        </div>
                        <div class="col-md-3">
                            <input v-model="forms.updateCard.card_name" type="text" class="form-control" placeholder="Card Name">
                        </div>
                        <div class="col-md-3">
                            <input v-model="forms.updateCard.hp" type="text" class="form-control" placeholder="HP">
                        </div>
                        <div class="col-md-3">
                            <input v-model="forms.updateCard.type" type="text" class="form-control" placeholder="Type">
                        </div>
                    </div>
                    <button class="btn btn-warning mt-3" @click="updateCard" :disabled="loading.updateCard || !authToken">
                        <span v-if="loading.updateCard" class="spinner-border spinner-border-sm me-2"></span>
                        Update Card
                    </button>
                    <div v-if="responses.updateCard" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.updateCard, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Get Conditions -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/collection/cards/{card}/conditions</h4>
                    <input v-model="forms.updateCondition.card_id" type="text" class="form-control mb-3" placeholder="Card ID">
                    <button class="btn btn-pokemon" @click="getConditions" :disabled="loading.getConditions || !authToken">
                        <span v-if="loading.getConditions" class="spinner-border spinner-border-sm me-2"></span>
                        Get Conditions
                    </button>
                    <div v-if="responses.getConditions" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getConditions, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Update Condition -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/collection/cards/{card}/condition</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input v-model="forms.updateCondition.card_id" type="text" class="form-control" placeholder="Card ID">
                        </div>
                        <div class="col-md-6">
                            <select v-model="forms.updateCondition.condition" class="form-select">
                                <option value="Mint">Mint</option>
                                <option value="Near Mint">Near Mint</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Light Played">Light Played</option>
                                <option value="Played">Played</option>
                                <option value="Poor">Poor</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-info mt-3" @click="updateCondition" :disabled="loading.updateCondition || !authToken">
                        <span v-if="loading.updateCondition" class="spinner-border spinner-border-sm me-2"></span>
                        Update Condition
                    </button>
                    <div v-if="responses.updateCondition" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.updateCondition, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Update Set -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/collection/cards/{card}/set</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input v-model="forms.updateSet.card_id" type="text" class="form-control" placeholder="Card ID">
                        </div>
                        <div class="col-md-6">
                            <input v-model="forms.updateSet.card_set_id" type="text" class="form-control" placeholder="Card Set ID">
                        </div>
                    </div>
                    <button class="btn btn-success mt-3" @click="updateSetAssociation" :disabled="loading.updateSet || !authToken">
                        <span v-if="loading.updateSet" class="spinner-border spinner-border-sm me-2"></span>
                        Update Set
                    </button>
                    <div v-if="responses.updateSet" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.updateSet, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Remove Set -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">DELETE /api/collection/cards/{card}/set</h4>
                    <input v-model="forms.removeSet.card_id" type="text" class="form-control mb-3" placeholder="Card ID">
                    <button class="btn btn-warning" @click="removeSetAssociation" :disabled="loading.removeSet || !authToken">
                        <span v-if="loading.removeSet" class="spinner-border spinner-border-sm me-2"></span>
                        Remove Set
                    </button>
                    <div v-if="responses.removeSet" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.removeSet, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Delete Card from Collection -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">DELETE /api/collection/cards/{card}</h4>
                    <input v-model="forms.destroyCard.card_id" type="text" class="form-control mb-3" placeholder="Card ID">
                    <button class="btn btn-danger" @click="destroyCard" :disabled="loading.destroyCard || !authToken">
                        <span v-if="loading.destroyCard" class="spinner-border spinner-border-sm me-2"></span>
                        Delete Card
                    </button>
                    <div v-if="responses.destroyCard" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.destroyCard, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- 4. SETS SECTION -->
            <div v-show="activeSection === 'sets'" class="section">
                <h2 class="section-title">🎴 Card Sets</h2>
                
                <!-- Get Sets -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/sets</h4>
                    <button class="btn btn-pokemon" @click="getSets" :disabled="loading.getSets || !authToken">
                        <span v-if="loading.getSets" class="spinner-border spinner-border-sm me-2"></span>
                        Get All Sets
                    </button>
                    
                    <!-- Sets List -->
                    <div v-if="setsList.length > 0" class="mt-3">
                        <h5 class="text-white mb-2">Set trovati ({{ setsList.length }}):</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="set in setsList.slice(0, 10)" :key="set.id">
                                        <td>{{ set.id }}</td>
                                        <td>{{ set.name }}</td>
                                        <td>
                                            <button class="btn btn-xs btn-outline-light" @click="copyCardId(set.id)">
                                                📋 Copy ID
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div v-if="responses.getSets" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getSets, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Show Set -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/sets/{id}</h4>
                    <input v-model="forms.showSet.set_id" type="text" class="form-control mb-3" placeholder="Set ID">
                    <button class="btn btn-pokemon" @click="showSet" :disabled="loading.showSet || !authToken">
                        <span v-if="loading.showSet" class="spinner-border spinner-border-sm me-2"></span>
                        Show Set Details
                    </button>
                    <div v-if="responses.showSet" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.showSet, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- 5. MATCHING SECTION -->
            <div v-show="activeSection === 'matching'" class="section">
                <h2 class="section-title">🔗 Card Matching</h2>
                
                <!-- Get Suggestions -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">GET /api/matching/cards/{card}/suggestions</h4>
                    <input v-model="forms.suggestions.card_id" type="text" class="form-control mb-3" placeholder="Card ID">
                    <button class="btn btn-pokemon" @click="getSuggestions" :disabled="loading.getSuggestions || !authToken">
                        <span v-if="loading.getSuggestions" class="spinner-border spinner-border-sm me-2"></span>
                        Get Matching Suggestions
                    </button>
                    <div v-if="responses.getSuggestions" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.getSuggestions, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Match Card -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/matching/cards/{card}/match</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input v-model="forms.match.card_id" type="text" class="form-control" placeholder="Card ID">
                        </div>
                        <div class="col-md-6">
                            <input v-model="forms.match.card_set_id" type="text" class="form-control" placeholder="Card Set ID">
                        </div>
                    </div>
                    <button class="btn btn-success mt-3" @click="matchCard" :disabled="loading.matchCard || !authToken">
                        <span v-if="loading.matchCard" class="spinner-border spinner-border-sm me-2"></span>
                        Match Card to Set
                    </button>
                    <div v-if="responses.matchCard" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.matchCard, null, 2) }}</pre>
                    </div>
                </div>

                <!-- Auto Match -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/matching/auto-match</h4>
                    <p class="text-muted">Automaticamente fa il match di tutte le carte senza set associato</p>
                    <button class="btn btn-success" @click="autoMatch" :disabled="loading.autoMatch || !authToken">
                        <span v-if="loading.autoMatch" class="spinner-border spinner-border-sm me-2"></span>
                        Auto Match All Cards
                    </button>
                    <div v-if="responses.autoMatch" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.autoMatch, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- 6. MARKET DATA SECTION -->
            <div v-show="activeSection === 'market'" class="section">
                <h2 class="section-title">💰 Market Data</h2>
                
                <!-- Import Market Data -->
                <div class="glass-card p-4 mb-3">
                    <h4 class="text-pokemon mb-3">POST /api/market-data/import</h4>
                    <p class="text-muted">Inserisci un JSON valido con i dati di mercato</p>
                    <textarea 
                        v-model="forms.importMarketData.data" 
                        class="form-control" 
                        rows="10" 
                        placeholder='{"result": [{"id": 1, "name": "...", "price": 10.50}]}'
                    ></textarea>
                    <button class="btn btn-success mt-3" @click="importMarketData" :disabled="loading.importMarketData || !authToken">
                        <span v-if="loading.importMarketData" class="spinner-border spinner-border-sm me-2"></span>
                        Import Market Data
                    </button>
                    <div v-if="responses.importMarketData" class="response-box mt-3">
                        <pre>{{ JSON.stringify(responses.importMarketData, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #FFCB05 0%, #3D7DCA 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
}

.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

.section-title {
    color: #FFCB05;
    font-weight: 700;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.text-pokemon {
    color: #FFCB05;
}

.btn-pokemon {
    background: linear-gradient(135deg, #FFCB05 0%, #FFA500 100%);
    color: #000;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
}

.btn-pokemon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 203, 5, 0.4);
    background: linear-gradient(135deg, #FFA500 0%, #FFCB05 100%);
}

.response-box {
    background: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.response-box pre {
    color: #00ff00;
    margin: 0;
    font-size: 0.85rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.form-control, .form-select {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
}

.form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: #FFCB05;
    color: white;
    box-shadow: 0 0 0 0.2rem rgba(255, 203, 5, 0.25);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.table-dark {
    --bs-table-bg: rgba(0, 0, 0, 0.3);
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>
