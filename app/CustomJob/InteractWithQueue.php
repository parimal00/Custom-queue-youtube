<?php

namespace App\CustomJob;

use App\CustomJob\Jobs\DummyJob;


trait InteractWithQueue
{
    public static function dispatch(...$arguments)
    {
        return new PendingCustomDispatch(
            new static(...$arguments)
        );
    }
}
