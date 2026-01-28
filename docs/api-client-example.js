/**
 * API Client per Carte Pokemon
 * 
 * Esempio di integrazione delle API nel frontend JavaScript
 * Può essere usato con vanilla JS, React, Vue, o qualsiasi framework
 */

class CartePokemonAPI {
    constructor(baseURL = 'http://localhost/api') {
        this.baseURL = baseURL;
        this.token = localStorage.getItem('api_token') || null;
    }

    /**
     * Effettua una richiesta HTTP all'API
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;

        const config = {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...options.headers,
            },
        };

        // Aggiungi il token se presente
        if (this.token) {
            config.headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * Salva il token nel localStorage e nell'istanza
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('api_token', token);
    }

    /**
     * Rimuove il token
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem('api_token');
    }

    // ==================
    // AUTHENTICATION
    // ==================

    /**
     * Registra un nuovo utente
     */
    async register(email, password, passwordConfirmation, name = null) {
        const data = await this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify({
                email,
                password,
                password_confirmation: passwordConfirmation,
                name,
            }),
        });

        if (data.token) {
            this.setToken(data.token);
        }

        return data;
    }

    /**
     * Effettua il login
     */
    async login(email, password) {
        const data = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        });

        if (data.token) {
            this.setToken(data.token);
        }

        return data;
    }

    /**
     * Effettua il logout
     */
    async logout() {
        const data = await this.request('/auth/logout', {
            method: 'POST',
        });

        this.clearToken();
        return data;
    }

    /**
     * Ottiene l'utente corrente
     */
    async getCurrentUser() {
        return await this.request('/auth/user');
    }

    // ==================
    // COLLECTION
    // ==================

    /**
     * Ottiene le carte della collezione
     * 
     * @param {Object} params - Parametri di filtro
     * @param {string} params.game - Filtra per gioco
     * @param {number} params.set_id - Filtra per set ID
     * @param {string} params.rarity - Filtra per rarità
     * @param {string} params.condition - Filtra per condizione
     * @param {string} params.search - Cerca nel nome della carta
     * @param {string} params.sort_by - Campo per ordinamento
     * @param {string} params.sort_order - Direzione ordinamento (asc/desc)
     * @param {number} params.per_page - Numero risultati per pagina
     * @param {number} params.page - Numero pagina
     */
    async getCards(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/collection/cards${queryString ? `?${queryString}` : ''}`;
        return await this.request(endpoint);
    }

    /**
     * Ottiene i giochi/collezionabili con statistiche
     */
    async getGames() {
        return await this.request('/collection/games');
    }

    // ==================
    // SETS
    // ==================

    /**
     * Ottiene tutti i sets
     * 
     * @param {Object} params - Parametri di filtro
     * @param {string} params.game - Filtra per gioco
     * @param {string} params.search - Cerca nel nome o abbreviazione
     * @param {string} params.sort_by - Campo per ordinamento
     * @param {string} params.sort_order - Direzione ordinamento (asc/desc)
     * @param {number} params.per_page - Numero risultati per pagina
     * @param {number} params.page - Numero pagina
     */
    async getSets(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/sets${queryString ? `?${queryString}` : ''}`;
        return await this.request(endpoint);
    }

    /**
     * Ottiene i dettagli di un set specifico
     */
    async getSetDetails(setId) {
        return await this.request(`/sets/${setId}`);
    }
}

// ==================
// ESEMPI DI UTILIZZO
// ==================

/*

// Inizializza l'API client
const api = new CartePokemonAPI();

// ---- AUTENTICAZIONE ----

// Registrazione
try {
    const response = await api.register(
        'user@example.com',
        'password123',
        'password123',
        'Nome Utente'
    );
    console.log('Registrato:', response.user);
} catch (error) {
    console.error('Errore registrazione:', error);
}

// Login
try {
    const response = await api.login('user@example.com', 'password123');
    console.log('Login effettuato:', response.user);
} catch (error) {
    console.error('Errore login:', error);
}

// Ottieni utente corrente
try {
    const response = await api.getCurrentUser();
    console.log('Utente corrente:', response.user);
} catch (error) {
    console.error('Errore:', error);
}

// ---- COLLEZIONE ----

// Ottieni tutte le carte
try {
    const response = await api.getCards({ per_page: 20, page: 1 });
    console.log('Carte:', response.data);
    console.log('Totale:', response.meta.total);
} catch (error) {
    console.error('Errore:', error);
}

// Ottieni carte filtrate per gioco
try {
    const response = await api.getCards({
        game: 'Pokemon',
        rarity: 'Rare',
        per_page: 10
    });
    console.log('Carte Pokemon rare:', response.data);
} catch (error) {
    console.error('Errore:', error);
}

// Cerca carte
try {
    const response = await api.getCards({
        search: 'Charizard',
        sort_by: 'card_name',
        sort_order: 'asc'
    });
    console.log('Carte trovate:', response.data);
} catch (error) {
    console.error('Errore:', error);
}

// Ottieni giochi/collezionabili
try {
    const response = await api.getGames();
    console.log('Giochi nella collezione:', response.data);
    response.data.forEach(game => {
        console.log(`${game.name}: ${game.card_count} carte, valore: €${game.total_value}`);
    });
} catch (error) {
    console.error('Errore:', error);
}

// ---- SETS ----

// Ottieni tutti i sets
try {
    const response = await api.getSets({ per_page: 20 });
    console.log('Sets:', response.data);
} catch (error) {
    console.error('Errore:', error);
}

// Ottieni sets filtrati
try {
    const response = await api.getSets({
        game: 'Pokemon',
        search: 'Base',
        sort_by: 'release_date',
        sort_order: 'desc'
    });
    console.log('Sets trovati:', response.data);
} catch (error) {
    console.error('Errore:', error);
}

// Ottieni dettagli di un set specifico
try {
    const response = await api.getSetDetails(1);
    console.log('Dettagli set:', response.data);
    console.log('Carte possedute:', response.data.cards);
} catch (error) {
    console.error('Errore:', error);
}

// ---- LOGOUT ----

// Logout
try {
    await api.logout();
    console.log('Logout effettuato');
} catch (error) {
    console.error('Errore logout:', error);
}

*/

// Esporta per uso in moduli
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CartePokemonAPI;
}

// Rendi disponibile globalmente nel browser
if (typeof window !== 'undefined') {
    window.CartePokemonAPI = CartePokemonAPI;
}
