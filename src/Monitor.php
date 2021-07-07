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
            
            foreach ($query->bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $query->bindings[ $i ] = $binding->format('\'Y-m-d H:i:s\'');
                } else {
                    if (is_string($binding)) {
                        $query->bindings[ $i ] = "'$binding'";
                    }
                }
            }

            $boundSql = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
            $boundSql = vsprintf($boundSql, $query->bindings);


            $endTime = microtime(true);

            $this->queries[] = [
                'database' => $query->connectionName,
                'driver' => 'mysql',
                'query' => $boundSql,
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

        SendToServer::dispatch([
            'queries' => $this->queries,

            'request' => [
                'user_id' => auth()->id(),
                'server' => $request->server('HOSTNAME'),
                'method' => $request->method(),
                'url' => $request->root(),
                'uri' => $request->path(),
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
