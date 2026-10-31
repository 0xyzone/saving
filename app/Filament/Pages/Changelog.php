<?php

namespace App\Filament\Pages;

use App\Models\Changelog as ChangelogModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class Changelog extends Page
{
    protected string $view = 'filament.pages.changelog';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Changelog & Release Notes';

    /**
     * @return Collection<int, ChangelogModel>
     */
    public function getChangelogsProperty(): Collection
    {
        return ChangelogModel::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRelease')
                ->label('New Release')
                ->icon(Heroicon::Plus)
                ->color('primary')
                ->slideOver()
                ->form([
                    TextInput::make('version')
                        ->label('Version Tag')
                        ->placeholder('e.g. v1.1.0')
                        ->required()
                        ->unique(ChangelogModel::class, 'version'),

                    Select::make('type')
                        ->label('Release Type')
                        ->options([
                            'major' => 'Major Release (Architectural / Breaking)',
                            'minor' => 'Minor Release (New Features)',
                            'macro' => 'Macro Update (Patch / Fixes / Tweaks)',
                        ])
                        ->default('macro')
                        ->required(),

                    DatePicker::make('released_at')
                        ->label('Release Date')
                        ->default(now())
                        ->required(),

                    TextInput::make('title')
                        ->label('Release Summary Title')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Release Notes / Highlights')
                        ->rows(5)
                        ->placeholder('Write bullet points or markdown details...'),
                ])
                ->action(function (array $data): void {
                    ChangelogModel::create($data);

                    Notification::make()
                        ->success()
                        ->title('Release added')
                        ->body("Release {$data['version']} recorded successfully.")
                        ->send();
                }),
        ];
    }
}
