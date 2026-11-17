<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\UserResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Details')
                    ->description('User personal identity and primary credentials')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('username')
                            ->label('Username (Used for Login)')
                            ->placeholder('e.g. johndoe')
                            ->alphaDash()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->default(function () {
                                $panelUserRole = Role::where('name', Utils::getPanelUserRoleName())
                                    ->where('guard_name', Utils::getFilamentAuthGuard())
                                    ->first();

                                return $panelUserRole ? [$panelUserRole->id] : [];
                            })
                            ->label('Roles')
                            ->visible(fn (): bool => UserResource::canShield('ManageRoles')),

                        TextInput::make('primary_contact_number')
                            ->label('Primary Contact Number (Used for Login)')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('secondary_contact_number')
                            ->label('Secondary Contact Number')
                            ->tel()
                            ->nullable()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Textarea::make('home_address')
                            ->label('Home Address')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('Payment Options')
                    ->description('Multiple bank accounts and digital wallets for this user')
                    ->visible(fn (): bool => UserResource::canShield('ManagePaymentOptions'))
                    ->schema([
                        Repeater::make('paymentOptions')
                            ->relationship('paymentOptions')
                            ->schema([
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'bank' => 'Bank Account',
                                        'wallet' => 'Digital Wallet',
                                    ])
                                    ->required()
                                    ->native(false),

                                TextInput::make('provider_name')
                                    ->label('Bank / Wallet Provider Name')
                                    ->placeholder('e.g. Chase, Esewa, Khalti, PayPal')
                                    ->nullable()
                                    ->maxLength(100),

                                TextInput::make('account_number')
                                    ->label('Account / Wallet Number')
                                    ->required()
                                    ->maxLength(100),

                                FileUpload::make('qr_code_image')
                                    ->label('QR Code')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('payment_qrs')
                                    ->imageEditor()
                                    ->nullable(),

                                Toggle::make('is_preferred')
                                    ->label('Preferred')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set, $component): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $items = $get('../../paymentOptions') ?? [];
                                        $currentPath = $component->getStatePath();
                                        foreach ($items as $key => $item) {
                                            if (! str_contains($currentPath, ".{$key}.")) {
                                                $items[$key]['is_preferred'] = false;
                                            }
                                        }
                                        $set('../../paymentOptions', $items);
                                    }),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(['default' => 1, 'sm' => 2])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['provider_name'] ?? $state['type'] ?? 'Payment Option').' - '.($state['account_number'] ?? ''))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
