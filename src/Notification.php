<?php

namespace Lkt\Http;

use Lkt\Http\Enums\NotificationCategory;
use function Lkt\Tools\Parse\clearInput;

class Notification
{
    public NotificationCategory $category = NotificationCategory::Toast;

    public string $text = '';
    public string $details = '';

    public string $icon = '';
//    public string $positionX = '';

    public function __construct(NotificationCategory $category, array $payload)
    {
        $this->category = $category;
        if ($payload['text']) $this->text = clearInput($payload['text']);
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

    public function toArray(): array
    {
        $payload = [];
        if ($this->text !== '') $payload['text'] = $this->text;
        if ($this->details !== '') $payload['details'] = $this->details;
        if ($this->icon !== '') $payload['icon'] = $this->icon;
        return [
            'category' => $this->category->value,
            'payload' => $payload,
        ];
    }
}