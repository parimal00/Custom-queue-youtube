<?php

namespace App\CustomJob\Jobs;

use Exception;
use Illuminate\Support\Facades\Log;

class DummyJob
{

    public function __construct() {}

    public function handle()
    {
        Log::info("job run");
    }
}
