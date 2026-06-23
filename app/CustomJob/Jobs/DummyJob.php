<?php

namespace App\CustomJob\Jobs;

use App\CustomJob\InteractWithQueue;
use Exception;
use Illuminate\Support\Facades\Log;

class DummyJob
{
    use InteractWithQueue;

    public function __construct() {}

    public function handle()
    {
        Log::info("job run");
    }
}
