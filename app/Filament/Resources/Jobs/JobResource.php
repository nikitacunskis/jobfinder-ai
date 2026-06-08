<?php

namespace App\Filament\Resources\Jobs;

use App\Filament\Resources\Jobs\Pages\CreateJob;
use App\Filament\Resources\Jobs\Pages\EditJob;
use App\Filament\Resources\Jobs\Pages\ListJobs;
use App\Filament\Resources\Jobs\Pages\ViewJob;
use App\Models\Job;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Vacancies';

    protected static ?string $modelLabel = 'Vacancy';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vacancy')
                ->schema([
                    Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
                    TextInput::make('title')->required()->columnSpanFull(),
                    TextInput::make('search_keyword'),
                    TextInput::make('language'),
                    TextInput::make('link')->url()->columnSpanFull(),
                    Textarea::make('raw_job_description')->rows(10)->columnSpanFull(),
                ]),
            Section::make('Details')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('country'),
                        TextInput::make('city'),
                        TextInput::make('remote_type'),
                        TextInput::make('salary_original'),
                        TextInput::make('eur_month_min')->numeric(),
                        TextInput::make('eur_month_max')->numeric(),
                        TextInput::make('posted_date'),
                        TextInput::make('applicant_count'),
                        TextInput::make('employment_type'),
                        Toggle::make('easy_apply'),
                        TextInput::make('poster_name'),
                        TextInput::make('poster_position'),
                    ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vacancy')
                ->schema([
                    Grid::make(4)->schema([
                        \Filament\Infolists\Components\TextEntry::make('company.name')->label('Company'),
                        \Filament\Infolists\Components\TextEntry::make('search_keyword')->label('Keyword')->badge(),
                        \Filament\Infolists\Components\TextEntry::make('language')->placeholder('Language unknown'),
                        \Filament\Infolists\Components\TextEntry::make('employment_type')->placeholder('Employment not captured'),
                        \Filament\Infolists\Components\TextEntry::make('location')->state(fn (Job $record): string => $record->locationText()),
                        \Filament\Infolists\Components\TextEntry::make('remote_type')->badge(),
                        \Filament\Infolists\Components\TextEntry::make('salary')->state(fn (Job $record): string => $record->salaryText())->badge(),
                        \Filament\Infolists\Components\IconEntry::make('easy_apply')->boolean(),
                    ]),
                    \Filament\Infolists\Components\TextEntry::make('link')->url(fn (?string $state): ?string => $state)->openUrlInNewTab()->placeholder('No link captured')->columnSpanFull(),
                ]),
            Section::make('Meta')
                ->schema([
                    Grid::make(4)->schema([
                        \Filament\Infolists\Components\TextEntry::make('posted_date')->placeholder('Not captured'),
                        \Filament\Infolists\Components\TextEntry::make('applicant_count')->placeholder('Not captured'),
                        \Filament\Infolists\Components\TextEntry::make('poster_name')->placeholder('Not captured'),
                        \Filament\Infolists\Components\TextEntry::make('poster_position')->placeholder('Not captured'),
                    ]),
                ]),
            Section::make('Skills')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('matching_skills_display')
                        ->label('Matching skills')
                        ->state(fn (Job $record): string => collect($record->displayMatchingSkills())->join(', ') ?: 'No matching skills found')
                        ->badge(),
                    \Filament\Infolists\Components\TextEntry::make('missing_skills_display')
                        ->label('Missing skills')
                        ->state(fn (Job $record): string => collect($record->displayMissingSkills())->join(', ') ?: 'No missing skills found')
                        ->badge(),
                    \Filament\Infolists\Components\TextEntry::make('all_skills_display')
                        ->label('Skills found in vacancy')
                        ->state(fn (Job $record): string => collect($record->displayAllSkills())->join(', ') ?: 'No known skills found')
                        ->columnSpanFull(),
                ]),
            Section::make('Description')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('raw_job_description')
                        ->hiddenLabel()
                        ->markdown()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('favorite')
                    ->label('Favorite')
                    ->state(fn (Job $record): bool => $record->isFavoritedBy(auth()->user()))
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => self::sortByFavorite($query, $direction)),
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('company.name')->label('Company')->searchable()->sortable(),
                TextColumn::make('search_keyword')->label('Keyword')->badge()->searchable(),
                TextColumn::make('location')->state(fn (Job $record): string => $record->locationText()),
                TextColumn::make('remote_type')->badge(),
                TextColumn::make('salary_original')->label('Salary')->formatStateUsing(fn (?string $state): string => $state && $state !== '-NOT MENTIONED-' ? $state : 'salary not mentioned'),
                IconColumn::make('easy_apply')->boolean(),
                TextColumn::make('matching')->state(fn (Job $record): int => count($record->displayMatchingSkills()))->badge(),
                TextColumn::make('missing')->state(fn (Job $record): int => count($record->displayMissingSkills()))->badge(),
            ])
            ->filters([
                Filter::make('favorite')
                    ->label('Favorites')
                    ->query(fn (Builder $query): Builder => $query->whereHas('favorites', fn (Builder $query): Builder => $query->where('user_id', auth()->id()))),
                SelectFilter::make('company')->relationship('company', 'name')->searchable()->preload(),
            ])
            ->defaultSort('title')
            ->recordActions([
                Action::make('favorite')
                    ->label(fn (Job $record): string => $record->isFavoritedBy(auth()->user()) ? 'Unfavorite' : 'Favorite')
                    ->icon(fn (Job $record): string => $record->isFavoritedBy(auth()->user()) ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (Job $record): string => $record->isFavoritedBy(auth()->user()) ? 'warning' : 'gray')
                    ->action(fn (Job $record): bool => auth()->user()->toggleFavorite($record)),
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobs::route('/'),
            'create' => CreateJob::route('/create'),
            'view' => ViewJob::route('/{record}'),
            'edit' => EditJob::route('/{record}/edit'),
        ];
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
                where favorites.favoritable_id = jobs.id
                    and favorites.favoritable_type = ?
                    and favorites.user_id = ?
            ) '.$direction,
            [(new Job())->getMorphClass(), $userId],
        );
    }
}
