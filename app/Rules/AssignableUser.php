<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * A user who may be given work inside this company.
 *
 * Replaces the exists+closure pair that was copy-pasted into every request.
 * That pair had two holes: Rule::exists ignores soft deletes, so a removed
 * account still validated, and neither half looked at status, so an offboarded
 * employee could be assigned fresh work.
 *
 * Employees and staff are the same thing here. Both are internal users; whether
 * an HRM profile exists says nothing about whether they can own a lead or a
 * task.
 */
class AssignableUser implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $user = User::query()
            ->internal()
            ->where('company_id', Auth::user()?->company_id)
            ->find($value);

        if (! $user) {
            $fail('That team member is no longer available. Pick someone else.');

            return;
        }

        // Deliberately not a hard failure elsewhere: an inactive account keeps
        // its history and its old assignments. It just cannot be handed
        // anything new.
        if ($user->status !== 'active') {
            $fail("{$user->name} is no longer active and cannot be assigned new work.");
        }
    }
}