<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Enums\CompanyStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OfferRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'offerRecords';

    protected static ?string $title = 'Offer records';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('job_id')
                ->label('Job')
                ->relationship('job', 'title')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options([
                    CompanyStatus::OfferReceived->value => CompanyStatus::OfferReceived->label(),
                    CompanyStatus::JobDeclined->value => CompanyStatus::JobDeclined->label(),
                ])
                ->required(),
            TextInput::make('amount_money')->label('Amount of money')->maxLength(255),
            TextInput::make('documents')->maxLength(255),
            DateTimePicker::make('declined_at')->label('Declined at'),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => CompanyStatus::tryFrom($state)?->label() ?? $state),
                TextColumn::make('amount_money')->placeholder('No amount'),
                TextColumn::make('job.title')->label('Job')->placeholder('No job linked')->wrap(),
                TextColumn::make('declined_at')->dateTime()->placeholder(''),
                TextColumn::make('notes')->wrap()->limit(120),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
