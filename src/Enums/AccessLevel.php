<?php

namespace Lkt\Http\Enums;

enum AccessLevel: int
{
    case Public = 1;
    case OnlyLoggedUsers = 2;
    case OnlyNotLoggedUsers = 3;
    case OnlyAdminUsers = 4;
}