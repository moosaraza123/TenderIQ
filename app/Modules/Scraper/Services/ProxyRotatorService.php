<?php

namespace App\Modules\Scraper\Services;

class ProxyRotatorService
{
    private array $proxies = [];
    private int   $index   = 0;

    public function __construct()
    {
        $list = config('scraper.proxy_list', env('PROXY_LIST', ''));

        if ($list) {
            $this->proxies = array_filter(array_map('trim', explode(',', $list)));
        }
    }

    public function hasProxies(): bool
    {
        return ! empty($this->proxies);
    }

    public function next(): ?string
    {
        if (empty($this->proxies)) {
            return null;
        }

        $proxy       = $this->proxies[$this->index % count($this->proxies)];
        $this->index = ($this->index + 1) % count($this->proxies);

        return $proxy;
    }
}
