<?php

namespace Experteam\ApiLaravelEBilling\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class UrlConfigController extends Controller
{
    /**
     * Get all url configured
     */
    public function index(): Response|ResponseFactory
    {
        $urls = config('experteam-billing.urls');
        $index = (int)Redis::get(config('experteam-billing.redis.active_index'));

        if ($index >= count($urls)) {
            $index = 0;
            Redis::set(config('experteam-billing.redis.active_index'), $index);
        }

        $configs = [];
        foreach ($urls as $key => $url) {
            $configs[] = [
                'index' => $key + 1,
                'url' => $url,
                'is_active' => $index == $key,
            ];
        }

        return jsend_success(compact('configs'));
    }

    public function update($index): Response|ResponseFactory
    {
        $index = $index - 1;
        $urls = config('experteam-billing.urls');

        if ($index >= count($urls))
            jsend_fail(['message' => 'Index not defined']);

        Redis::set(config('experteam-billing.redis.active_index'), $index);
        return jsend_success();
    }
}