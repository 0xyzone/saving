<?php

namespace App\Filament\Resources\SavingsSchemes\Pages;

use App\Filament\Resources\SavingsSchemes\SavingsSchemeResource;
use App\Models\SavingsScheme;
use App\Services\SavingsSchemeService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavingsSchemes extends ListRecords
{
    protected static string $resource = SavingsSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->after(function (SavingsScheme $record) {
                    app(SavingsSchemeService::class)->initializeScheme($record);
                }),
        ];
    }
}
