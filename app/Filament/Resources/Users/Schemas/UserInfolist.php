<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->circular()
                            ->disk('public'),

                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight('bold'),

                        TextEntry::make('primary_contact_number')
                            ->label('Primary Contact (Login Number)')
                            ->copyable(),

                        TextEntry::make('secondary_contact_number')
                            ->label('Secondary Contact')
                            ->placeholder('-'),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->placeholder('-'),

                        TextEntry::make('home_address')
                            ->label('Home Address')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Registered Date')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Payment Options')
                    ->schema([
                        RepeatableEntry::make('paymentOptions')
                            ->label('')
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                                TextEntry::make('provider_name')
                                    ->label('Provider / Bank')
                                    ->placeholder('-'),

                                TextEntry::make('account_number')
                                    ->label('Account / Wallet Number')
                                    ->copyable(),

                                ImageEntry::make('qr_code_image')
                                    ->label('QR Code')
                                    ->disk('public'),

                                IconEntry::make('is_preferred')
                                    ->label('Preferred')
                                    ->boolean(),

                                IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
