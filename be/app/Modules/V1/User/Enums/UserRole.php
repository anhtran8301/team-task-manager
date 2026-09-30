<?php

namespace App\Modules\V1\User\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
