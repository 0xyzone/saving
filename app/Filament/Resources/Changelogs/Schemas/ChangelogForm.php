<?php

namespace App\Filament\Resources\Changelogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChangelogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Release Details')
                    ->schema([
                        TextInput::make('version')
                            ->label('Version Number')
                            ->placeholder('e.g. v1.1.0')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Select::make('type')
                            ->label('Update Type')
                            ->options([
                                'major' => 'Major Update (Breaking / Large Feature)',
                                'minor' => 'Minor Update (Feature / Improvements)',
                                'macro' => 'Macro Update (Patch / Bug Fix)',
                            ])
                            ->required()
                            ->default('macro')
                            ->native(false),

                        TextInput::make('title')
                            ->label('Title / Summary')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        DatePicker::make('released_at')
                            ->label('Release Date')
                            ->default(now())
                            ->required(),

                        Textarea::make('description')
                            ->label('Details / Release Notes')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
