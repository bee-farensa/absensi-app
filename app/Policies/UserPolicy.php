<?php

namespace App\Policies;

use App\Models\User;

use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function view(User $user, User $targetUser): bool
    {
        // Super admin and admin_pt can view all users in their scope
        // Users can view their own profile
        return $user->can('view_user') && (
            $user->id === $targetUser->id ||
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $targetUser->company_id)
        );
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function update(User $user, User $targetUser): bool
    {
        // Super admin can update all users
        // Admin PT can update users in their company
        // Users cannot update other users
        return $user->can('update_user') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $targetUser->company_id)
        );
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Super admin can delete all users except themselves
        // Admin PT can delete users in their company except themselves
        return $user->can('delete_user') && 
               $user->id !== $targetUser->id && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $targetUser->company_id)
        );
    }

    /**
     * Determine whether the user can bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_user');
    }

    /**
     * Determine whether the user can permanently delete.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function forceDelete(User $user, User $targetUser): bool
    {
        // Only super admin can force delete, and not themselves
        return $user->can('force_delete_user') && 
               $user->id !== $targetUser->id &&
               $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_user');
    }

    /**
     * Determine whether the user can restore.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function restore(User $user, User $targetUser): bool
    {
        // Super admin can restore all
        // Admin PT can restore users in their company
        return $user->can('restore_user') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $targetUser->company_id)
        );
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_user');
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $targetUser
     * @return bool
     */
    public function replicate(User $user, User $targetUser): bool
    {
        // Super admin can replicate all
        // Admin PT can replicate users in their company
        return $user->can('replicate_user') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $targetUser->company_id)
        );
    }

    /**
     * Determine whether the user can reorder.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_user');
    }
}
