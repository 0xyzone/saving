<?php

namespace Database\Seeders;

use App\Models\SavingsScheme;
use App\Models\User;
use App\Services\SavingsSchemeService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $organizer = User::first();
        if ($organizer && SavingsScheme::count() === 0) {
            $scheme = SavingsScheme::create([
                'title' => '20-Month Gold Savings Committee (Demo)',
                'description' => 'Periodical committee scheme with 20 seats, Rs. 10,000 monthly installments, Rs. 500 fixed interest on past winners, and Rs. 1,000 Khaja Kharcha.',
                'organizer_id' => $organizer->id,
                'total_seats' => 20,
                'total_periods' => 20,
                'installment_amount' => 10000,
                'interest_amount' => 500,
                'khaja_kharcha' => 1000,
                'frequency' => 'monthly',
                'start_date' => now()->startOfMonth()->toDateString(),
                'status' => 'active',
                'current_period' => 1,
            ]);

            app(SavingsSchemeService::class)->initializeScheme($scheme);
        }
    }
}
