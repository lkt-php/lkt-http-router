<?php

namespace Lkt\Http\Enums;

enum SiteMapChangeFrequency: string
{
    case Never = 'never';
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Daily = 'daily';
    case Hourly = 'hourly';
    case Always = 'always';
}