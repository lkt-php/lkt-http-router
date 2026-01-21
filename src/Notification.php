<?php

namespace Lkt\Http;

use Lkt\Http\Enums\NotificationCategory;
use function Lkt\Tools\Parse\clearInput;

class Notification
{
    public NotificationCategory $category = NotificationCategory::Toast;

    public static string $defaultSuccessIcon = '';
    public static string $defaultFailIcon = '';
    public static string $defaultWarningIcon = '';

    public static string $defaultSuccessClass = '';
    public static string $defaultFailClass = '';
    public static string $defaultWarningClass = '';

    public string $text = '';
    public string $details = '';
    public string $class = '';

    public string $icon = '';
//    public string $positionX = '';

    public function __construct(NotificationCategory $category, array $payload)
    {
        $this->category = $category;
        if ($payload['text']) $this->text = clearInput($payload['text']);
        if ($payload['class']) $this->class = clearInput($payload['class']);
        if ($payload['details']) $this->details = clearInput($payload['details']);
        if ($payload['icon']) $this->icon = clearInput($payload['icon']);
//        if ($payload['positionX']) $this->details = clearInput($payload['positionX']);
    }

    public static function sendToast(array $payload): static
    {
        $instance = new static( NotificationCategory::Toast, $payload);
        Router::addPendingNotification($instance);
        return $instance;
    }

    public static function sendSuccessToast(array $payload): static
    {
        $p = [];
        if (static::$defaultSuccessClass) $p['class'] = static::$defaultSuccessClass;
        if (static::$defaultSuccessIcon) $p['icon'] = static::$defaultSuccessIcon;

        $instance = new static( NotificationCategory::Toast, [...$p, ...$payload]);
        Router::addPendingNotification($instance);
        return $instance;
    }

    public static function sendFailToast(array $payload): static
    {
        $p = [];
        if (static::$defaultFailClass) $p['class'] = static::$defaultFailClass;
        if (static::$defaultFailIcon) $p['icon'] = static::$defaultFailIcon;

        $instance = new static( NotificationCategory::Toast, [...$p, ...$payload]);
        Router::addPendingNotification($instance);
        return $instance;
    }

    public static function sendWarningToast(array $payload): static
    {
        $p = [];
        if (static::$defaultWarningClass) $p['class'] = static::$defaultWarningClass;
        if (static::$defaultWarningIcon) $p['icon'] = static::$defaultWarningIcon;

        $instance = new static( NotificationCategory::Toast, [...$p, ...$payload]);
        Router::addPendingNotification($instance);
        return $instance;
    }

    public function toArray(): array
    {
        $payload = [];
        if ($this->text !== '') $payload['text'] = $this->text;
        if ($this->details !== '') $payload['details'] = $this->details;
        if ($this->class !== '') $payload['class'] = $this->class;
        if ($this->icon !== '') $payload['icon'] = $this->icon;
        return [
            'category' => $this->category->value,
            'payload' => $payload,
        ];
    }
}