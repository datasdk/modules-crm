<?php

namespace Modules\Crm\Events\Auth;

use App\Models\User;

class UserLoggedIn
{
    public function __construct(
        public User $user,
        public ?string $token = null
    ) {
    }
}
