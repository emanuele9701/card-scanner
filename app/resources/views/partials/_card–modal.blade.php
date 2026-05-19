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
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 id="cm-name" class="fw-bold mb-0" style="font-size:1.125rem; color:#d4e4fa;">...</h2>
                <span id="cm-rarity" class="badge mt-1"
                    style="background:rgba(251,180,0,0.15); color:#fbb400;
                           border:1px solid rgba(251,180,0,0.3);
                           font-size:0.7rem; font-weight:600; display:none;"></span>
            </div>
            <button onclick="closeModal()" class="btn-modal-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>
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
                                <th class="modal-th">{{ __('Media 1g') }}</th>
                                <th class="modal-th">{{ __('Media 7g') }}</th>
                                <th class="modal-th">{{ __('Media 30g') }}</th>
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

            /* Storico prezzi */
            var tbody = document.getElementById('cm-prices-tbody');
            if (card.prices && card.prices.length) {
                tbody.innerHTML = card.prices.map(function(p) {
                    var d = p.created_at ?
                        new Date(p.created_at).toLocaleDateString('it-IT', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }) :
                        '—';
                    return '<tr>' +
                        '<td class="modal-td">' + _esc(d) + '</td>' +
                        '<td class="modal-td" style="color:#fbb400;font-weight:600;">' + _price(p.trend) +
                        '</td>' +
                        '<td class="modal-td">' + _price(p.avg_1d) + '</td>' +
                        '<td class="modal-td">' + _price(p.avg_7d) + '</td>' +
                        '<td class="modal-td">' + _price(p.avg_30d) + '</td>' +
                        '<td class="modal-td" style="color:rgba(212,228,250,0.45);font-size:0.75rem;">' +
                        _esc(p.provider || '—') + '</td>' +
                        '</tr>';
                }).join('');
            } else {
                tbody.innerHTML =
                    '<tr><td colspan="6" class="modal-td" ' +
                    'style="text-align:center;color:rgba(212,228,250,0.3);padding:1.5rem 0;">' +
                    (window.__trans ? window.__trans.no_price : 'Nessun prezzo disponibile') + '</td></tr>';
            }
        }

    })();
</script>
