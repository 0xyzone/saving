<?php

namespace App\Filament\Pages;

use App\Models\Changelog as ChangelogModel;
use App\Services\ChangelogService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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

    protected static ?string $title = 'System Changelog';

    protected static ?string $slug = 'changelog';

    public function getHeading(): string
    {
        return 'System Changelog & Release Notes';
    }

    public function getSubheading(): ?string
    {
        return 'Track feature additions, system milestones, and architectural enhancements across the platform.';
    }

    public function getCurrentVersion(): string
    {
        return ChangelogService::getCurrentVersion();
    }

    /**
     * @return Collection<int, ChangelogModel>
     */
    public function getReleasesProperty(): Collection
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
                    Select::make('type')
                        ->label('Update Type (SemVer)')
                        ->options([
                            'major' => 'Major Release (Architectural / Breaking)',
                            'minor' => 'Minor Release (New Features & Enhancements)',
                            'macro' => 'Macro Update (Patch / Bug Fixes / Tweaks)',
                        ])
                        ->default('macro')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $next = ChangelogService::getNextVersion($state ?? 'macro');
                            $set('version', $next);
                        })
                        ->required(),

                    TextInput::make('version')
                        ->label('Version Tag')
                        ->default(fn () => ChangelogService::getNextVersion('macro'))
                        ->placeholder('e.g. 1.0.1')
                        ->required()
                        ->unique(ChangelogModel::class, 'version'),

                    TextInput::make('badge')
                        ->label('Category Badge')
                        ->placeholder('e.g. Feature Milestone, Security Patch, UI Enhancement')
                        ->default('Enhancement')
                        ->required(),

                    DatePicker::make('released_at')
                        ->label('Release Date')
                        ->default(now())
                        ->required(),

                    TextInput::make('title')
                        ->label('Release Headline / Summary')
                        ->placeholder('e.g. Modern Two-Step Login and Mobile Screen Optimizations')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Overview / Summary Paragraph')
                        ->rows(3)
                        ->columnSpanFull(),

                    Repeater::make('highlights')
                        ->label('Key Bullet Highlights')
                        ->simple(
                            TextInput::make('highlight')
                                ->placeholder('e.g. Removed spin buttons on all numeric input fields')
                                ->required()
                        )
                        ->defaultItems(1)
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    // Extract strings from simple repeater
                    if (isset($data['highlights']) && is_array($data['highlights'])) {
                        $data['highlights'] = array_values(array_filter(array_map(function ($item) {
                            return is_array($item) ? ($item['highlight'] ?? reset($item)) : $item;
                        }, $data['highlights'])));
                    }

                    ChangelogModel::create($data);

                    Notification::make()
                        ->success()
                        ->title('Release Logged Successfully')
                        ->body("Release v{$data['version']} is now live in the system changelog.")
                        ->send();
                }),
        ];
    }
}
