<?php

namespace Experteam\ApiLaravelEBilling\Utils;

use Illuminate\Support\Facades\Redis;

class UrlConfig
{
    private bool $changeUrl = false;

    public function getActiveUrl(): mixed
    {
        $index = config('experteam-billing.redis.active_index');
        $urls = config('experteam-billing.urls');
        return $urls[$index];
    }

    public function errorRegister(): void
    {
        $errorCount = Redis::incr(config('experteam-billing.redis.errors_count'));
        $urls = config('experteam-billing.urls');

        if ($errorCount >= config('experteam-billing.max_fails')) {
            $index = (int)Redis::get(config('experteam-billing.redis.active_index'));

            if ($index >= count($urls))
                $index = 0;

            Redis::set(config('experteam-billing.redis.active_index'), $index);
            $this->changeUrl = true;
        }
    }

    public function errorCount(): int
    {
        return (int)Redis::get(config('experteam-billing.redis.errors_count'));
    }

    public function urlChanged(): int
    {
        return $this->changeUrl;
    }
}