<footer class="mt-auto py-4 border-top text-center text-secondary pb-5 pb-md-4" style="background-color: rgba(3, 7, 18, 0.8); border-color: rgba(255, 255, 255, 0.05) !important;">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 fw-semibold" style="color: #d1d5db;">
                <div class="navbar-brand-icon" style="width: 24px; height: 24px;">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
                PokeStash &copy; {{ date('Y') }}
            </div>
            <div class="d-flex gap-4" style="font-size: 0.85rem;">
                <a href="#" class="text-secondary text-decoration-none hover-text-white transition">{{ __('Privacy Policy') }}</a>
                <a href="#" class="text-secondary text-decoration-none hover-text-white transition">{{ __('Terms of Service') }}</a>
            </div>
        </div>
    </div>
    <style>
        .hover-text-white:hover { color: #fff !important; }
        .transition { transition: all 0.2s; }
    </style>
</footer>
