<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand as BaseMakeUserCommand;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeFilamentUserCommand extends BaseMakeUserCommand
{
    protected $description = 'Create a new Filament user with primary contact number';

    protected $name = 'make:filament-user';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'filament:make-user',
        'filament:user',
    ];

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'name',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The name of the user',
            ),
            new InputOption(
                name: 'primary_contact_number',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'A valid and unique primary contact number',
            ),
            new InputOption(
                name: 'email',
                shortcut: null,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'A valid email address (optional)',
            ),
            new InputOption(
                name: 'password',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The password for the user (min. 8 characters)',
            ),
            new InputOption(
                name: 'panel',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The panel to create the user in',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserData(): array
    {
        $primaryContact = $this->options['primary_contact_number'] ?? text(
            label: 'Primary contact number',
            placeholder: 'e.g. 9800000000 or +1234567890',
            required: true,
            validate: fn (string $contact): ?string => match (true) {
                strlen(trim($contact)) < 5 => 'The primary contact number must be at least 5 digits.',
                static::getUserModel()::query()->where('primary_contact_number', $contact)->exists() => 'A user with this primary contact number already exists.',
                default => null,
            },
        );

        $data = [
            'name' => $this->options['name'] ?? text(
                label: 'Name',
                required: true,
            ),
            'primary_contact_number' => $primaryContact,
            'password' => Hash::make($this->options['password'] ?? password(
                label: 'Password',
                required: true,
            )),
        ];

        if (isset($this->options['email']) && filled($this->options['email'])) {
            $data['email'] = $this->options['email'];
        }

        return $data;
    }

    protected function sendSuccessMessage(Model&Authenticatable $user): void
    {
        $loginUrl = $this->panel->getLoginUrl();
        $identifier = $user->getAttribute('primary_contact_number') ?? $user->getAttribute('name') ?? 'You';

        $this->components->info("Success! {$identifier} may now log in at {$loginUrl}");
    }
}
