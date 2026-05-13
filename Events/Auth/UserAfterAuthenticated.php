<?php

namespace Modules\Crm\Events\Auth;

class UserAfterAuthenticated
{
    public function __construct(
        public $request,
        public $response
    ) {
    }
}
