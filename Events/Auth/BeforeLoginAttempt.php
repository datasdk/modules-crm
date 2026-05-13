<?php

namespace Modules\Crm\Events\Auth;

class BeforeLoginAttempt
{
    public function __construct(public array $credentials = [])
    {
    }
}
