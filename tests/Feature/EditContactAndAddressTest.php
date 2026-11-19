<?php

namespace Tests\Feature;

use App\Livewire\EditContactAndAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditContactAndAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_contact_details_button_uses_right_aligned_filament_action_container(): void
    {
        $user = User::factory()->create([
            'primary_contact_number' => '9800000010',
        ]);

        $this->actingAs($user);

        Livewire::test(EditContactAndAddress::class)
            ->assertSeeHtml('<div class="fi-ac fi-align-end">')
            ->assertDontSeeHtml('justify-content: flex-end')
            ->assertSee('Save Contact Details');
    }
}
