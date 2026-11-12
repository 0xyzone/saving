<?php

namespace App\Filament\Resources\Users;

use Agroezinger\FilamentShieldEnhanced\Traits\HasResourceShield;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\Widgets\UserStatsWidget;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use HasResourceShield;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \UnitEnum|string|null $navigationGroup = 'Access Management';

    public static function getShieldResourcePermissions(): array
    {
        return [
            'ManageRoles' => 'Manage user roles & permissions',
            'ManagePaymentOptions' => 'Manage user payment options & QR codes',
            'Export' => 'Export users data',
        ];
    }

    public static function getShieldPermissionDescriptions(): array
    {
        return [
            'ManageRoles:User' => 'Allows granting and modifying role assignments for users.',
            'ManagePaymentOptions:User' => 'Allows adding, editing, and managing user bank/wallet options and QR codes.',
            'Export:User' => 'Allows exporting user profiles and details.',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            // 'create' => CreateUser::route('/create'),
            // 'view' => ViewUser::route('/{record}'),
            // 'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
