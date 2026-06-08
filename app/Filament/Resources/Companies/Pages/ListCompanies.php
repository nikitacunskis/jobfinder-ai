<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\JobsucherSearch;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reindex')
                ->label('Reindex search')
                ->action(function (): void {
                    $count = app(JobsucherSearch::class)->reindexCompanies();
                    Notification::make()->title("Indexed {$count} companies")->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}
