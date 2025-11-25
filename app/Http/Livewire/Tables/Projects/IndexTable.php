<?php

namespace App\Http\Livewire\Tables\Projects;

use App\Enums\Roles;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\UserTablePreference;
use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class IndexTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public ?Organization $organization = null;

    public ?Project $project = null;

    protected string $tablePreferenceKey = 'projects.index.table';

    protected array $activeSavedFilters = [];

    public array $toggledTableColumns = [];

    public function mount(): void
    {
        $this->loadUserColumnToggles();

        if (request()->boolean('resetFilters')) {
            $this->clearSavedFiltersForUser();
            $this->redirect(request()->url(), navigate: true);
            return;
        }

        $currentFilters = $this->getCurrentFiltersFromRequest();
        if (! empty($currentFilters)) {
            $this->activeSavedFilters = $currentFilters;
            $this->saveFiltersArrayForUser($currentFilters);
        } else {
            $this->activeSavedFilters = $this->getSavedFiltersForUser();
        }
    }

    protected function getTableQuery(): Builder
    {
        $user = request()->user()->load([
            'organizations',
        ]);

        return Project::with([
            'tenant',
            'sponsor',
            'auditors',
            'referent',
            'createdBy',
            'certification',
            'segmentation',
            'methodForm',
            'donationSplits.childrenSplits',
        ])
            ->withCount([
                'childrenProjects',
            ])
            ->when($this->organization, function ($query) {
                return $query->where('sponsor_id', $this->organization->id)
                    ->where('sponsor_type', get_class($this->organization));
            })->when($this->project, function ($query) {
                return $query->where('parent_project_id', $this->project->id);
            }, function ($query) {
                return $query->whereNull('parent_project_id');
            })
            ->when($user->hasRole(Roles::Referent), function ($query) use ($user) {
                return $query->where('referent_id', $user->id);
            })
            ->when($user->hasRole(Roles::Auditor), function ($query) use ($user) {
                return $query->whereRelation('auditors', 'id', '=', $user->id);
            })
            ->when($user->hasRole(Roles::Sponsor), function ($query) use ($user) {
                return $query->where('sponsor_id', $user->id);
            })
            ->when($user->hasRole(Roles::Member), function ($query) use ($user) {
                return $query->whereIn('sponsor_id', $user->organizations->pluck('id')->toArray());
            })
            ->when($user->hasRole(Roles::Partner), function ($query) use ($user) {
                return $query->whereHas('projectPartners', function ($q) use ($user) {
                    return $q->whereIn('partner_id', $user->partners()->pluck('id')->toArray());
                });
            })
            ->when($this->getFilterParam('cf_id'), fn ($query, $value) => $this->applyMultiFilter($query, 'certification_id', $value))
            ->when($this->getFilterParam('mf_id'), fn ($query, $value) => $this->applyMultiFilter($query, 'method_form_id', $value))
            ->when($this->getFilterParam('sg_id'), fn ($query, $value) => $this->applyMultiFilter($query, 'segmentation_id', $value))
            ->when($this->getFilterParam('st'), fn ($query, $value) => $this->applyMultiFilter($query, 'state', $value));
    }

    protected function getTableRecordUrlUsing(): Closure
    {
        return fn (Model $record): string => route('projects.show.details', ['project' => $record->slug]);
    }

    public function updatedToggledTableColumns(): void
    {
        session()->put([
            $this->getTableColumnToggleFormStateSessionKey() => $this->toggledTableColumns,
        ]);

        if (! $this->canPersistPreferences()) {
            return;
        }

        if ($user = request()->user()) {
            UserTablePreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'table_key' => $this->tablePreferenceKey,
                ],
                [
                    'toggled_columns' => $this->toggledTableColumns,
                ],
            );
        }
    }

    protected function loadUserColumnToggles(): void
    {
        if (! $this->canPersistPreferences()) {
            return;
        }

        $user = request()->user();
        if (! $user) {
            return;
        }

        $pref = UserTablePreference::where('user_id', $user->id)
            ->where('table_key', $this->tablePreferenceKey)
            ->first();

        if ($pref && is_array($pref->toggled_columns)) {
            $this->toggledTableColumns = $pref->toggled_columns;

            session()->put([
                $this->getTableColumnToggleFormStateSessionKey() => $this->toggledTableColumns,
            ]);
        }
    }

    protected function getCurrentFiltersFromRequest(): array
    {
        return collect(request()->only(['cf_id', 'mf_id', 'sg_id', 'st']))
            ->filter(fn ($value) => filled($value))
            ->toArray();
    }

    protected function saveFiltersArrayForUser(array $filters): void
    {
        if (! $this->canPersistPreferences()) {
            return;
        }

        $user = request()->user();
        if (! $user) {
            return;
        }

        UserTablePreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'table_key' => $this->tablePreferenceKey,
            ],
            [
                'saved_filters' => $filters,
            ]
        );
    }

    protected function clearSavedFiltersForUser(): void
    {
        if (! $this->canPersistPreferences()) {
            return;
        }

        $user = request()->user();
        if (! $user) {
            return;
        }

        UserTablePreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'table_key' => $this->tablePreferenceKey,
            ],
            [
                'saved_filters' => null,
            ]
        );
    }

    protected function getSavedFiltersForUser(): array
    {
        if (! $this->canPersistPreferences()) {
            return [];
        }

        $user = request()->user();
        if (! $user) {
            return [];
        }

        return UserTablePreference::where('user_id', $user->id)
            ->where('table_key', $this->tablePreferenceKey)
            ->value('saved_filters') ?? [];
    }

    protected function getFilterParam(string $key)
    {
        $value = request()->input($key);

        return filled($value) ? $value : ($this->activeSavedFilters[$key] ?? null);
    }

    protected function normalizeFilterValues($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(explode(',', $raw)));
    }

    protected function applyMultiFilter(Builder $query, string $column, $raw): Builder
    {
        $values = $this->normalizeFilterValues($raw);
        if (count($values) === 0) {
            return $query;
        }

        return count($values) === 1
            ? $query->where($column, $values[0])
            : $query->whereIn($column, $values);
    }

    protected function canPersistPreferences(): bool
    {
        return Schema::hasTable('user_table_preferences');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Nom')
                ->limit(200)
                ->tooltip(fn (Model $record): string => $record->name)
                ->grow(false)
                ->description(function (Model $record): string {
                    return $record->tenant ? $record->tenant->name : 'Projet national';
                })
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->orderBy('name', $direction);
                })
                ->searchable()
                ->toggleable(),

            TextColumn::make('certification.name')
                ->label('Certification')
                ->default('-')
                ->toggleable(),
            TextColumn::make('segmentation.name')
                ->label('Segmentation')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('methodForm.name')
                ->label('Méthode')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('state')
                ->label('Statut')
                ->formatStateUsing(fn (Project $record) => $record->state?->humanName() ?? '-')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('sponsor.name')
                ->label('Porteur')
                ->description(function (Model $record): string {
                    return match ($record->sponsor ? get_class($record->sponsor) : null) {
                        User::class => 'Acteur',
                        Organization::class => 'Organisation',
                        default => 'Type de porteur inconnu'
                    };
                })
                ->toggleable(),
            TextColumn::make('auditor.name')
                ->default('-')
                ->label('Auditeur')
                ->toggleable(),
            TextColumn::make('referent.name')
                ->default('-')
                ->label('Référent')
                ->toggleable(),

            TextColumn::make('id')
                ->formatStateUsing(function (Project $record) {
                    if (! isset($record->cost_global_ttc) || ! is_numeric($record->cost_global_ttc)) {
                        return '-';
                    }

                    $amountWanted = (float) $record->cost_global_ttc;
                    $contributionsReceived = optional($record->donationSplits)->sum('amount') ?? 0.0;
                    $remainingToFund = $amountWanted - $contributionsReceived;

                    return format(max(0, $remainingToFund), 2).' €';
                })
                ->label('Reste à financer (TTC)')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('Démarrage et dépôt')
                ->formatStateUsing(function (Project $record): ?string {
                    return "Démarrage : " . $record->created_at->format('d/m/Y');
                })
                ->description(function (Model $record): ?string {
                    return 'Dépôt le '.$record->created_at->format('d/m/Y');
                })
                ->sortable(['created_at'])
                ->toggleable(),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('synchronize')
                ->label('Synchroniser avec le projet parent')
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    if ($records->count() == 0) {
                        return;
                    }

                    $records->toQuery()->update([
                        'is_synchronized_with_parent' => true
                    ]);

                    defaultSuccessNotification("Tous les projets sélectionnés sont maintenant synchronisés.");
                })
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return (bool) ! $this->project;
    }

    public function render()
    {
        return view('livewire.tables.projects.index-table');
    }
}
