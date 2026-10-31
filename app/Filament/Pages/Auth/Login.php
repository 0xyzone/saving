<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public bool $isNumberVerified = false;

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('primary_contact_number')
            ->label('Primary Contact Number')
            ->tel()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->disabled(fn (): bool => $this->isNumberVerified)
            ->dehydrated()
            ->suffixAction(
                fn (): ?Action => $this->isNumberVerified
                    ? Action::make('changeNumber')
                        ->icon(Heroicon::PencilSquare)
                        ->tooltip('Change contact number')
                        ->action(function (): void {
                            $this->isNumberVerified = false;
                            $this->data['password'] = null;
                        })
                    : null
            );
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->visible(fn (): bool => $this->isNumberVerified)
            ->autofocus(fn (): bool => $this->isNumberVerified);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->visible(fn (): bool => $this->isNumberVerified);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        if (! $this->isNumberVerified) {
            return [
                $this->getCheckNumberFormAction(),
            ];
        }

        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getCheckNumberFormAction(): Action
    {
        return Action::make('checkNumber')
            ->label('Continue')
            ->action('verifyContactNumber');
    }

    protected function getUserModelClass(): string
    {
        /** @var EloquentUserProvider $provider */
        $provider = Filament::auth()->getProvider();

        return $provider->getModel();
    }

    public function verifyContactNumber(): void
    {
        $this->resetErrorBag();

        $contactNumber = trim((string) ($this->data['primary_contact_number'] ?? ''));

        if (blank($contactNumber)) {
            throw ValidationException::withMessages([
                'data.primary_contact_number' => 'Please enter your primary contact number.',
            ]);
        }

        $userModel = $this->getUserModelClass();
        $userExists = $userModel::query()
            ->where('primary_contact_number', $contactNumber)
            ->exists();

        if (! $userExists) {
            Notification::make()
                ->danger()
                ->title('Account not found')
                ->body('No account was found with the provided primary contact number.')
                ->send();

            throw ValidationException::withMessages([
                'data.primary_contact_number' => 'No account exists with this primary contact number.',
            ]);
        }

        $this->isNumberVerified = true;
    }

    public function authenticate(): ?LoginResponse
    {
        if (! $this->isNumberVerified) {
            $this->verifyContactNumber();

            return null;
        }

        return parent::authenticate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'primary_contact_number' => $data['primary_contact_number'] ?? ($this->data['primary_contact_number'] ?? ''),
            'password' => $data['password'] ?? ($this->data['password'] ?? ''),
        ];
    }

    protected function throwFailureValidationException(): never
    {
        Notification::make()
            ->danger()
            ->title('Authentication failed')
            ->body('The password you entered is incorrect.')
            ->send();

        throw ValidationException::withMessages([
            'data.password' => 'The password provided is incorrect.',
        ]);
    }
}
