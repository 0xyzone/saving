<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
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
        CreateAction::configureUsing(fn(CreateAction $action) => $action->slideOver());
        EditAction::configureUsing(fn(EditAction $action) => $action->slideOver());
        ViewAction::configureUsing(fn(ViewAction $action) => $action->slideOver());

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
