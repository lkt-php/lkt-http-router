<?php

namespace Lkt\Http;

use Lkt\Http\Enums\HttpEvent;

class HttpEventHandler
{
    protected static array $hooks = [];

    private function __construct(
        readonly public HttpEvent $event,
        readonly public mixed     $callableHandler
    )
    {
        if (!is_callable($this->callableHandler)) throw new \Exception('Invalid handler: Must be a valid callable type');
    }

    public static function successCreate(callable $handler): static
    {
        return new static(HttpEvent::SuccessCreate, $handler);
    }

    public static function successUpdate(callable $handler): static
    {
        return new static(HttpEvent::SuccessUpdate, $handler);
    }

    public static function successDrop(callable $handler): static
    {
        return new static(HttpEvent::SuccessDrop, $handler);
    }

    public static function notEnoughPerms(callable $handler): static
    {
        return new static(HttpEvent::NotEnoughPerms, $handler);
    }

    public static function triggerEvent(HttpEvent $event, array $haystack, ...$args): void
    {
        $handlerArguments = is_array($args) ? $args : [];
        /** @var static $handler */
        foreach ($haystack as $handler) {
            if ($handler->event === $event) {
                call_user_func_array($handler->callableHandler, $handlerArguments);
            }
        }
    }
}