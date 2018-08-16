<?php

namespace RMoore\WebMonitor;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
}
