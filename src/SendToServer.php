<?php

namespace RMoore\WebMonitor;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;

use Illuminate\Queue\Middleware\ThrottlesExceptions;

class SendToServer implements ShouldQueue
{
    protected $data;

    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new Client(['base_uri' => config('web-monitor.base_url')]))->post('/api/requests', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'auth' => [
                config('web-monitor.client_id'),
                config('web-monitor.client_secret'),
            ],
            'json' => $this->data,
        ]);
    }

    public function middleware(): array
    {
        $rateLimitClass = RateLimited::class;

        if (config('queue.default') === 'redis') {
            $rateLimitClass = RateLimitedWithRedis::class;
        }

        return [
            (new $rateLimitClass('web-monitor:send-to-server')), //->releaseAfter(60),
            new ThrottlesExceptions(10, 5),
        ];
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(120);
    }
}
