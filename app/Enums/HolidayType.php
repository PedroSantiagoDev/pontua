<?php

namespace App\Enums;

enum HolidayType: string
{
    case Holiday = 'holiday';
    case Optional = 'optional';
    case Partial = 'partial';
}
