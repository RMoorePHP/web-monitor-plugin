<?php
namespace RMoore\WebMonitor;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class Monitor
{
    private $queries = [];
    private $enabled = false;

    public function __construct()
    {
        $this->enabled = config('web-monitor.enabled');

        DB::listen(function ($query) {
            if (!$this->enabled) {
                return;
            }

            $endTime = microtime(true);

            $this->queries[] = [
                'database' => $query->connectionName,
                'driver' => 'mysql',
                'query' => $query->sql,
                'started_at_epoch' => $endTime - ($query->time / 1000),
                'finished_at_epoch' => $endTime,
            ];
        });
    }

    public function send($data = [])
    {
        if (!$this->enabled) {
            return;
        }

        $request = request();
        $response = $data['response'];
        
        $path = $request->path();
        if ($path == 'graphql' && $request->operationName) 
            $path .= '.' . $request->operationName;

        SendToServer::dispatch([
            'queries' => $this->queries,

            'request' => [
                'user_id' => auth()->id(),
                'server' => $request->server('HOSTNAME'),
                'method' => $request->method(),
                'url' => $request->root(),
                'uri' => $path,
                'content_type' => $request->getContentType(),
                'request_length' => strlen($request->getContent()),
                'response_code' => $response->getStatusCode(),
                'response_length' => strlen($response->getContent()),
                'started_at_epoch' => $request->server('REQUEST_TIME_FLOAT'),
                'finished_at_epoch' => microtime(true),
            ],
        ])->onQueue(config('web-monitor.queue'));
    }
}
