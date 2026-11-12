<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SavingsScheme;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SavingsSchemePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SavingsScheme');
    }

    public function view(AuthUser $authUser, SavingsScheme $savingsScheme): bool
    {
        return $authUser->can('View:SavingsScheme');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SavingsScheme');
    }

    public function update(AuthUser $authUser, SavingsScheme $savingsScheme): bool
    {
        return $authUser->can('Update:SavingsScheme');
    }

    public function delete(AuthUser $authUser, SavingsScheme $savingsScheme): bool
    {
        return $authUser->can('Delete:SavingsScheme');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SavingsScheme');
    }

    public function restore(AuthUser $authUser, SavingsScheme $savingsScheme): bool
    {
        return $authUser->can('Restore:SavingsScheme');
    }

    public function forceDelete(AuthUser $authUser, SavingsScheme $savingsScheme): bool
    {
        return $authUser->can('ForceDelete:SavingsScheme');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SavingsScheme');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SavingsScheme');
    }

    public function replicate(AuthUser $authUser, SavingsScheme $savingsScheme): bool
    {
        return $authUser->can('Replicate:SavingsScheme');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SavingsScheme');
    }
}
