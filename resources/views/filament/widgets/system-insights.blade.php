<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Performance Indicators</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($insights as $insight)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="p-2 rounded-lg bg-{{ $insight['color'] }}-100 dark:bg-{{ $insight['color'] }}-900">
                                    <x-dynamic-component :component="$insight['icon']" class="w-5 h-5 text-{{ $insight['color'] }}-600 dark:text-{{ $insight['color'] }}-400" />
                                </div>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $insight['value'] }}
                                </span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $insight['title'] }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $insight['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Recommendations</h3>
                <div class="space-y-3">
                    @foreach ($recommendations as $recommendation)
                        <div class="flex items-start space-x-3 p-3 rounded-lg
                            @if($recommendation['type'] === 'urgent') bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                            @elseif($recommendation['type'] === 'warning') bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800
                            @elseif($recommendation['type'] === 'info') bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800
                            @else bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800
                            @endif">
                            <div class="flex-shrink-0">
                                @if($recommendation['type'] === 'urgent')
                                    <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-600 dark:text-red-400" />
                                @elseif($recommendation['type'] === 'warning')
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                                @elseif($recommendation['type'] === 'info')
                                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                @else
                                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                                @endif
                            </div>
                            <p class="text-sm
                                @if($recommendation['type'] === 'urgent') text-red-800 dark:text-red-200
                                @elseif($recommendation['type'] === 'warning') text-yellow-800 dark:text-yellow-200
                                @elseif($recommendation['type'] === 'info') text-blue-800 dark:text-blue-200
                                @else text-green-800 dark:text-green-200
                                @endif">
                                {{ $recommendation['message'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>