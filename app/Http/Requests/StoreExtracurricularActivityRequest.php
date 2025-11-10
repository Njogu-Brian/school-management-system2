<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtracurricularActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_type' => 'required|in:club,society,sports_team,competition,leadership_role,community_service,other',
            'activity_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'position_role' => 'nullable|string|max:255',
            'team_name' => 'nullable|string|max:255',
            'competition_name' => 'nullable|string|max:255',
            'competition_level' => 'nullable|string|max:255',
            'award_achievement' => 'nullable|string|max:255',
            'achievement_description' => 'nullable|string',
            'achievement_date' => 'nullable|date',
            'community_service_hours' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'supervisor_id' => 'nullable|exists:users,id',
            'votehead_id' => 'nullable|exists:voteheads,id',
            'fee_amount' => 'nullable|numeric|min:0',
            'auto_bill' => 'nullable|boolean',
            'billing_term' => 'nullable|integer|in:1,2,3',
            'billing_year' => 'nullable|integer|digits:4',
        ];
    }
}
