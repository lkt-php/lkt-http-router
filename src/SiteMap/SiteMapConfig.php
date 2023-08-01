<?php

namespace Lkt\Http\SiteMap;

use Lkt\Http\Networking\Networking;

class SiteMapConfig
{
    protected string $location = '';
    protected string|null $changeFrequency = null;
    protected float|null $priority = null;
    protected $dynamicHandler = null;

    const CHANGE_FREQUENCY_NEVER = 'never';
    const CHANGE_FREQUENCY_YEARLY = 'yearly';
    const CHANGE_FREQUENCY_MONTHLY = 'monthly';
    const CHANGE_FREQUENCY_WEEKLY = 'weekly';
    const CHANGE_FREQUENCY_DAILY = 'daily';
    const CHANGE_FREQUENCY_HOURLY = 'hourly';
    const CHANGE_FREQUENCY_ALWAYS = 'always';

    public function __construct(string $location, string $changeFrequency = null, float $priority = null, ?callable $dynamicHandler = null)
    {
        if ($priority < 0) $priority = 0.0;
        if ($priority > 1) $priority = 1.0;

        $this->location = $location;
        $this->priority = $priority;
        $this->changeFrequency = $changeFrequency;
        $this->dynamicHandler = $dynamicHandler;
    }

    public function __toString(): string
    {
        $location = Networking::getInstance()->getPublicUrl() . $this->location;
        $r = ["<loc>{$location}</loc>"];
        if ($this->priority !== null) $r[] = "<priority>{$this->priority}</priority>";
        if ($this->changeFrequency !== null) $r[] = "<changefreq>{$this->changeFrequency}</changefreq>";

        $response = implode('', $r);
        return "<url>{$response}</url>";
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}

