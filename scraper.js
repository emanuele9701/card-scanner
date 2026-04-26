import { readFileSync, writeFileSync, existsSync, statSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';

const url = process.argv[2];
const start = Date.now();
const scriptDir = dirname(process.argv[1]);

// Percorsi file
const CACHE_FILE = join(scriptDir, 'storage', 'app', 'proxy_cache.json');
const ENV_FILE   = join(scriptDir, '.env');
const CACHE_TTL_MS = 60 * 60 * 1000; // 1 ora

const log = (msg, data = '') => {
    const elapsed = ((Date.now() - start) / 1000).toFixed(2);
    const line = data
        ? `[${elapsed}s] ${msg} — ${JSON.stringify(data)}`
        : `[${elapsed}s] ${msg}`;
    process.stderr.write(line + '\n');
};

// ─────────────────────── PROXY MANAGEMENT ───────────────────────

/**
 * Legge WEBSHARE_API_TOKEN dal file .env nella root del progetto.
 */
function getApiToken() {
    if (!existsSync(ENV_FILE)) {
        throw new Error(`.env non trovato in ${ENV_FILE}`);
    }
    const content = readFileSync(ENV_FILE, 'utf-8');
    const match = content.match(/^WEBSHARE_API_TOKEN=(.+)$/m);
    if (!match || !match[1].trim()) {
        throw new Error('WEBSHARE_API_TOKEN non configurato nel file .env');
    }
    return match[1].trim().replace(/^["']|["']$/g, '');
}

/**
 * Verifica se la cache dei proxy è ancora valida (meno di 1 ora).
 */
function isCacheValid() {
    if (!existsSync(CACHE_FILE)) return false;
    try {
        const stat = statSync(CACHE_FILE);
        const ageMs = Date.now() - stat.mtimeMs;
        log('📋 Cache proxy trovata', { age_min: Math.round(ageMs / 60000) });
        return ageMs < CACHE_TTL_MS;
    } catch {
        return false;
    }
}

/**
 * Recupera la lista proxy dall'API Webshare e la salva in cache.
 */
async function fetchAndCacheProxies(token) {
    log('🔄 Recupero lista proxy da Webshare API...');

    const res = await fetch(
        'https://proxy.webshare.io/api/v2/proxy/list/?mode=direct&page=1&page_size=25',
        { headers: { 'Authorization': `Token ${token}` } }
    );

    if (!res.ok) {
        throw new Error(`Webshare API errore HTTP ${res.status}: ${res.statusText}`);
    }

    const data = await res.json();

    if (!data.results || data.results.length === 0) {
        throw new Error('Webshare API ha restituito una lista proxy vuota');
    }

    // Filtra solo proxy validi
    const proxies = data.results
        .filter(p => p.valid)
        .map(p => ({
            ip:       p.proxy_address,
            port:     p.port,
            username: p.username,
            password: p.password,
            country:  p.country_code,
        }));

    if (proxies.length === 0) {
        throw new Error('Nessun proxy valido trovato dalla Webshare API');
    }

    const cacheData = {
        fetched_at: new Date().toISOString(),
        ttl_hours:  1,
        count:      proxies.length,
        proxies,
    };

    // Crea la directory se non esiste
    const cacheDir = dirname(CACHE_FILE);
    if (!existsSync(cacheDir)) {
        mkdirSync(cacheDir, { recursive: true });
    }

    writeFileSync(CACHE_FILE, JSON.stringify(cacheData, null, 2), 'utf-8');
    log('✅ Lista proxy salvata in cache', { count: proxies.length });

    return proxies;
}

/**
 * Ottiene la lista proxy: da cache se valida, altrimenti da API.
 */
async function getProxies() {
    if (isCacheValid()) {
        const data = JSON.parse(readFileSync(CACHE_FILE, 'utf-8'));
        log('✅ Proxy caricati da cache', { count: data.proxies.length });
        return data.proxies;
    }
    const token = getApiToken();
    return await fetchAndCacheProxies(token);
}

/**
 * Seleziona un proxy random dalla lista.
 */
function pickRandom(proxies) {
    return proxies[Math.floor(Math.random() * proxies.length)];
}

// ─────────────────────── MAIN ───────────────────────

if (!url) {
    log('❌ URL mancante');
    process.exit(1);
}

let sessionId = null;

try {
    // 0. Recupera un proxy random (da cache o da API Webshare)
    const proxies = await getProxies();
    const proxy   = pickRandom(proxies);

    log('🚀 Avvio scraper via FlareSolverr', {
        url,
        proxy: `${proxy.ip}:${proxy.port} (${proxy.country})`
    });

    // 1. Crea una sessione FlareSolverr con proxy autenticato
    //    (sessions.create è l'unico modo per passare username/password)
    log('📡 Creazione sessione con proxy...');
    const sessionRes = await fetch('http://localhost:8191/v1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cmd: 'sessions.create',
            proxy: {
                url:      `http://${proxy.ip}:${proxy.port}`,
                username: proxy.username,
                password: proxy.password,
            },
        }),
    });

    const sessionData = await sessionRes.json();
    if (sessionData.status !== 'ok') {
        throw new Error(`Errore creazione sessione: ${sessionData.message}`);
    }

    sessionId = sessionData.session;
    log('✅ Sessione creata', { session: sessionId });

    // 2. Richiedi la pagina usando la sessione (il proxy è già dentro la sessione)
    log('📡 Richiesta pagina in corso...');
    const reqRes = await fetch('http://localhost:8191/v1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cmd:        'request.get',
            url:        url,
            maxTimeout: 60000,
            session:    sessionId,
        }),
    });

    log('✅ Risposta ricevuta da FlareSolverr', { status: reqRes.status });
    const data = await reqRes.json();

    if (data.status !== 'ok') {
        throw new Error(`FlareSolverr errore: ${data.message}`);
    }

    const html = data.solution.response;
    log('✅ HTML estratto', { bytes: html.length });
    log('📌 Titolo pagina', { title: data.solution.title ?? 'N/A' });
    log('🍪 Cookies ricevuti', { count: data.solution.cookies?.length ?? 0 });

    process.stdout.write(html);
    log('✅ HTML scritto su stdout');

} catch (err) {
    log('❌ Errore durante la richiesta', { error: err.message });
    process.exitCode = 1;

} finally {
    // 3. Distruggi SEMPRE la sessione per liberare memoria (ogni sessione = un Chrome)
    if (sessionId) {
        log('🧹 Chiusura sessione...', { session: sessionId });
        try {
            const destroyRes = await fetch('http://localhost:8191/v1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cmd:     'sessions.destroy',
                    session: sessionId,
                }),
            });
            const destroyData = await destroyRes.json();
            if (destroyData.status === 'ok') {
                log('✅ Sessione chiusa');
            } else {
                log('⚠️ Errore chiusura sessione', { message: destroyData.message });
            }
        } catch (e) {
            log('⚠️ Impossibile chiudere la sessione', { error: e.message });
        }
    }
    process.exit();
}