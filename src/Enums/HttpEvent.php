<?php

namespace Lkt\Http\Enums;

enum HttpEvent: string
{
    case MethodNotAllowed = 'method-not-allowed';
    case BadRequest = 'bad-request';
    case NotEnoughPerms = 'not-enough-perms';
    case SuccessCreate = 'mk-ok';
    case SuccessUpdate = 'up-ok';
    case SuccessDrop = 'rm-ok';
}