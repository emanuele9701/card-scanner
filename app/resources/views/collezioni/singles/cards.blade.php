@php
    $isCollected = $card->isCollected ?? false;

    $rarityConfig = match (strtolower($card->rarity ?? '')) {
        'ultra rare', 'ultra-rare', 'secret rare' => [
            'label' => 'Ultra Rare',
            'chip' => 'bg-[#ffb3b1]/[0.08] border border-[#ffb3b1]/30 text-[#ffb3b1]',
            'dot' => 'bg-[#ffb3b1]',
        ],
        'rare', 'rare holo' => [
            'label' => $card->rarity === 'rare holo' ? 'Rare Holo' : 'Rare',
            'chip' => 'bg-[#ffd795]/[0.08] border border-[#ffd795]/30 text-[#ffd795]',
            'dot' => 'bg-[#ffd795]',
        ],
        'uncommon' => [
            'label' => 'Uncommon',
            'chip' => 'bg-[#69d4f4]/[0.08] border border-[#69d4f4]/25 text-[#69d4f4]',
            'dot' => 'bg-[#69d4f4]',
        ],
        default => [
            'label' => 'Common',
            'chip' => 'bg-[#d4e4fa]/[0.06] border border-[#d4e4fa]/15 text-[#a0b4cc]',
            'dot' => 'bg-[#a0b4cc]',
        ],
    };
@endphp

<div data-card-id="{{ $card->id }}"
    :class="isSelected({{ $card->id }}) ? 'border-[#fbb400]/60 shadow-[0_0_20px_rgba(251,180,0,0.2)]' :
        'border-white/[0.08]'"
    class="
        group relative flex flex-col overflow-hidden rounded-[1.5rem] cursor-pointer
        aspect-[2.5/4]
        bg-gradient-to-br from-[#1c2b3c] to-[#122131]
        border transition-all duration-300 ease-out
        hover:-translate-y-1.5 hover:scale-[1.02]
        hover:shadow-[0_0_28px_rgba(251,180,0,0.25),0_12px_40px_rgba(0,0,0,0.55)]
        {{ $isCollected ? '' : 'opacity-60' }}
    ">
    {{-- Highlight vetro --}}
    <div
        class="pointer-events-none absolute inset-0 z-10 rounded-[1.5rem] bg-gradient-to-br from-[#ffb3b1]/[0.04] to-transparent">
    </div>

    {{-- Checkbox selezione (solo se non raccolta) --}}
    @if (!$isCollected)
        <div @click.stop="toggleSelect({{ $card->id }})"
            :class="isSelected({{ $card->id }}) ?
                'border-[#fbb400] bg-[#fbb400] opacity-100' :
                'border-white/20 bg-[#051424]/60 opacity-0 group-hover:opacity-100'"
            class="absolute left-2.5 top-2.5 z-30 flex h-6 w-6 cursor-pointer items-center justify-center rounded-md border backdrop-blur-md transition-all">
            <svg x-show="isSelected({{ $card->id }})" width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M2 6l3 3 5-5" stroke="#422c00" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
    @endif

    {{-- Area immagine --}}
    <div class="relative min-h-64 flex-1 overflow-hidden bg-gradient-to-b from-[#0d1c2d] to-[#1c2b3c]">

        @if ($card->url_image)
            <img src="{{ $card->url_image }}/high.png" alt="{{ $card->name }}" class="h-full w-full object-cover"
                loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center">
                <svg class="h-14 w-14 opacity-15 text-[#d4e4fa]" viewBox="0 0 80 80" fill="none">
                    <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="2" />
                    <circle cx="40" cy="40" r="18" fill="currentColor" opacity="0.4" />
                    <rect x="4" y="38" width="72" height="4" fill="currentColor" opacity="0.5" />
                </svg>
            </div>
        @endif

        {{-- Badge numero --}}
        <div
            class="absolute left-2.5 top-2.5 z-20 rounded-md border border-white/[0.07] bg-[#051424]/70 px-1.5 py-0.5 font-mono text-[10px] tracking-widest text-[#d4e4fa]/50 backdrop-blur-md">
            #{{ str_pad($card->dexId, 3, '0', STR_PAD_LEFT) }}
        </div>

        {{-- Badge raccolta --}}
        @if ($isCollected)
            <div
                class="absolute right-2.5 top-2.5 z-20 flex h-6 w-6 items-center justify-center rounded-full border border-[#fbb400]/40 bg-[#fbb400]/12 backdrop-blur-md">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M2 6l3 3 5-5" stroke="#ffd795" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
        @endif

        {{-- Overlay bottoni su hover --}}
        <div
            class="absolute inset-0 z-20 flex flex-col items-center justify-end gap-2 bg-gradient-to-t from-[#051424]/90 via-[#051424]/40 to-transparent p-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100">

            <button
                @click.stop="openModal({{ json_encode(['id' => $card->id, 'name' => $card->name, 'image' => $card->url_image]) }})"
                class="w-full rounded-xl border border-white/15 bg-white/10 py-2 text-xs font-semibold text-[#d4e4fa] backdrop-blur-md transition hover:bg-white/20">
                Vedi dettagli
            </button>

            @if (!$isCollected)
                <button @click.stop="addToCollection([{{ $card->id }}])"
                    class="w-full rounded-xl bg-[#fbb400] py-2 text-xs font-bold text-[#422c00] transition hover:bg-[#ffd795]">
                    + Aggiungi
                </button>
            @endif

        </div>
    </div>

    {{-- Footer --}}
    <div class="relative z-20 border-t border-white/[0.06] bg-[#051424]/85 px-3.5 py-3 backdrop-blur-xl">
        <p class="mb-1.5 truncate text-[13px] font-bold leading-tight text-[#d4e4fa]">{{ $card->name }}</p>
        <div class="flex items-center justify-between gap-1">
            <span
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.06em] backdrop-blur-md {{ $rarityConfig['chip'] }}">
                <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full {{ $rarityConfig['dot'] }}"></span>
                {{ $rarityConfig['label'] }}
            </span>
            @if ($card->type)
                <span
                    class="text-[10px] font-semibold uppercase tracking-[0.04em] text-[#d4e4fa]/35">{{ $card->type }}</span>
            @endif
        </div>
    </div>
</div>
