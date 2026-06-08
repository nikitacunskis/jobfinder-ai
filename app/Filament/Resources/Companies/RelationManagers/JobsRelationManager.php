<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Jobs\JobResource;
use App\Models\Job;
use App\Services\SkillMatcher;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobs';

    protected static ?string $title = 'Vacancies';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Skills, keywords, title, description')
            ->searchable()
            ->searchUsing(function (Builder $query, string $search): void {
                $term = mb_strtolower(trim($search));

                if ($term === '') {
                    return;
                }

                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('lower(title) like ?', ["%{$term}%"])
                        ->orWhereRaw('lower(search_keyword) like ?', ["%{$term}%"])
                        ->orWhereRaw('lower(raw_job_description) like ?', ["%{$term}%"]);
                });
            })
            ->columns([
                TextColumn::make('title')->label('Title')->searchable()->wrap(),
                TextColumn::make('search_keyword')->label('Keyword')->badge(),
                TextColumn::make('location')->state(fn (Job $record): string => $record->locationText()),
                TextColumn::make('remote_type')->badge(),
                TextColumn::make('salary_original')->label('Salary')->formatStateUsing(fn (?string $state): string => $state && $state !== '-NOT MENTIONED-' ? $state : 'salary not mentioned'),
                TextColumn::make('matching')->state(fn (Job $record): int => count($record->displayMatchingSkills()))->badge(),
                TextColumn::make('missing')->state(fn (Job $record): int => count($record->displayMissingSkills()))->badge(),
                TextColumn::make('skills')->state(fn (Job $record): string => collect($record->displayAllSkills())->take(12)->join(', ') ?: 'No skills found')->wrap(),
            ])
            ->filters([
                Filter::make('skill')
                    ->schema([
                        Select::make('skill')
                            ->searchable()
                            ->options(fn (): array => app(SkillMatcher::class)->knownSkills()->mapWithKeys(fn (string $skill): array => [$skill => $skill])->all()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $skill = mb_strtolower(trim((string) ($data['skill'] ?? '')));

                        if ($skill === '') {
                            return $query;
                        }

                        $ids = $query->get()->filter(function (Job $job) use ($skill): bool {
                            return collect($job->displayAllSkills())
                                ->contains(fn (string $label): bool => str_contains(mb_strtolower($label), $skill));
                        })->pluck('id');

                        return $query->whereIn('id', $ids);
                    }),
            ])
            ->defaultSort('title')
            ->recordActions([
                Action::make('view')
                    ->label('Open')
                    ->url(fn (Job $record): string => JobResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
