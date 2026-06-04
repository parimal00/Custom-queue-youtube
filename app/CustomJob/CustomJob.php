<?php

namespace App\CustomJob;

use Illuminate\Support\Facades\DB;

class CustomJob
{

    public static function push($job, $queue = 'default')
    {
        DB::table('custom_jobs')->insert([
            'queue' => $queue,
            'payload' => serialize($job),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);
    }
}