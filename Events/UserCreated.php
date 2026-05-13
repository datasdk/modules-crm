<?php

namespace Modules\Crm\Events;

use Modules\Crm\Entities\User;

class UserCreated
{
    public function __construct(public User $user)
    {
    }
}
