<?php

namespace App\Filament\Resources\SavingsSchemes;

use Agroezinger\FilamentShieldEnhanced\Traits\HasResourceShield;
use App\Filament\Resources\SavingsSchemes\Pages\ListSavingsSchemes;
use App\Filament\Resources\SavingsSchemes\Pages\ManageSavingsScheme;
use App\Filament\Resources\SavingsSchemes\Schemas\SavingsSchemeForm;
use App\Filament\Resources\SavingsSchemes\Tables\SavingsSchemesTable;
use App\Models\SavingsScheme;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SavingsSchemeResource extends Resource
{
    use HasResourceShield;

    protected static ?string $model = SavingsScheme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'title';

    protected static UnitEnum|string|null $navigationGroup = 'Savings Management';

    protected static ?string $navigationLabel = 'Savings Schemes';

    protected static ?int $navigationSort = 1;

    public static function getShieldResourcePermissions(): array
    {
        return [
            'DeclareWinner' => 'Declare round winners and compute payouts',
            'ManageInstallments' => 'Record and verify installment collections',
            'ManageSeats' => 'Assign and manage member seats',
        ];
    }

    public static function getShieldPermissionDescriptions(): array
    {
        return [
            'DeclareWinner:SavingsScheme' => 'Allows selecting the period winner, auto-settling seat dues, and issuing net prize payouts.',
            'ManageInstallments:SavingsScheme' => 'Allows recording payments, verifying proofs, and marking member installments as paid.',
            'ManageSeats:SavingsScheme' => 'Allows manual allocation of available seats to members.',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return SavingsSchemeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavingsSchemesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavingsSchemes::route('/'),
            'manage' => ManageSavingsScheme::route('/{record}'),
        ];
    }
}
