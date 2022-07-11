<?php

namespace Vectorwyse\IdleTimeoutAlert\TimeoutCalculator;

use Illuminate\Support\Facades\DB;

class DatabaseSessionChecker extends SessionChecker
{
    public function getLastModified()
    {
        $sessionId = explode('|', $this->sessionId);
        $sessionId = array_pop($sessionId);
        $session = DB::table(config('session.table'))->where('id', $sessionId)->first();

        if (!$session) {
            throw new TimeoutCalculatorException('Session not found');
        }

        return $session->last_activity;
    }
}
