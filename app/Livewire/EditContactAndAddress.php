<?php

namespace App\Livewire;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasUser;
use Joaopaulolndev\FilamentEditProfile\Livewire\BaseProfileForm;

class EditContactAndAddress extends BaseProfileForm
{
    use HasUser;

    protected string $view = 'livewire.edit-contact-and-address';

    public ?array $data = [];

    protected static int $sort = 15;

    public function mount(): void
    {
        $this->user = $this->getUser();

        $this->form->fill([
            'primary_contact_number' => $this->user->primary_contact_number,
            'secondary_contact_number' => $this->user->secondary_contact_number,
            'home_address' => $this->user->home_address,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact & Address Information')
                    ->aside()
                    ->description('Update your primary and secondary contact numbers and residential address.')
                    ->schema([
                        TextInput::make('primary_contact_number')
                            ->label('Primary Contact Number')
                            ->tel()
                            ->required()
                            ->unique($this->user::class, 'primary_contact_number', ignorable: $this->user),

                        TextInput::make('secondary_contact_number')
                            ->label('Secondary Contact Number')
                            ->tel()
                            ->nullable(),

                        Textarea::make('home_address')
                            ->label('Home Address')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->user->update($data);

        Notification::make()
            ->success()
            ->title('Contact details saved successfully.')
            ->send();
    }
}
