<?php

namespace App\Filament\Resources\Companies\RelationManagers;

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

class ContactLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'contactLogs';

    protected static ?string $title = 'Contact history';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('job_id')
                ->label('Discussing job')
                ->relationship('job', 'title')
                ->searchable()
                ->preload(),
            Select::make('contact_type')
                ->options([
                    'email' => 'Email',
                    'linkedin' => 'LinkedIn',
                    'call' => 'Call',
                    'note' => 'Note',
                ])
                ->required(),
            DateTimePicker::make('contact_at')->required()->default(now()),
            TextInput::make('subject')->maxLength(255),
            Textarea::make('message')->required()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contact_at')->dateTime()->sortable(),
                TextColumn::make('contact_type')->badge(),
                TextColumn::make('subject')->placeholder('No subject')->searchable(),
                TextColumn::make('job.title')->label('Job')->placeholder('No job linked')->wrap(),
                TextColumn::make('message')->wrap()->limit(120),
            ])
            ->defaultSort('contact_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
