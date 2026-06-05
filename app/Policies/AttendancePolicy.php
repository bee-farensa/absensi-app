<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
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
        return $user->can('view_any_attendance');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return bool
     */
    public function view(User $user, Attendance $attendance): bool
    {
        // Super admin can view all
        // Admin PT can view attendance in their company
        // Users can only view their own attendance
        return $user->can('view_attendance') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $attendance->company_id) ||
            $user->id === $attendance->user_id
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
        return $user->can('create_attendance');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return bool
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Super admin can update all
        // Admin PT can update attendance in their company
        // Regular users cannot update attendance records
        return $user->can('update_attendance') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $attendance->company_id)
        );
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return bool
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        // Super admin can delete all
        // Admin PT can delete attendance in their company
        // Users cannot delete their own attendance
        return $user->can('delete_attendance') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $attendance->company_id)
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
        return $user->can('delete_any_attendance');
    }

    /**
     * Determine whether the user can permanently delete.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return bool
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        // Only super admin can force delete
        return $user->can('force_delete_attendance') && $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_attendance') && $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return bool
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        // Super admin can restore all
        // Admin PT can restore attendance in their company
        return $user->can('restore_attendance') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $attendance->company_id)
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
        return $user->can('restore_any_attendance');
    }

    /**
     * Determine whether the user can replicate.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return bool
     */
    public function replicate(User $user, Attendance $attendance): bool
    {
        // Super admin can replicate all
        // Admin PT can replicate attendance in their company
        return $user->can('replicate_attendance') && (
            $user->hasRole('super_admin') ||
            ($user->hasRole('admin_pt') && $user->company_id === $attendance->company_id)
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
        return $user->can('reorder_attendance');
    }

}
