<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:custom-queue-work')]
#[Description('Command description')]
class CustomQueueWork extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Queue worker is runninng");

        while(true){
            $job = DB::table('custom_jobs')
            ->where('available_at', '<=', time())
            ->whereNull('reserved_at')
            ->first();

            if(!$job){
                sleep(1);
                continue;
            }

            DB::table('custom_jobs')
            ->where('id', $job->id)
            ->update([
                'reserved_at' => time(),
                'attempts' => $job->attempts + 1
            ]);

           $this->comment('Processing job'. $job->id);

           $payload = $job->payload;
           $unserializedJob = unserialize($payload);

           $unserializedJob->handle();

           DB::table('custom_jobs')
           ->where('id', $job->id)
           ->delete();

           $this->info('Job processed '. $job->id);
        }
    }
}
