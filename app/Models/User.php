<?php

namespace App\Models;

// Re-export the module User so Laravel's auth config resolving still works
class User extends \App\Modules\User\Models\User
{
}
