<?php

namespace Modules\Crm\Events\Auth;

class UserBeforeAuthenticated
{
    public function __construct(
        public $request,
        public array $guards = []
    ) {
    }
}
