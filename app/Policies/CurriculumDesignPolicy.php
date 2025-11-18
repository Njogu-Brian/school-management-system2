<?php

namespace App\Policies;

use App\Models\CurriculumDesign;
use App\Models\User;

class CurriculumDesignPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'curriculum_designs.view',
            'curriculum_designs.view_own',
        ]) || $user->hasRole(['admin', 'subject_lead', 'senior_teacher', 'teacher']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CurriculumDesign $curriculumDesign): bool
    {
        // Admins and subject leads can view all
        if ($user->hasRole(['admin', 'subject_lead'])) {
            return true;
        }

        // Teachers can view if they have permission
        if ($user->hasPermissionTo('curriculum_designs.view')) {
            return true;
        }

        // Users can view their own uploads
        if ($curriculumDesign->uploaded_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'curriculum_designs.create',
        ]) || $user->hasRole(['admin', 'subject_lead', 'senior_teacher']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CurriculumDesign $curriculumDesign): bool
    {
        // Admins can update all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Subject leads can update their subject's designs
        if ($user->hasRole('subject_lead') && $curriculumDesign->subject_id) {
            // Check if user is assigned to this subject
            return $user->subjects()->where('subjects.id', $curriculumDesign->subject_id)->exists();
        }

        // Users can update their own uploads if not yet processed
        if ($curriculumDesign->uploaded_by === $user->id && $curriculumDesign->status === 'processing') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CurriculumDesign $curriculumDesign): bool
    {
        // Only admins can delete
        return $user->hasRole('admin') || $user->hasPermissionTo('curriculum_designs.delete');
    }

    /**
     * Determine whether the user can use the AI assistant.
     */
    public function useAssistant(User $user): bool
    {
        return $user->hasAnyPermission([
            'curriculum_assistant.use',
        ]) || $user->hasRole(['admin', 'subject_lead', 'senior_teacher', 'teacher']);
    }

    /**
     * Determine whether the user can reprocess a curriculum design.
     */
    public function reprocess(User $user, CurriculumDesign $curriculumDesign): bool
    {
        return $this->update($user, $curriculumDesign);
    }
}
