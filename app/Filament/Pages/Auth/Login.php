<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

class Login extends BaseLogin
{
    /**
     * @return Component
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('primary_contact_number')
            ->label('Primary Contact Number')
            ->tel()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'primary_contact_number' => $data['primary_contact_number'],
            'password' => $data['password'],
        ];
    }
}
