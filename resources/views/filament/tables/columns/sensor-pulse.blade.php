@php
    $record = $getRecord();
    $lastSeen = $record->last_seen;
    $isRecentlyActive = $lastSeen && $lastSeen->diffInSeconds(now()) < 15;
@endphp

<div
    x-data="{
        pulsing: {{ $isRecentlyActive ? 'true' : 'false' }},
        lastSeen: '{{ $lastSeen?->toISOString() }}',
        init() {
            if (this.pulsing) {
                setTimeout(() => {
                    this.pulsing = false;
                }, 2000);
            }
        }
    }"
    class="flex items-center justify-center"
>
    <div class="relative w-4 h-4">
        <!-- Ping animation -->
        <div
            x-show="pulsing"
            x-transition:leave="transition ease-in duration-1000"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-200"
            class="absolute inset-0 rounded-full bg-green-500"
        ></div>

        <!-- Main pulse dot -->
        <div
            class="relative w-4 h-4 rounded-full transition-colors duration-300"
            :class="pulsing ? 'bg-green-500 shadow-lg shadow-green-500/50' : 'bg-gray-400'"
        ></div>
    </div>
</div>

<style>
    .scale-200 {
        transform: scale(2);
    }
</style>