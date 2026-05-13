<?php

namespace Modules\Crm\Events\Auth;

use App\Models\User;

class UserLoginSuccess
{
    public function __construct(public User $user)
    {
    }
}
