<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\RateLimiter;
use Closure;

/**
 * Global send throttle middleware.
 *
 * Enforces an approximate rate of 2 messages per 5 seconds across all workers.
 * Implementation note:
 * - We normalize the window to "per minute" for RateLimiter: 2/5s ~= 24/min.
 * - If the limiter says "no", we release the job back to the queue with a 5s delay.
 *
 */
class ThrottleSends
{
    /**
     * Handle the queued job with throttling.
     *
     * @param  mixed   $job   The queued job instance.
     * @param  Closure $next  The next middleware/callback.
     * @return mixed
     */
    public function handle($job, Closure $next)
    {
        $key = 'sms:send:global';
        $executed = RateLimiter::attempt(
            $key,
            $perMinute = 24,
            function () use ($next, $job) {
                return $next($job);
            },
            $decay = 60
        );

        if (! $executed) {
            $job->release(5);
        }
    }
}
