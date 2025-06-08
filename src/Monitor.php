<?php
namespace RMoore\WebMonitor;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Database\Events\QueryExecuted;

class Monitor
{
    private $queries = [];
    private $enabled = false;

    public function __construct()
    {
        $this->enabled = config('web-monitor.enabled');

        DB::listen(function (QueryExecuted $query) {
            if (!$this->enabled) {
                return;
            }

            $endTime = microtime(true);

            $this->queries[] = [
                'database' => $query->connection->getNameWithReadWriteType(),
                'driver' => $query->connection->getDriverTitle(),
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
            $path = 'graphql.' . $request->operationName;

        SendToServer::dispatch([
            'queries' => $this->queries,

            'request' => [
                'user_id' => auth()->id(),
                'server' => $request->server('HOSTNAME') ?? $_ENV['HOSTNAME'],
                'session' => session()->getId(),
                'method' => $request->method(),
                'url' => $request->root(),
                'uri' => $path,
                'content_type' => $this->getContentType($request),
                'request_length' => strlen($request->getContent()),
                'response_code' => $response->getStatusCode(),
                'response_length' => strlen($response->getContent()),
                'started_at_epoch' => $this->startTime($request),
                'finished_at_epoch' => microtime(true),
            ],
        ])->onQueue(config('web-monitor.queue'));
    }

    private function startTime($request)
    {
        if (defined('LARAVEL_START')) {
            return LARAVEL_START;
        }

        return $request->server('REQUEST_TIME_FLOAT');
    }

    private function getContentType($request) : ?string {
        if (method_exists($request, 'getContentType')) {
            return $request->getContentType();
        }

        if (method_exists($request, 'getContentTypeFormat')) {
            return $request->getContentTypeFormat();
        }

        return null;
    }
}
