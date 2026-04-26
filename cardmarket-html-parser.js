/**
 * Cardmarket HTML Seller Parser — Browser Console Edition
 *
 * Apri il file HTML in un browser, poi incolla questo script nella
 * Developer Console (F12 → Console) e premi Invio.
 *
 * Output:
 *   - Tabella riepilogativa in console
 *   - Statistiche aggregate
 *   - Array JSON scaricabile come file
 */

(async () => {
    'use strict';

    // ─────────────────────────────────────────────
    // Fase 1 — Carica tutti i risultati
    // ─────────────────────────────────────────────

    let buttonLoader = document.getElementById('loadMoreButton');
    let max = 10000;
    let min = 1500;
    const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    while(true){
        buttonLoader.click();
        await sleep(Math.floor(Math.random() * (max - min + 1)) + min);
        window.scrollTo(0, document.body.scrollHeight);
        if(buttonLoader.classList.contains('d-none')) break;
    }

    // ─────────────────────────────────────────────
    // Fase 2 — Helpers
    // ─────────────────────────────────────────────

    const textOf = (el) => el?.textContent?.trim() || null;

    /**
     * Parsa tooltip vendite: "4138&nbsp;Sales&nbsp;|&nbsp;126&nbsp;Available items"
     */
    function parseSalesTooltip(raw) {
        if (!raw) return { sales: null, availableItems: null };
        const text = raw.replace(/&nbsp;/g, ' ').replace(/\u00A0/g, ' ');
        const salesMatch = text.match(/([\d,.]+)\s*Sales/i);
        const availMatch = text.match(/([\d,.]+)\s*Available/i);
        return {
            sales: salesMatch ? parseInt(salesMatch[1].replace(/[,.]/g, ''), 10) : null,
            availableItems: availMatch ? parseInt(availMatch[1].replace(/[,.]/g, ''), 10) : null,
        };
    }

    /** Parsa "6,80 €" → 6.80 */
    function parsePrice(raw) {
        if (!raw) return null;
        const cleaned = raw.replace('€', '').replace(/\s/g, '').replace(',', '.');
        const val = parseFloat(cleaned);
        return isNaN(val) ? null : val;
    }

    /** "Item location: France" → "France" */
    function parseCountry(raw) {
        if (!raw) return null;
        const m = raw.match(/Item location:\s*(.+)/i);
        return m ? m[1].trim() : raw.trim();
    }

    // ─────────────────────────────────────────────
    // Parsing singola riga
    // ─────────────────────────────────────────────

    function parseArticleRow(row) {
        const result = {};

        // 1. Article ID
        const rowId = row.getAttribute('id') || '';
        result.articleId = rowId.replace('articleRow', '') || null;

        // ── Seller ───────────────────────────────
        const sellerNameContainer = row.querySelector('.seller-name');

        // 2. Vendite & articoli disponibili
        const sellCountBadge = sellerNameContainer?.querySelector('.sell-count');
        const salesTooltip = sellCountBadge?.getAttribute('data-bs-original-title') ?? null;
        const { sales, availableItems } = parseSalesTooltip(salesTooltip);
        result.sellerSalesCount = sales;
        result.sellerAvailableItems = availableItems;

        // 3. Paese venditore
        const countryIcon = sellerNameContainer?.querySelector('.icon.d-flex.has-content-centered');
        const countryRaw = countryIcon?.getAttribute('aria-label')
            || countryIcon?.getAttribute('data-bs-original-title')
            || null;
        result.sellerCountry = parseCountry(countryRaw);

        // 4. Nome venditore & profilo
        const sellerLink = sellerNameContainer?.querySelector('a[href*="/Users/"]');
        result.sellerName = sellerLink ? textOf(sellerLink) : null;
        result.sellerProfileUrl = sellerLink?.getAttribute('href') ?? null;

        // ── Prodotto ─────────────────────────────
        const productAttrs = row.querySelector('.product-attributes');

        // 5. Condizione carta
        const conditionEl = productAttrs?.querySelector('.article-condition');
        result.cardCondition = conditionEl?.getAttribute('data-bs-original-title') ?? null;
        result.cardConditionCode = conditionEl ? textOf(conditionEl.querySelector('.badge')) : null;

        // 6. Lingua carta
        const langIcons = productAttrs?.querySelectorAll('.icon.me-2') || [];
        let cardLanguage = null;
        for (const icon of langIcons) {
            const label = icon.getAttribute('aria-label') || icon.getAttribute('data-bs-original-title');
            if (label && !label.includes('location')) {
                cardLanguage = label;
                break;
            }
        }
        result.cardLanguage = cardLanguage;

        // 7. Commento venditore
        const commentContainer = row.querySelector('.product-comments');
        let sellerComment = null;
        if (commentContainer) {
            const desktopComment = commentContainer.querySelector('.d-block.text-truncate');
            if (desktopComment) {
                sellerComment = desktopComment.getAttribute('data-bs-original-title')
                    || textOf(desktopComment);
            }
            if (!sellerComment) {
                const mobileComment = commentContainer.querySelector('.fonticon-comments');
                if (mobileComment) {
                    sellerComment = mobileComment.getAttribute('aria-label')
                        || mobileComment.getAttribute('data-bs-original-title');
                }
            }
        }
        result.sellerComment = sellerComment;

        // ── Offerta ──────────────────────────────

        // 8. Prezzo
        const priceContainer = row.querySelector('.price-container');
        const priceSpan = priceContainer?.querySelector('.fw-bold');
        const priceText = textOf(priceSpan);
        result.priceRaw = priceText;
        result.priceEur = parsePrice(priceText);

        // 9. Quantità
        const amountContainer = row.querySelector('.col-offer .amount-container .item-count');
        const qtyText = textOf(amountContainer);
        result.quantity = qtyText ? parseInt(qtyText, 10) : null;

        return result;
    }

    // ─────────────────────────────────────────────
    // Parsing info carta (#tabContent-info)
    // ─────────────────────────────────────────────

    function parseCardInfo() {
        const info = {};
        const panel = document.querySelector('#tabContent-info');
        if (!panel) return info;

        // Immagine carta
        const img = panel.querySelector('.card-image img');
        info.imageUrl = img?.getAttribute('src') ?? null;
        info.cardName = img?.getAttribute('alt')?.trim() ?? null;

        // Coppie dt/dd dalla definition list
        const dtElements = panel.querySelectorAll('dl.labeled dt');
        const ddElements = panel.querySelectorAll('dl.labeled dd');

        for (let i = 0; i < dtElements.length; i++) {
            const key = textOf(dtElements[i]);
            const dd = ddElements[i];
            if (!key || !dd) continue;

            switch (key) {
                case 'Rarity': {
                    const svg = dd.querySelector('svg');
                    info.rarity = svg?.getAttribute('aria-label')
                        || svg?.getAttribute('data-bs-original-title')
                        || textOf(dd);
                    break;
                }
                case 'Number':
                    info.number = textOf(dd);
                    break;
                case 'Printed in': {
                    const expLink = dd.querySelector('a[href*="/Expansions/"]');
                    info.expansion = textOf(expLink) || textOf(dd);
                    info.expansionUrl = expLink?.getAttribute('href') ?? null;
                    break;
                }
                case 'Reprints': {
                    const links = dd.querySelectorAll('a');
                    info.reprintVersions = null;
                    info.reprintOffersUrl = null;
                    for (const link of links) {
                        const t = textOf(link);
                        if (t && t.includes('Versions')) {
                            const m = t.match(/(\d+)/);
                            info.reprintVersions = m ? parseInt(m[1], 10) : t;
                        }
                        if (link.getAttribute('href')?.includes('/Cards/')) {
                            info.reprintOffersUrl = link.getAttribute('href');
                        }
                    }
                    break;
                }
                case 'Species': {
                    const specLink = dd.querySelector('a');
                    info.species = textOf(specLink) || textOf(dd);
                    break;
                }
                case 'Available items':
                    info.availableItems = parseInt(textOf(dd)?.replace(/[,.]/g, ''), 10) || null;
                    break;
                case 'From':
                    info.priceFrom = textOf(dd);
                    info.priceFromEur = parsePrice(textOf(dd));
                    break;
                case 'Price Trend':
                    info.priceTrend = textOf(dd);
                    info.priceTrendEur = parsePrice(textOf(dd));
                    break;
                case '30-days average price':
                    info.avg30days = textOf(dd);
                    info.avg30daysEur = parsePrice(textOf(dd));
                    break;
                case '7-days average price':
                    info.avg7days = textOf(dd);
                    info.avg7daysEur = parsePrice(textOf(dd));
                    break;
                case '1-day average price':
                    info.avg1day = textOf(dd);
                    info.avg1dayEur = parsePrice(textOf(dd));
                    break;
            }
        }

        // Storico prezzi dal grafico Chart.js
        const chartScript = panel.querySelector('script.chart-init-script');
        if (chartScript) {
            const scriptText = chartScript.textContent;
            try {
                const labelsMatch = scriptText.match(/"labels":\[([^\]]+)\]/);
                const dataMatch = scriptText.match(/"data":\[([\d.,]+)\]/);
                if (labelsMatch && dataMatch) {
                    const labels = labelsMatch[1].match(/"([^"]+)"/g)?.map(s => s.replace(/"/g, '')) || [];
                    const data = dataMatch[1].split(',').map(Number);
                    info.priceHistory = labels.map((date, i) => ({
                        date,
                        avgSellPrice: data[i] ?? null
                    }));
                }
            } catch (e) {
                console.warn('⚠️ Errore nel parsing dello storico prezzi:', e.message);
            }
        }

        return info;
    }

    // ─────────────────────────────────────────────
    // Esecuzione
    // ─────────────────────────────────────────────

    // Parsing dati carta
    const dati_carta = parseCardInfo();

    // Parsing offerte venditori
    const rows = document.querySelectorAll('.article-row');

    if (rows.length === 0) {
        console.error('❌ Nessuna riga .article-row trovata nella pagina.');
        return;
    }

    const offers = [];
    for (const row of rows) {
        try {
            offers.push(parseArticleRow(row));
        } catch (err) {
            console.warn('⚠️ Errore nel parsing di una riga:', err.message);
        }
    }

    // Struttura finale
    const result = { dati_carta, offers };

    // ── Statistiche ──────────────────────────────
    const countries = {};
    const conditions = {};
    const languages = {};
    let minPrice = Infinity, maxPrice = -Infinity, totalPrice = 0, priceCount = 0;

    for (const s of offers) {
        if (s.sellerCountry) countries[s.sellerCountry] = (countries[s.sellerCountry] || 0) + 1;
        if (s.cardCondition) conditions[s.cardCondition] = (conditions[s.cardCondition] || 0) + 1;
        if (s.cardLanguage) languages[s.cardLanguage] = (languages[s.cardLanguage] || 0) + 1;
        if (s.priceEur !== null) {
            minPrice = Math.min(minPrice, s.priceEur);
            maxPrice = Math.max(maxPrice, s.priceEur);
            totalPrice += s.priceEur;
            priceCount++;
        }
    }

    console.log('%c═══════════════════════════════════════════════════════════════', 'color: #4CAF50; font-weight: bold');
    console.log(`%c  🃏 ${dati_carta.cardName || 'Carta'} — ${dati_carta.expansion || ''}`, 'color: #FF9800; font-size: 14px; font-weight: bold');
    console.log(`%c  📦 ${offers.length} OFFERTE TROVATE`, 'color: #2196F3; font-size: 14px; font-weight: bold');
    console.log('%c═══════════════════════════════════════════════════════════════', 'color: #4CAF50; font-weight: bold');

    console.log('\n🃏 Dati carta:');
    console.table({
        Nome: dati_carta.cardName,
        Numero: dati_carta.number,
        Rarità: dati_carta.rarity,
        Espansione: dati_carta.expansion,
        Specie: dati_carta.species,
        'Articoli disponibili': dati_carta.availableItems,
        'Prezzo da': dati_carta.priceFrom,
        'Price Trend': dati_carta.priceTrend,
        'Media 30gg': dati_carta.avg30days,
        'Media 7gg': dati_carta.avg7days,
        'Media 1gg': dati_carta.avg1day,
    });

    console.log(`\n💰 Prezzo min: ${minPrice === Infinity ? 'N/D' : minPrice.toFixed(2) + ' €'}`);
    console.log(`💰 Prezzo max: ${maxPrice === -Infinity ? 'N/D' : maxPrice.toFixed(2) + ' €'}`);
    console.log(`💰 Prezzo medio: ${priceCount ? (totalPrice / priceCount).toFixed(2) + ' €' : 'N/D'}`);

    console.log('\n🌍 Paesi venditori:');
    console.table(Object.entries(countries).sort((a, b) => b[1] - a[1]).map(([paese, n]) => ({ Paese: paese, Offerte: n })));

    console.log('📋 Condizioni carta:');
    console.table(Object.entries(conditions).sort((a, b) => b[1] - a[1]).map(([cond, n]) => ({ Condizione: cond, Offerte: n })));

    console.log('🗣️ Lingue carta:');
    console.table(Object.entries(languages).sort((a, b) => b[1] - a[1]).map(([lang, n]) => ({ Lingua: lang, Offerte: n })));

    // ── Tabella completa ─────────────────────────
    console.log('\n📊 Tutte le offerte:');
    console.table(offers.map(s => ({
        Venditore: s.sellerName,
        Prezzo: s.priceRaw,
        Condizione: s.cardConditionCode,
        Lingua: s.cardLanguage,
        Paese: s.sellerCountry,
        Vendite: s.sellerSalesCount,
        'Articoli Disp.': s.sellerAvailableItems,
        Quantità: s.quantity,
        Commento: s.sellerComment ? (s.sellerComment.length > 40 ? s.sellerComment.slice(0, 40) + '…' : s.sellerComment) : '',
    })));

    // ── Download JSON ────────────────────────────
    const filename = (dati_carta.cardName || 'cardmarket').replace(/\s+/g, '_').toLowerCase() + '.json';
    const blob = new Blob([JSON.stringify(result, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);

    console.log(`%c\n✅ File JSON scaricato: ${filename}`, 'color: #4CAF50; font-weight: bold');
    console.log('💡 I dati sono anche disponibili nella variabile: window.__cardmarketData');

    // Esponi i dati globalmente per ulteriori analisi nella console
    window.__cardmarketData = result;

})();
