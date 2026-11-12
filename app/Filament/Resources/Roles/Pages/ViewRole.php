<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use Agroezinger\FilamentShieldEnhanced\Traits\HasEnhancedRoleForm;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    use HasEnhancedRoleForm;

    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
