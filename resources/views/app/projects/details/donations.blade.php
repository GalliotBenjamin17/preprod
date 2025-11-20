<x-pages.projects.details-base :project="$project">
    <x-slot name="fullContent">
        @if ($donationsAffiliated > 0)
            <div class="pt-3 pb-5 px-3 bg-gray-50 border border-gray-300 rounded-md shadow-sm">
                <div class="flex justify-between mb-1">
                    <span class="text-base font-medium">Financement</span>
                    <div class="text-right">
                        <div class="text-sm font-medium">{{ format($donationsAffiliated) }} € TTC /
                            {{ format($project->cost_global_ttc ?? 0) }} € TTC</div>
                        <div class="text-xs text-gray-600">
                            {{ format(\App\Helpers\TVAHelper::getHT($donationsAffiliated)) }} € HT /
                            {{ format(\App\Helpers\TVAHelper::getHT($project->cost_global_ttc ?? 0)) }} € HT</div>
                    </div>
                </div>
                <div class="w-full bg-gray-300 rounded-full h-4 overflow-hidden dark:bg-gray-700">
                    @if ($project->cost_global_ttc > 0)
                        <div class="bg-green-600 text-xs font-medium text-white text-center p-0.5 leading-none rounded-full"
                            style="width: {{ $donationsAffiliated < $project->cost_global_ttc ? round(($donationsAffiliated / $project->cost_global_ttc) * 100) : '100' }}%">
                            {{ format(round(($donationsAffiliated / $project->cost_global_ttc) * 100)) }}%</div>
                    @else
                        <div class="bg-green-600 text-xs font-medium text-white text-center p-0.5 leading-none rounded-full"
                            style="width: 100%">100%</div>
                    @endif
                </div>
                @php
                    $costTtc = $project->cost_global_ttc ?? 0;
                    $remainingTtc = max($costTtc - $donationsAffiliated, 0);
                    $remainingHt = \App\Helpers\TVAHelper::getHT($remainingTtc);
                @endphp
                <div class="mt-2 text-xs text-gray-700">
                    Reste à financer: <span class="font-medium">{{ format($remainingTtc) }} € TTC</span>
                    ({{ format($remainingHt) }} € HT)
                </div>
            </div>
        @endif

        @if ($tonnesAffiliated > 0)
            <div class="pt-3 pb-5 px-3 bg-gray-50 border border-gray-300 rounded-md shadow-sm">
                <div class="flex justify-between mb-1">
                    <span class="text-base font-medium">Tonnes CO2 vendues (base HT)</span>
                    <span class="text-sm font-medium">{{ format($tonnesAffiliated) }} tCO2e /
                        {{ format($project->tco2 ?? 0) }} tCO2e</span>
                </div>
                @php
                    $tco2Total = $project->tco2 ?? 0;
                    $tco2Remaining = max($tco2Total - $tonnesAffiliated, 0);
                @endphp
                <div class="w-full bg-gray-300 rounded-full h-4 overflow-hidden dark:bg-gray-700">
                    @if ($project->tco2 > 0)
                        <div class="bg-blue-600 text-xs font-medium text-white text-center p-0.5 leading-none rounded-full"
                            style="width: {{ $tonnesAffiliated < $project->tco2 ? round(($tonnesAffiliated / $project->tco2) * 100) : '100' }}%">
                            {{ format(round(($tonnesAffiliated / $project->tco2) * 100)) }}%</div>
                    @else
                        <div class="bg-blue-600 text-xs font-medium text-white text-center p-0.5 leading-none rounded-full"
                            style="width: 100%">100%</div>
                    @endif
                </div>
                <div class="mt-2 text-xs text-gray-700">
                    Reste à financer: <span class="font-medium">{{ format($tco2Remaining) }} tCO2e</span>
                </div>
            </div>
        @endif

        @if ($parentSplitsWithRemaining->isNotEmpty())
            <div class="mt-6">
                <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-5 shadow-sm">
                    <div class="flex flex-col gap-y-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-amber-900">Contributions partiellement fléchées</p>
                            <p class="text-xs text-amber-800">
                                Ces contributions possèdent encore un montant à répartir vers un sous-projet.
                            </p>
                        </div>
                        <div class="text-xs font-medium text-amber-800">
                            {{ $parentSplitsWithRemaining->count() }} contribution(s)
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($parentSplitsWithRemaining as $split)
                            @php
                                $remainingAmount = max($split->amount - $split->childrenSplits->sum('amount'), 0);
                                $donorName = $split->donation?->related?->name ?? $split->donation?->related?->full_name ?? 'Donateur inconnu';
                            @endphp

                            <div
                                class="flex flex-col gap-y-2 rounded-lg border border-amber-100 bg-white/70 px-3 py-2 text-sm shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $donorName }}
                                    </p>
                                    <div class="text-xs text-gray-600 space-y-0.5">
                                        <p>
                                            Montant déjà fléché sur ce projet parent :
                                            <span class="font-semibold text-gray-800">
                                                {{ format($split->amount - $remainingAmount) }} € TTC
                                            </span>
                                        </p>
                                        <p>
                                            Reste à flécher {{ format($remainingAmount) }} € TTC —
                                            Contribution {{ \Illuminate\Support\Str::limit($split->donation_id, 8, '...') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    @php
                                        $openSplitUrl = str_replace("'", "\\'", request()->fullUrlWithQuery(['open_split' => $split->getKey()]));
                                    @endphp
                                    <button type="button"
                                        class="text-xs font-semibold text-primary-700 hover:text-primary-600 underline"
                                        onclick="
                                            const actionBtn = document.querySelector(`[data-split-action='{{ $split->getKey() }}']`);
                                            if (actionBtn) {
                                                actionBtn.click();
                                                actionBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                            } else {
                                                window.location = '{{ $openSplitUrl }}';
                                            }
                                        ">
                                        Flécher
                                    </button>

                                    <a href="{{ route('donations.show.split', ['donation' => $split->donation_id]) }}"
                                        class="text-xs font-semibold text-amber-800 underline hover:text-amber-900">
                                        Lien vers la contribution
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <x-layouts.card name="Contributions" :thin-padding="true">
            <x-slot:icon>
                {!! \App\Helpers\IconHelper::donationsIcon(size: 'sm') !!}
            </x-slot:icon>

            <x-slot:content>
                @if ($project->donation_splits_count > 0)
                    <style>
                        .filament-tables-table-container {
                            border-top-left-radius: 0px !important;
                            border-top-right-radius: 0px !important;
                        }
                    </style>

                    <livewire:tables.donations.donation-splits-project-table :project="$project" />
                @else
                    <div class="p-2.5 sm:p-[1rem] flex items-center flex-wrap gap-x-5 gap-y-2">
                        <x-empty-model content="Aucune contribution flǸchǸe" :model="new \App\Models\Donation()" class="col-span-4"
                            height="48" />
                    </div>
                @endif
            </x-slot:content>
        </x-layouts.card>
    </x-slot>
</x-pages.projects.details-base>
