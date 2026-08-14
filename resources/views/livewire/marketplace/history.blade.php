<div>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                        {{ __('History') }}: {{ $item->name }}
                    </h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.marketplace.index') }}" class="text-white bg-zinc-700 hover:bg-zinc-600 px-3 py-1.5 rounded-full text-sm font-medium">
                        <span>{{ __('← Items') }}</span>
                    </a>
                    @can('marketplace.edit')
                        @if (! $item->trashed())
                            <a href="{{ route('admin.marketplace.edit', $item->id) }}" class="text-sm bg-slate-600 hover:bg-slate-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium">
                                {{ __('Edit item') }}
                            </a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-200/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('When') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Who') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Event') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Changes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($audits as $audit)
                                <tr class="hover:bg-gray-50 transition-colors align-top">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ $audit->created_at?->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ $audit->user?->name ?? __('System') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                            @if ($audit->event === 'created') bg-green-100 text-green-800
                                            @elseif ($audit->event === 'archived') bg-red-100 text-red-800
                                            @elseif ($audit->event === 'restored') bg-emerald-100 text-emerald-800
                                            @else bg-gray-100 text-gray-800
                                            @endif
                                        ">{{ $audit->eventLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @php $changes = $audit->changes ?? []; @endphp
                                        @if ($changes === [])
                                            <span class="text-gray-400">—</span>
                                        @else
                                            <ul class="space-y-1">
                                                @foreach ($changes as $field => $diff)
                                                    <li @class([
                                                        'text-sm',
                                                        'font-semibold text-orange-700' => \App\Models\MarketplaceItemAudit::isHighlightedField($field),
                                                        'text-gray-700' => ! \App\Models\MarketplaceItemAudit::isHighlightedField($field),
                                                    ])>
                                                        <span class="font-medium">{{ \App\Models\MarketplaceItemAudit::fieldLabel($field) }}:</span>
                                                        <span class="text-gray-500">{{ \App\Models\MarketplaceItemAudit::formatValue($field, $diff['old'] ?? null, $lookups) }}</span>
                                                        <span class="text-gray-400">→</span>
                                                        <span>{{ \App\Models\MarketplaceItemAudit::formatValue($field, $diff['new'] ?? null, $lookups) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-gray-600">{{ __('No change history yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($audits->hasPages())
                <div>
                    {{ $audits->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
