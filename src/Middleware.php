<?php

namespace RMoore\WebMonitor;

use Closure;

class Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $monitor = resolve(Monitor::class);

        $result = $next($request);

        try {
            $monitor->send(['response' => $result]);
        } catch (\Exception $e) {
            report($e);
        }

        return $result;
    }
}
