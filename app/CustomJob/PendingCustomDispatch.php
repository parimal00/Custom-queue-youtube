<?php

namespace App\CustomJob;

use Illuminate\Support\Facades\DB;

class PendingCustomDispatch
{

    public function __construct(
        protected $job,
        protected string $queue = 'default',
        protected $delay = 0,
    ) {}

    public function onQueue(string $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    public function delay(int $seconds)
    {
        $this->delay = $seconds;
        return $this;
    }


    public function __destruct()
    {
        DB::table('custom_jobs')->insert([
            'queue' => $this->queue,
            'payload' => serialize($this->job),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() + $this->delay,
            'created_at' => time(),
        ]);
    }
}
