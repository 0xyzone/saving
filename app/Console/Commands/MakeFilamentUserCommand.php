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
    protected $description = 'Create a new Filament user with username';

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
                name: 'username',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'A valid and unique username',
            ),
            new InputOption(
                name: 'primary_contact_number',
                shortcut: null,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'A valid and unique primary contact number (optional)',
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
        $username = $this->options['username'] ?? text(
            label: 'Username',
            placeholder: 'e.g. johndoe',
            required: true,
            validate: fn (string $username): ?string => match (true) {
                strlen(trim($username)) < 3 => 'The username must be at least 3 characters.',
                static::getUserModel()::query()->where('username', trim($username))->exists() => 'A user with this username already exists.',
                default => null,
            },
        );

        $data = [
            'name' => $this->options['name'] ?? text(
                label: 'Name',
                required: true,
            ),
            'username' => trim($username),
            'password' => Hash::make($this->options['password'] ?? password(
                label: 'Password',
                required: true,
            )),
        ];

        if (isset($this->options['primary_contact_number']) && filled($this->options['primary_contact_number'])) {
            $data['primary_contact_number'] = trim($this->options['primary_contact_number']);
        }

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
