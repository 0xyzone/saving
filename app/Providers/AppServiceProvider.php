<?php

namespace App\Providers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        // 1. Globally configure all Create/Edit/View modal actions to be slide-over
        CreateAction::configureUsing(fn (CreateAction $action) => $action->slideOver()->modalWidth('6xl'));
        EditAction::configureUsing(fn (EditAction $action) => $action->slideOver()->modalWidth('7xl'));
        ViewAction::configureUsing(fn (ViewAction $action) => $action->slideOver()->modalWidth('7xl'));

        // 2. Globally optimize Tables for clean mobile layouts
        Table::configureUsing(function (Table $table): void {
            $table
                ->striped()
                ->defaultPaginationPageOption(10);
        });

        // 3. Globally disable wheel spin changing number inputs & remove spin arrows
        TextInput::configureUsing(function (TextInput $component): void {
            $component->extraInputAttributes([
                'onwheel' => 'this.blur()',
            ], merge: true);
        });
    }
}
