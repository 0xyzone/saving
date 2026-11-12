<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\UserResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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

                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
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

                        FileUpload::make('avatar_url')
                            ->label('Filament Avatar')
                            ->image()
                            ->directory('avatars')
                            ->avatar()
                            ->imageEditor()
                            ->nullable(),

                        Textarea::make('home_address')
                            ->label('Home Address')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

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
                                    ->directory('payment_qrs')
                                    ->imageEditor()
                                    ->nullable(),

                                Toggle::make('is_preferred')
                                    ->label('Preferred')
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['provider_name'] ?? $state['type'] ?? 'Payment Option').' - '.($state['account_number'] ?? ''))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
