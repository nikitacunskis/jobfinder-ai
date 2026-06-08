<?php

namespace App\Filament\Resources\Companies;

use App\Enums\CompanyStatus;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\RelationManagers\ContactLogsRelationManager;
use App\Filament\Resources\Companies\RelationManagers\JobsRelationManager;
use App\Filament\Resources\Companies\RelationManagers\OfferRecordsRelationManager;
use App\Models\Company;
use App\Models\Job;
use App\Services\JobsucherSearch;
use App\Services\SkillMatcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('industry')->maxLength(255),
                Select::make('status')
                    ->options(self::statusOptions())
                    ->required(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company')
                ->schema([
                    Grid::make(4)->schema([
                        \Filament\Infolists\Components\TextEntry::make('name'),
                        \Filament\Infolists\Components\TextEntry::make('industry')->placeholder('Industry not captured'),
                        \Filament\Infolists\Components\TextEntry::make('status')->badge()->formatStateUsing(fn (CompanyStatus|string|null $state): string => $state instanceof CompanyStatus ? $state->label() : (string) $state),
                        \Filament\Infolists\Components\TextEntry::make('status_updated_at')->dateTime(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('jobs')
                ->withCount(['jobs', 'contactLogs', 'offerRecords'])
                ->withMax('contactLogs', 'contact_at'))
            ->searchPlaceholder('Company, job title, skills, description')
            ->searchUsing(function (Builder $query, string $search): void {
                $ids = app(JobsucherSearch::class)->searchCompanyIds($search, null);

                if ($ids !== null) {
                    $query->whereIn('id', $ids);
                }
            })
            ->columns([
                IconColumn::make('favorite')
                    ->label('Favorite')
                    ->state(fn (Company $record): bool => $record->isFavoritedBy(auth()->user()))
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => self::sortByFavorite($query, $direction)),
                TextColumn::make('name')->label('Company')->searchable()->sortable()->description(fn (Company $record): string => $record->industry ?: 'Industry not captured'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (CompanyStatus|string|null $state): string => $state instanceof CompanyStatus ? $state->label() : (string) $state)->sortable(),
                TextColumn::make('jobs_count')->label('Jobs')->sortable(),
                TextColumn::make('contact_logs_count')->label('Contacts')->sortable(),
                TextColumn::make('offer_records_count')->label('Offers')->sortable(),
                TextColumn::make('contact_logs_max_contact_at')->label('Last contact')->dateTime()->placeholder('No contact yet')->sortable(),
                TextColumn::make('matching_skills_count')
                    ->label('Matching skills')
                    ->state(fn (Company $record): int => $record->jobs->sum(fn (Job $job): int => count($job->displayMatchingSkills())))
                    ->badge(),
                TextColumn::make('job_skills')
                    ->label('Job skills')
                    ->state(fn (Company $record): string => collect($record->jobs)
                        ->flatMap(fn (Job $job): array => app(SkillMatcher::class)->allSkillsFor($job))
                        ->unique(fn (string $skill): string => mb_strtolower($skill))
                        ->take(10)
                        ->join(', ') ?: 'No skills captured')
                    ->wrap(),
            ])
            ->filters([
                Filter::make('favorite')
                    ->label('Favorites')
                    ->query(fn (Builder $query): Builder => $query->whereHas('favorites', fn (Builder $query): Builder => $query->where('user_id', auth()->id()))),
                SelectFilter::make('status')->options(self::statusOptions()),
                Filter::make('skill')
                    ->schema([
                        Select::make('skill')
                            ->label('Skill')
                            ->searchable()
                            ->options(fn (): array => app(SkillMatcher::class)->knownSkills()->mapWithKeys(fn (string $skill): array => [$skill => $skill])->all()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $skill = (string) ($data['skill'] ?? '');
                        $ids = app(JobsucherSearch::class)->searchCompanyIds(null, $skill);

                        return $ids === null ? $query : $query->whereIn('id', $ids);
                    }),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('favorite')
                    ->label(fn (Company $record): string => $record->isFavoritedBy(auth()->user()) ? 'Unfavorite' : 'Favorite')
                    ->icon(fn (Company $record): string => $record->isFavoritedBy(auth()->user()) ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (Company $record): string => $record->isFavoritedBy(auth()->user()) ? 'warning' : 'gray')
                    ->action(fn (Company $record): bool => auth()->user()->toggleFavorite($record)),
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            JobsRelationManager::class,
            ContactLogsRelationManager::class,
            OfferRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }

    private static function statusOptions(): array
    {
        return collect(CompanyStatus::cases())
            ->mapWithKeys(fn (CompanyStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    private static function sortByFavorite(Builder $query, string $direction): Builder
    {
        $userId = auth()->id();

        if ($userId === null) {
            return $query;
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderByRaw(
            'exists (
                select 1
                from favorites
                where favorites.favoritable_id = companies.id
                    and favorites.favoritable_type = ?
                    and favorites.user_id = ?
            ) '.$direction,
            [(new Company())->getMorphClass(), $userId],
        );
    }
}
