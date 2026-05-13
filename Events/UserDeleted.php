<?php

namespace Modules\Crm\Events;

use Modules\Crm\Entities\User;

class UserDeleted
{
    public function __construct(public User $user)
    {
    }
}
