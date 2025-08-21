<?php

namespace Experteam\ApiLaravelEBilling\Utils;

use Illuminate\Support\Facades\Redis;

class UrlConfig
{
    private bool $changeUrl = false;

    public function getActiveUrl(): mixed
    {
        $index = (int)(Redis::get(config('experteam-billing.redis.active_index')) ?? 0);
        $urls = config('experteam-billing.urls');
        return $urls[$index];
    }

    public function errorRegister(): void
    {
        $errorCount = Redis::incr(config('experteam-billing.redis.errors_count'));
        $urls = config('experteam-billing.urls');

        if ($errorCount >= config('experteam-billing.max_fails')) {
            $index = (int)Redis::get(config('experteam-billing.redis.active_index')) + 1;

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

    public function urlChanged(): bool
    {
        return $this->changeUrl;
    }

    public function setUrlChanged(bool $changeUrl): void
    {
        $this->changeUrl = $changeUrl;
    }

    public function setErrorsCount(int $number)
    {
        return Redis::set(config('experteam-billing.redis.errors_count'), $number);
    }
}