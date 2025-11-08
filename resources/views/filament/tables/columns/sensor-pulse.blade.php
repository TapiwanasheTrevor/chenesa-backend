@php
    $record = $getRecord();
    $lastSeen = $record->last_seen;
    $isRecentlyActive = $lastSeen && $lastSeen->diffInSeconds(now()) < 60;

    // Determine status based on last seen time
    $hoursSince = $lastSeen ? $lastSeen->diffInHours(now()) : 999;
    $dotColor = match(true) {
        $hoursSince < 10 => 'green',   // Active: < 10 hours
        $hoursSince < 24 => 'orange',  // Delayed: 10-24 hours
        default => 'gray'              // Offline: > 24 hours
    };
@endphp

<div
    x-data="{
        dotColor: '{{ $dotColor }}',
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
            class="absolute inset-0 rounded-full"
            :class="{
                'bg-green-500': dotColor === 'green',
                'bg-orange-500': dotColor === 'orange',
                'bg-gray-500': dotColor === 'gray'
            }"
        ></div>

        <!-- Main pulse dot -->
        <div
            class="relative w-4 h-4 rounded-full transition-colors duration-300"
            :class="{
                'bg-green-500 shadow-lg shadow-green-500/50': dotColor === 'green' && pulsing,
                'bg-green-500': dotColor === 'green' && !pulsing,
                'bg-orange-500 shadow-lg shadow-orange-500/50': dotColor === 'orange' && pulsing,
                'bg-orange-500': dotColor === 'orange' && !pulsing,
                'bg-gray-400': dotColor === 'gray'
            }"
        ></div>
    </div>
</div>

<style>
    .scale-200 {
        transform: scale(2);
    }
</style>