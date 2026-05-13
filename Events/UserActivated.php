<?php

namespace Modules\Crm\Events;

use Modules\Crm\Entities\User;

class UserActivated
{
    public function __construct(public User $user)
    {
    }
}
