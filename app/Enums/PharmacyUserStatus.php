<?php

namespace App\Enums;

enum PharmacyUserStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';
    case Pending   = 'pending';
    case Invited   = 'invited';
    case Removed   = 'removed';
}
