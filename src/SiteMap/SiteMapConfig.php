<?php

namespace Lkt\Http\SiteMap;

use Lkt\Http\Enums\SiteMapChangeFrequency;
use Lkt\Http\Networking\Networking;

class SiteMapConfig
{
    protected string $location = '';
    protected SiteMapChangeFrequency|null $changeFrequency = null;
    protected float|null $priority = null;
    protected $dynamicHandler = null;

    /** @deprecated  */
    const CHANGE_FREQUENCY_NEVER = 'never';
    /** @deprecated  */
    const CHANGE_FREQUENCY_YEARLY = 'yearly';
    /** @deprecated  */
    const CHANGE_FREQUENCY_MONTHLY = 'monthly';
    /** @deprecated  */
    const CHANGE_FREQUENCY_WEEKLY = 'weekly';
    /** @deprecated  */
    const CHANGE_FREQUENCY_DAILY = 'daily';
    /** @deprecated  */
    const CHANGE_FREQUENCY_HOURLY = 'hourly';
    /** @deprecated  */
    const CHANGE_FREQUENCY_ALWAYS = 'always';

    public function __construct(string $location, SiteMapChangeFrequency $changeFrequency = null, float $priority = null, ?callable $dynamicHandler = null)
    {
        if ($priority < 0) $priority = 0.0;
        if ($priority > 1) $priority = 1.0;

        $this->location = $location;
        $this->priority = $priority;
        $this->changeFrequency = $changeFrequency;
        $this->dynamicHandler = $dynamicHandler;
    }

    public function getLocation(): string
    {
        $base = Networking::getPublicUrl();
        $path = $this->location;
        if (str_ends_with($base, '/') && str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }
        return "{$base}{$path}";
    }

    public function __toString(): string
    {
        $r = ["<loc>{$this->getLocation()}</loc>"];
        if ($this->priority !== null) $r[] = "<priority>{$this->priority}</priority>";
        if ($this->changeFrequency !== null) $r[] = "<changefreq>{$this->changeFrequency->value}</changefreq>";

        $response = implode('', $r);
        return "<url>{$response}</url>";
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}

