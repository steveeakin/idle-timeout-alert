<?php

namespace Vectorwyse\IdleTimeoutAlert\TimeoutCalculator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TimeoutCalculator
{
    /**
     * Calculates the number of seconds left before session expires.
     *
     * @param \Illuminate\Http\Request  $request
     * @return int
     */
    public static function getSecondsLeft(Request $request)
    {
        // We have to get session id from cookie.
        // If we try to grab it from session(), we end up touching the timestamp during page load
        $cookie = $request->cookie(config('session.cookie'));

        if (!$cookie) {
            throw new TimeoutCalculatorException('Not logged in');
        }

        try {
            $sessionId = Crypt::decryptString($cookie);
        } catch (\Throwable) {
            $cookie = explode('|', $cookie);
            $cookie = array_pop($cookie);

            $sessionId = Crypt::decryptString($cookie);
        }

        switch (config('session.driver')) {
            case 'database':    $checker = new DatabaseSessionChecker($sessionId);  break;
            case 'file':        $checker = new FileSessionChecker($sessionId);      break;
            default:            throw new TimeoutCalculatorException('Session driver not supported');
        }

        $secondsSince = time() - $checker->getLastModified();
        $secondsLeft = config('session.lifetime') * 60 - $secondsSince;
        return $secondsLeft;
    }
}
