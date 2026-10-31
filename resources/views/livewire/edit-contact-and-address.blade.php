<form wire:submit="save" class="fi-sc-form">
    {{ $this->form }}

    <div class="fi-ac fi-align-end" style="margin-top: 1rem; display: flex; justify-content: flex-end;">
        <x-filament::button type="submit">
            Save Contact Details
        </x-filament::button>
    </div>
</form>

