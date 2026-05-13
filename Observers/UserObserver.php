<?php

namespace Modules\Crm\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    public function creating(User $user): void
    {
        if (empty($user->uid)) {
            $user->uid = Str::uuid()->toString();
        }
    }

    public function saving(User $user): void
    {
        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            if (!empty($user->$field) && is_string($user->$field)) {
                $user->$field = ucfirst(strtolower($user->$field));
            }
        }

        if (!empty($user->email) && is_string($user->email)) {
            $user->email = strtolower($user->email);
        }
    }
}
