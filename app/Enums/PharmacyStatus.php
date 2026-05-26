<?php

namespace App\Enums;

enum PharmacyStatus: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
    case Archived  = 'archived';
    case Pending   = 'pending';
}
