<div class="fi-ta-top-without-corner fi-ta-extra-thin">
    {{ $this->table }}
</div>

@push('styles')
    <style>
        .fi-ta-header-toolbar .fi-ta-filters-modal,
        .fi-ta-header-toolbar .fi-ta-filters-dropdown {
            display: none !important;
        }

        .fi-dd label {
            cursor: pointer;
        }
    </style>
@endpush

@php
    $filterOptions = [
        'certification' => \App\Models\Certification::pluck('name', 'id'),
        'method' => \App\Models\MethodForm::pluck('name', 'id'),
        'state' => \App\Enums\Models\Projects\ProjectStateEnum::toArray(),
        'segmentation' => \App\Models\Segmentation::pluck('name', 'id'),
    ];
@endphp

@push('scripts')
    <script>
        (function () {
            const PARAMS = {certification: 'cf_id', method: 'mf_id', state: 'st', segmentation: 'sg_id'};
            const LABELS = {certification: 'certification', method: 'méthode', state: 'statut', segmentation: 'segmentation'};
            const OPTIONS = @json($filterOptions);
            let openedDropdown = null;

            function closeDropdown() {
                if (openedDropdown) {
                    openedDropdown.remove();
                    openedDropdown = null;
                }
            }

            function buildDropdown(btn, key) {
                closeDropdown();

                const dd = document.createElement('div');
                dd.className = 'fi-dd absolute z-[1000] mt-2 w-64 rounded-md border border-gray-200 bg-white p-2 text-sm shadow-lg';
                const rect = btn.getBoundingClientRect();
                dd.style.top = (rect.bottom + window.scrollY) + 'px';
                dd.style.left = (rect.left + window.scrollX) + 'px';

                const url = new URL(window.location.href);
                const param = PARAMS[key];
                const selected = (url.searchParams.get(param) || '').split(',').filter(Boolean);
                const options = OPTIONS[key] || {};

                const items = Object.entries(options).map(function ([id, label]) {
                    const checked = selected.includes(String(id)) ? ' checked' : '';
                    return '<label class="flex items-center gap-2 py-1"><input type="checkbox" value="' + id + '"' + checked + '> <span>' + label + '</span></label>';
                }).join('');

                dd.innerHTML = '<div class="mb-2 font-semibold">Filtrer par ' + (LABELS[key] || key) + '</div>' +
                    '<div class="max-h-64 overflow-auto pr-1">' + items + '</div>' +
                    '<div class="mt-2 flex gap-2">' +
                    '<button type="button" class="px-2 py-1 border rounded bg-blue-600 text-white" data-action="apply">Appliquer</button>' +
                    '<button type="button" class="px-2 py-1 border rounded" data-action="clear">Effacer</button>' +
                    '</div>';

                document.body.appendChild(dd);
                openedDropdown = dd;

                dd.querySelector('[data-action="apply"]').addEventListener('click', function () {
                    const values = Array.from(dd.querySelectorAll('input[type=checkbox]:checked')).map(function (o) {
                        return o.value;
                    });
                    if (values.length) {
                        url.searchParams.set(param, values.join(','));
                    } else {
                        url.searchParams.delete(param);
                    }
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                });

                dd.querySelector('[data-action="clear"]').addEventListener('click', function () {
                    url.searchParams.delete(param);
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                });
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-fi-funnel]');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const key = btn.getAttribute('data-fi-funnel');
                    if (PARAMS[key]) {
                        buildDropdown(btn, key);
                    }
                } else if (openedDropdown && !openedDropdown.contains(e.target)) {
                    closeDropdown();
                }
            });
        })();
    </script>
@endpush
