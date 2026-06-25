{{--
    Partial: card-modal.blade.php
    Includi UNA sola volta in layouts/app.blade.php subito prima di </body>:
        @include('partials.card-modal')
--}}

<style>
    .modal-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(1, 15, 31, 0.8);
        backdrop-filter: blur(4px);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .modal-overlay.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    .card-modal-content {
        background-color: #122131;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        max-width: 860px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        transform: scale(0.96);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .modal-overlay.is-open .card-modal-content {
        transform: scale(1);
        opacity: 1;
    }

    .btn-modal-close {
        border-radius: 0.5rem;
        padding: 0.375rem;
        color: rgba(212, 228, 250, 0.4);
        background: none;
        border: none;
        cursor: pointer;
        transition: color 0.2s;
        display: flex;
        align-items: center;
    }

    .btn-modal-close:hover {
        color: #d4e4fa;
    }

    .modal-detail-label {
        color: rgba(212, 228, 250, 0.45);
        font-weight: 500;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .modal-detail-value {
        color: rgba(212, 228, 250, 0.85);
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .modal-th {
        color: rgba(212, 228, 250, 0.4);
        font-weight: 600;
        text-align: left;
        padding: 0.5rem 0.75rem;
        white-space: nowrap;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
    }

    .modal-td {
        color: rgba(212, 228, 250, 0.75);
        padding: 0.6rem 0.75rem;
        white-space: nowrap;
    }

    tr:hover .modal-td {
        background-color: rgba(255, 255, 255, 0.025);
    }
</style>

<div id="cm-overlay" class="modal-overlay">

    {{-- Sfondo cliccabile per chiudere --}}
    <div onclick="closeModal()" class="position-absolute inset-0 w-100 h-100"></div>

    {{-- Pannello modale --}}
    <div class="card-modal-content p-4 position-relative" style="z-index:10;">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
            <div class="d-flex justify-content-between w-100">
                <div class="pe-3">
                    <h2 id="cm-name" class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa; line-height: 1.3;">...</h2>
                    <span id="cm-rarity" class="badge mt-1"
                        style="background:rgba(251,180,0,0.15); color:#fbb400;
                               border:1px solid rgba(251,180,0,0.3);
                               font-size:0.7rem; font-weight:600; display:none;"></span>
                </div>
                {{-- Pulsante chiudi per mobile --}}
                <button onclick="closeModal()" class="btn-modal-close d-md-none flex-shrink-0" style="align-self: flex-start;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto ms-md-auto">
                <button id="cm-monitor-btn" class="btn btn-sm d-flex align-items-center justify-content-center" style="background-color: rgba(99,179,237,0.15); color: #63b3ed; border: 1px solid rgba(99,179,237,0.3); font-weight: 600; display:none; flex: 1;" onclick="if(window._currentCard) toggleCardWatchlist(window._currentCard.id)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span id="cm-monitor-text" class="text-nowrap">{{ __('Monitora Prezzo') }}</span>
                </button>
                <button id="cm-manage-copies-btn" class="btn btn-sm text-dark fw-bold" style="background-color: #fbb400; display:none; flex: 1; white-space: nowrap;" onclick="if(window._currentCard) openManageModal(window._currentCard.id, window._currentCard.name)">
                    {{ __('Gestisci le tue copie') }}
                </button>
                {{-- Pulsante chiudi per desktop --}}
                <button onclick="closeModal()" class="btn-modal-close d-none d-md-flex flex-shrink-0 ms-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Spinner --}}
        <div id="cm-loading" class="d-flex justify-content-center align-items-center py-5">
            <div class="spinner-border" style="color:#fbb400;" role="status">
                <span class="visually-hidden">Caricamento...</span>
            </div>
        </div>

        {{-- Corpo (visibile dopo il fetch) --}}
        <div id="cm-body" style="display:none;">

            <div class="row g-4 align-items-start mb-4">

                {{-- Immagine --}}
                <div class="col-12 col-md-4 d-flex justify-content-center">
                    <div
                        style="background:rgba(255,255,255,0.03);
                                border:1px solid rgba(255,255,255,0.08);
                                border-radius:1rem; padding:1rem; display:inline-flex;">
                        <img id="cm-img" src="" alt=""
                            style="max-width:180px; width:100%;
                                   border-radius:0.5rem; object-fit:contain;">
                    </div>
                </div>

                {{-- Dettagli --}}
                <div class="col-12 col-md-8">
                    <dl class="row g-0" style="font-size:0.875rem;">

                        <dt class="col-5 modal-detail-label">{{ __('Dex ID') }}</dt>
                        <dd class="col-7 modal-detail-value" id="cm-dex"></dd>

                        <dt class="col-5 modal-detail-label">{{ __('Tipo') }}</dt>
                        <dd class="col-7 modal-detail-value" id="cm-types"></dd>

                        <dt class="col-5 modal-detail-label">{{ __('Stage') }}</dt>
                        <dd class="col-7 modal-detail-value" id="cm-stage"></dd>

                        <dt class="col-5 modal-detail-label">{{ __('Abilità') }}</dt>
                        <dd class="col-7 modal-detail-value" id="cm-abilities"></dd>

                        <dt class="col-5 modal-detail-label">{{ __('Varianti') }}</dt>
                        <dd class="col-7 modal-detail-value" id="cm-variants"></dd>

                        <dt class="col-5 modal-detail-label">{{ __('Ultimo prezzo') }}</dt>
                        <dd class="col-7 modal-detail-value" id="cm-price"></dd>

                    </dl>
                </div>
            </div>

            <div style="border-top:1px solid rgba(255,255,255,0.07); margin-bottom:1.25rem;"></div>

            {{-- Grafico Andamento Prezzi --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-semibold mb-0"
                        style="font-size:0.875rem; color:rgba(212,228,250,0.6);
                               text-transform:uppercase; letter-spacing:0.05em;">
                        {{ __('Andamento Prezzi') }}
                    </h3>
                    <div id="cm-chart-filters" class="d-flex gap-2">
                        <select id="cm-filter-lang" class="form-select form-select-sm bg-dark text-white border-secondary" style="font-size:0.75rem;" onchange="renderChart()">
                            <option value="">{{ __('Tutte Lingue') }}</option>
                            <option value="it">IT</option>
                            <option value="en">EN</option>
                            <option value="jp">JP</option>
                            <option value="fr">FR</option>
                            <option value="de">DE</option>
                            <option value="es">ES</option>
                            <option value="pt">PT</option>
                        </select>
                        <select id="cm-filter-cond" class="form-select form-select-sm bg-dark text-white border-secondary" style="font-size:0.75rem;" onchange="renderChart()">
                            <option value="">{{ __('Tutte Cond.') }}</option>
                            <option value="NM">NM</option>
                            <option value="LP">LP</option>
                            <option value="MP">MP</option>
                            <option value="HP">HP</option>
                            <option value="DMG">DMG</option>
                        </select>
                        <select id="cm-filter-variant" class="form-select form-select-sm bg-dark text-white border-secondary" style="font-size:0.75rem;" onchange="renderChart()">
                            <option value="normal">{{ __('Normal') }}</option>
                            <option value="holo">{{ __('Holo / Foil') }}</option>
                        </select>
                    </div>
                </div>
                <div id="cm-chart-container" style="position:relative; height:260px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:0.75rem; padding:1rem;">
                    <canvas id="cm-price-chart"></canvas>
                </div>
                <div id="cm-chart-empty" style="display:none; text-align:center; padding:2rem; color:rgba(212,228,250,0.3); font-size:0.85rem;">
                    {{ __('Nessun dato storico disponibile') }}
                </div>
            </div>

            {{-- Storico prezzi --}}
            <div>
                <h3 class="fw-semibold mb-3"
                    style="font-size:0.875rem; color:rgba(212,228,250,0.6);
                           text-transform:uppercase; letter-spacing:0.05em;">
                    {{ __('Storico prezzi') }}
                </h3>
                <div style="overflow-x:auto;">
                    <table class="w-100" style="font-size:0.8125rem; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                                <th class="modal-th">{{ __('Data') }}</th>
                                <th class="modal-th">{{ __('Trend') }}</th>
                                <th class="modal-th">{{ __('Provider') }}</th>
                            </tr>
                        </thead>
                        <tbody id="cm-prices-tbody"></tbody>
                    </table>
                </div>
            </div>

        </div>{{-- fine #cm-body --}}
    </div>{{-- fine .card-modal-content --}}
</div>{{-- fine #cm-overlay --}}

<script>
    (function() {

        function _esc(str) {
            if (str == null) return '';
            var d = document.createElement('div');
            d.textContent = String(str);
            return d.innerHTML;
        }

        function _price(val) {
            if (val === null || val === undefined) return '—';
            return parseFloat(val).toFixed(2) + ' €';
        }

        function _variants(variants) {
            if (!variants) return [];
            var labels = {
                normal: 'Normal',
                holo: 'Holo',
                reverse: 'Reverse',
                firstEdition: '1ª Ed.',
                wPromo: 'Promo'
            };
            return Object.entries(variants)
                .filter(function(kv) {
                    return kv[1] === true;
                })
                .map(function(kv) {
                    return labels[kv[0]] || kv[0];
                });
        }

        /* ── openModal ──────────────────────────────────────────────────────── */
        window.openModal = function(card) {
            var overlay = document.getElementById('cm-overlay');

            document.getElementById('cm-name').textContent = card.name || '...';
            document.getElementById('cm-rarity').style.display = 'none';
            document.getElementById('cm-loading').setAttribute('style', '');
            document.getElementById('cm-body').style.display = 'none';

            overlay.style.display = 'flex';
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    overlay.classList.add('is-open');
                });
            });
            document.body.style.overflow = 'hidden';

            if (!window._cardDetailRoute) {
                return;
            }

            fetch(window._cardDetailRoute.replace(':card', card.id), {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content ||
                            '',
                    },
                    credentials: 'same-origin',
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    _render(data);
                    document.getElementById('cm-loading').setAttribute('style', 'display:none !important;');
                    document.getElementById('cm-body').style.display = '';
                })
                .catch(function(e) {
                    console.error('openModal:', e);
                    document.getElementById('cm-loading').setAttribute('style', 'display:none !important;');
                });
        };

        /* ── closeModal ─────────────────────────────────────────────────────── */
        window.closeModal = function() {
            var overlay = document.getElementById('cm-overlay');
            overlay.classList.remove('is-open');
            setTimeout(function() {
                overlay.style.display = 'none';
                window._currentCard = null;
            }, 300);
            document.body.style.overflow = '';
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        /* ── _render ────────────────────────────────────────────────────────── */
        function _render(card) {
            document.getElementById('cm-name').textContent = card.name || '...';

            var rarityEl = document.getElementById('cm-rarity');
            if (card.rarity) {
                rarityEl.textContent = card.rarity;
                rarityEl.style.display = '';
            } else {
                rarityEl.style.display = 'none';
            }

            var img = document.getElementById('cm-img');
            img.src = card.url_image ? card.url_image + '/high.png' : '';
            img.alt = card.name || '';

            document.getElementById('cm-dex').textContent = card.dexId || '—';

            /* Tipi */
            var typesEl = document.getElementById('cm-types');
            if (card.types && card.types.length) {
                typesEl.innerHTML = '<div class="d-flex flex-wrap gap-1">' +
                    card.types.map(function(t) {
                        return '<span class="badge" style="background:rgba(99,179,237,0.15);' +
                            'color:#63b3ed;border:1px solid rgba(99,179,237,0.3);' +
                            'font-size:0.7rem;">' + _esc(t) + '</span>';
                    }).join('') + '</div>';
            } else {
                typesEl.innerHTML = '<span style="color:rgba(212,228,250,0.4);">—</span>';
            }

            document.getElementById('cm-stage').textContent = card.level_stage || '—';

            /* Abilità */
            var abEl = document.getElementById('cm-abilities');
            if (card.abilities && card.abilities.length) {
                abEl.innerHTML = '<ul class="list-unstyled mb-0">' +
                    card.abilities.map(function(ab) {
                        return '<li>' +
                            '<span class="fw-semibold" style="color:#d4e4fa;">' + _esc(ab.name) + '</span>' +
                            (ab.type ?
                                '<span class="ms-1 badge" style="background:rgba(154,230,180,0.15);' +
                                'color:#9ae6b4;border:1px solid rgba(154,230,180,0.3);' +
                                'font-size:0.65rem;">' + _esc(ab.type) + '</span>' : '') +
                            (ab.effect ?
                                '<p style="font-size:0.75rem;color:rgba(212,228,250,0.5);' +
                                'margin:2px 0 6px;">' + _esc(ab.effect) + '</p>' : '') +
                            '</li>';
                    }).join('') + '</ul>';
            } else {
                abEl.innerHTML = '<span style="color:rgba(212,228,250,0.4);">' + (window.__trans ? window.__trans
                    .no_abilities : 'Nessuna abilità') + '</span>';
            }

            /* Varianti */
            var varEl = document.getElementById('cm-variants');
            var vList = _variants(card.variants);
            if (vList.length) {
                varEl.innerHTML = '<div class="d-flex flex-wrap gap-1">' +
                    vList.map(function(v) {
                        return '<span class="badge" style="background:rgba(255,255,255,0.06);' +
                            'color:rgba(212,228,250,0.8);' +
                            'border:1px solid rgba(255,255,255,0.1);' +
                            'font-size:0.7rem;">' + _esc(v) + '</span>';
                    }).join('') + '</div>';
            } else {
                varEl.innerHTML = '<span style="color:rgba(212,228,250,0.4);">—</span>';
            }

            /* Prezzo */
            var trend = card.prices && card.prices[0] ? card.prices[0].trend : null;
            document.getElementById('cm-price').innerHTML =
                '<span class="fw-bold" style="color:#fbb400;font-size:1rem;">' + _price(trend) + '</span>';

            window._currentCard = card;
            var btnManage = document.getElementById('cm-manage-copies-btn');
            
            // Auto-select filters based on collection
            var condSelect = document.getElementById('cm-filter-cond');
            var variantSelect = document.getElementById('cm-filter-variant');
            var langSelect = document.getElementById('cm-filter-lang');
            condSelect.value = "";
            variantSelect.value = "normal";
            langSelect.value = "";

            if (card.collectors && card.collectors.length > 0) {
                btnManage.style.display = 'inline-flex';
                // Find highest value condition/variant (simplified heuristic: first one is NM/Holo preferred)
                var bestCopy = card.collectors[0];
                card.collectors.forEach(function(c) {
                    if(c.condition === 'NM') bestCopy = c;
                });
                
                condSelect.value = bestCopy.condition || "";
                
                if (bestCopy.variants && Array.isArray(bestCopy.variants)) {
                    var vLower = bestCopy.variants.map(function(v) { return v.toLowerCase(); });
                    if (vLower.indexOf('holo') !== -1 || vLower.indexOf('reverse') !== -1) {
                        variantSelect.value = "holo";
                    }
                    
                    // Look for languages in variants
                    var langs = ['it', 'en', 'jp', 'fr', 'de', 'es', 'pt'];
                    for(var i=0; i<langs.length; i++) {
                        if (vLower.indexOf(langs[i]) !== -1) {
                            langSelect.value = langs[i];
                            break;
                        }
                    }
                }
            } else {
                btnManage.style.display = 'none';
            }

            // Mostra sempre il pulsante monitora
            document.getElementById('cm-monitor-btn').style.display = 'inline-flex';
            document.getElementById('cm-monitor-btn').style.backgroundColor = 'rgba(99,179,237,0.15)';
            document.getElementById('cm-monitor-btn').style.color = '#63b3ed';

            renderChart();
            
            /* Storico prezzi */
            var tbody = document.getElementById('cm-prices-tbody');
            if (card.prices && card.prices.length) {
                // Filter table to last 3 days only
                var now = new Date();
                var threeDaysAgo = new Date(now.getTime() - (3 * 24 * 60 * 60 * 1000));
                var recentPrices = card.prices.filter(function(p) {
                    if (!p.created_at) return false;
                    return new Date(p.created_at) >= threeDaysAgo;
                });
                
                if (recentPrices.length === 0) {
                    // If no prices in last 3 days, show the most recent one
                    recentPrices = card.prices.slice(-1);
                }
                
                tbody.innerHTML = recentPrices.map(function(p) {
                    var d = p.created_at ?
                        new Date(p.created_at).toLocaleDateString('it-IT', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }) :
                        '—';
                    var trendVal = variantSelect.value === 'holo' ? p.trend_holo : p.trend;
                    return '<tr>' +
                        '<td class="modal-td">' + _esc(d) + '</td>' +
                        '<td class="modal-td" style="color:#fbb400;font-weight:600;">' + _price(trendVal || p.trend) +
                        '</td>' +
                        '<td class="modal-td" style="color:rgba(212,228,250,0.45);font-size:0.75rem;">' +
                        _esc(p.provider || '—') + '</td>' +
                        '</tr>';
                }).join('');
            } else {
                tbody.innerHTML =
                    '<tr><td colspan="3" class="modal-td" ' +
                    'style="text-align:center;color:rgba(212,228,250,0.3);padding:1.5rem 0;">' +
                    (window.__trans ? window.__trans.no_price : 'Nessun prezzo disponibile') + '</td></tr>';
            }
        }

        /* ── renderChart ──────────────────────────────────────────────────────── */
        window.renderChart = function() {
            var card = window._currentCard;
            if(!card) return;
            
            var chartContainer = document.getElementById('cm-chart-container');
            var chartEmpty = document.getElementById('cm-chart-empty');
            
            // Destroy previous chart if exists
            if (window._priceChart) {
                window._priceChart.destroy();
                window._priceChart = null;
            }

            // Combine priceHistory and current prices to ensure all providers are included (e.g. Cardtrader)
            var allPriceRecords = (card.priceHistory || []).concat(card.prices || []);
            var validPrices = allPriceRecords.filter(function(e) { return !!e.created_at; });
            
            var filterCond = document.getElementById('cm-filter-cond').value;
            var filterVariant = document.getElementById('cm-filter-variant').value;
            var filterLang = document.getElementById('cm-filter-lang').value;
            
            if (filterCond) {
                validPrices = validPrices.filter(function(e) {
                    return !e.condition || e.condition === filterCond;
                });
            }
            
            if (filterLang) {
                validPrices = validPrices.filter(function(e) {
                    return !e.language || e.language.toLowerCase() === filterLang;
                });
            }

            if (validPrices.length > 0) {
                chartContainer.style.display = '';
                chartEmpty.style.display = 'none';

                // Group by provider
                var providerData = {};
                validPrices.forEach(function(entry) {
                    var prov = entry.provider || 'cardmarket';
                    if (!providerData[prov]) providerData[prov] = [];
                    providerData[prov].push(entry);
                });

                // Provider color mapping
                var providerColors = {
                    'cardmarket': { line: '#fbb400', bg: 'rgba(251, 180, 0, 0.1)' },
                    'tcgplayer':  { line: '#63b3ed', bg: 'rgba(99, 179, 237, 0.1)' },
                    'cardtrader': { line: '#4ade80', bg: 'rgba(74, 222, 128, 0.1)' },
                    'default':    { line: '#9ae6b4', bg: 'rgba(154, 230, 180, 0.1)' }
                };
                var colorIndex = 0;
                var extraColors = [
                    { line: '#f687b3', bg: 'rgba(246, 135, 179, 0.1)' },
                    { line: '#b794f4', bg: 'rgba(183, 148, 244, 0.1)' },
                ];

                var datasets = [];
                var allLabels = new Set();

                // We need a unified x-axis. Build from all prices sorted by date.
                var allEntries = validPrices.slice().sort(function(a, b) {
                    return new Date(a.created_at) - new Date(b.created_at);
                });
                
                // Build unique sorted labels
                var labelsArr = [];
                var labelDateMap = {};
                allEntries.forEach(function(e) {
                    var d = new Date(e.created_at);
                    var key = d.toISOString().split('T')[0]; // YYYY-MM-DD
                    var label = d.toLocaleDateString('it-IT', { day: '2-digit', month: 'short' });
                    if (!labelDateMap[key]) {
                        labelDateMap[key] = label;
                        labelsArr.push(key);
                    }
                });

                Object.keys(providerData).forEach(function(provider) {
                    var entries = providerData[provider];
                    var colors = providerColors[provider] || extraColors[colorIndex++ % extraColors.length] || providerColors['default'];
                    
                    // Map entries to the unified x-axis
                    var dataByDate = {};
                    entries.forEach(function(e) {
                        var key = new Date(e.created_at).toISOString().split('T')[0];
                        // If variant is holo, use trend_holo or avg_holo
                        var val = 0;
                        if(filterVariant === 'holo') {
                            val = e.trend_holo || e.avg_holo || e.trend || e.avg || 0;
                        } else {
                            val = e.trend || e.avg || 0;
                        }
                        dataByDate[key] = parseFloat(val);
                    });

                    var dataPoints = labelsArr.map(function(key) {
                        return dataByDate[key] !== undefined ? dataByDate[key] : null;
                    });

                    datasets.push({
                        label: provider.charAt(0).toUpperCase() + provider.slice(1),
                        data: dataPoints,
                        borderColor: colors.line,
                        backgroundColor: colors.bg,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: colors.line,
                        fill: true,
                        tension: 0.3,
                        spanGaps: true
                    });
                });

                var displayLabels = labelsArr.map(function(key) { return labelDateMap[key]; });

                var ctx = document.getElementById('cm-price-chart').getContext('2d');
                window._priceChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: displayLabels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: datasets.length > 1,
                                position: 'top',
                                labels: {
                                    color: 'rgba(212, 228, 250, 0.7)',
                                    font: { size: 11, weight: '600' },
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 16
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(5, 20, 36, 0.95)',
                                titleColor: '#d4e4fa',
                                bodyColor: '#d4e4fa',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': \u20ac ' + (context.parsed.y !== null ? context.parsed.y.toFixed(2) : '\u2014');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255, 255, 255, 0.04)', drawBorder: false },
                                ticks: { color: 'rgba(212, 228, 250, 0.4)', font: { size: 10 }, maxRotation: 45, autoSkip: true, maxTicksLimit: 12 }
                            },
                            y: {
                                grid: { color: 'rgba(255, 255, 255, 0.04)', drawBorder: false },
                                ticks: {
                                    color: 'rgba(212, 228, 250, 0.4)',
                                    font: { size: 10 },
                                    callback: function(value) { return '\u20ac ' + value.toFixed(2); }
                                },
                                beginAtZero: false
                            }
                        }
                    }
                });
            } else {
                chartContainer.style.display = 'none';
                chartEmpty.style.display = '';
            }
        };

        /* ── toggleCardWatchlist ────────────────────────────────────────────────── */
        window.toggleCardWatchlist = function(cardId) {
            var btn = document.getElementById('cm-monitor-btn');
            if (btn.disabled) return;

            // Loading state
            btn.disabled = true;
            var textEl = document.getElementById('cm-monitor-text');
            var originalText = textEl.textContent;
            textEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ...';

            fetch('/watchlist/card/' + cardId, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content || ''
                },
                credentials: 'same-origin'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var cardEls = document.querySelectorAll('.card-item[data-card-id="' + cardId + '"]');
                if (data.status === 'added') {
                    btn.style.backgroundColor = '#63b3ed';
                    btn.style.color = '#fff';
                    textEl.textContent = 'Monitorata';
                    if (window.showToast) window.showToast(data.message, 'success');
                    
                    cardEls.forEach(function(cardEl) {
                        var imgArea = cardEl.querySelector('.card-image-area');
                        if (imgArea && !imgArea.querySelector('.watchlisted-badge')) {
                            var badge = document.createElement('div');
                            badge.className = 'watchlisted-badge';
                            badge.style.cssText = "position:absolute; top:12px; right:12px; z-index:25; background:rgba(99,179,237,0.9); color:#fff; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.5);";
                            badge.title = "In Watchlist";
                            badge.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
                            if (imgArea.querySelector('.card-qty-badge')) {
                                badge.style.right = '42px';
                            }
                            imgArea.appendChild(badge);
                        }
                    });
                } else if (data.status === 'removed') {
                    btn.style.backgroundColor = 'rgba(99,179,237,0.15)';
                    btn.style.color = '#63b3ed';
                    textEl.textContent = 'Monitora Prezzo';
                    if (window.showToast) window.showToast(data.message, 'success');
                    
                    cardEls.forEach(function(cardEl) {
                        var badge = cardEl.querySelector('.watchlisted-badge');
                        if (badge) badge.remove();
                    });
                }
            })
            .catch(function(e) {
                console.error('Errore toggle watchlist', e);
                textEl.textContent = originalText;
                if (window.showToast) window.showToast('Errore durante l\'operazione', 'danger');
            })
            .finally(function() {
                btn.disabled = false;
            });
        };

    })();
</script>
