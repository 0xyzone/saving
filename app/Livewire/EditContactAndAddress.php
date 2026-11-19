<?php

namespace App\Livewire;

use App\Models\UserPaymentOption;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
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
            'username' => $this->user->username,
            'primary_contact_number' => $this->user->primary_contact_number,
            'secondary_contact_number' => $this->user->secondary_contact_number,
            'home_address' => $this->user->home_address,
            'paymentOptions' => $this
                ->user
                ->paymentOptions()
                ->orderBy('id')
                ->get()
                ->map(fn(UserPaymentOption $option): array => [
                    'id' => $option->id,
                    'type' => $option->type,
                    'provider_name' => $option->provider_name,
                    'account_number' => $option->account_number,
                    'qr_code_image' => $option->qr_code_image,
                    'is_preferred' => (bool) $option->is_preferred,
                    'is_active' => (bool) $option->is_active,
                ])
                ->toArray(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account, Contact & Address Information')
                    ->aside()
                    ->description('Update your username, contact numbers, and residential address.')
                    ->schema([
                        TextEntry::make('username')
                            ->label('Username')
                            ->default(fn() => '@' . $this->user?->username)
                            ->visible(fn() => $this->user?->username)
                            ->extraAttributes([
                                'class' => 'cursor-not-allowed text-2xl text-amber-400',
                            ]),
                        TextInput::make('username')
                            ->label('Username')
                            ->placeholder('e.g. johndoe')
                            ->alphaDash()
                            ->default(fn() => $this->user?->username)
                            ->prefix('@')
                            ->nullable()
                            ->unique($this->user::class, 'username', ignorable: $this->user)
                            ->maxLength(255)
                            ->visible(fn() => !$this->user?->username),
                        TextInput::make('primary_contact_number')
                            ->label('Primary Contact Number')
                            ->tel()
                            ->nullable()
                            ->unique($this->user::class, 'primary_contact_number', ignorable: $this->user)
                            ->maxLength(50),
                        TextInput::make('secondary_contact_number')
                            ->label('Secondary Contact Number')
                            ->tel()
                            ->nullable()
                            ->maxLength(50),
                        Textarea::make('home_address')
                            ->label('Home Address')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'sm' => 3]),
                Section::make('Payment Methods')
                    ->aside()
                    ->description('Manage your bank accounts, digital wallets, and QR codes for receiving disbursements.')
                    ->schema([
                        Repeater::make('paymentOptions')
                            ->label('Recorded Payment Methods')
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
                                    ->placeholder('e.g. Nabil Bank, eSewa, Khalti')
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
                                        if (!$state) {
                                            return;
                                        }

                                        $items = $get('../../paymentOptions') ?? [];
                                        $currentPath = $component->getStatePath();
                                        foreach ($items as $key => $item) {
                                            if (!str_contains($currentPath, ".{$key}.")) {
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
                            ->defaultItems(0)
                            ->addActionLabel('Add Payment Method')
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => filled($state['provider_name'] ?? null)
                                ? $state['provider_name'] . ' - ' . ($state['account_number'] ?? '')
                                : ($state['account_number'] ?? 'New Payment Method')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->user->update([
            'username' => filled($data['username'] ?? null) ? trim((string) $data['username']) : null,
            'primary_contact_number' => filled($data['primary_contact_number'] ?? null) ? trim((string) $data['primary_contact_number']) : null,
            'secondary_contact_number' => filled($data['secondary_contact_number'] ?? null) ? trim((string) $data['secondary_contact_number']) : null,
            'home_address' => $data['home_address'] ?? null,
        ]);

        $submittedOptions = collect($data['paymentOptions'] ?? []);
        $keptIds = [];

        foreach ($submittedOptions as $optionData) {
            $payload = [
                'type' => $optionData['type'],
                'provider_name' => $optionData['provider_name'] ?? null,
                'account_number' => $optionData['account_number'],
                'qr_code_image' => $optionData['qr_code_image'] ?? null,
                'is_preferred' => (bool) ($optionData['is_preferred'] ?? false),
                'is_active' => (bool) ($optionData['is_active'] ?? true),
            ];

            if (!empty($optionData['id'])) {
                $existing = $this->user->paymentOptions()->find($optionData['id']);
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $this->user->paymentOptions()->create($payload);
            $keptIds[] = $created->id;
        }

        $this->user->paymentOptions()->whereNotIn('id', $keptIds)->delete();

        Notification::make()
            ->success()
            ->title('Profile, contact & payment details saved successfully.')
            ->send();
    }
}
