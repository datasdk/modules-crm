<?php

namespace Modules\Crm\Events;

use Modules\Crm\Entities\User;

class UserUpdated
{
    public function __construct(public User $user)
    {
    }
}
