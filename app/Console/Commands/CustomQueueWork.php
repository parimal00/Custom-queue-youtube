<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:custom-queue-work {queue=default : The queues to process in order of priority}')]
#[Description('Run custom queue worker with priority queue routing')]
class CustomQueueWork extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        while (true) {
            try {
                $job =  DB::transaction(function () {
                    $job = DB::table('custom_jobs')
                        ->where('available_at', '<=', time())
                        ->whereNull('reserved_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$job) {
                        return null;
                    }

                    DB::table('custom_jobs')
                        ->where('id', $job->id)
                        ->update([
                            'reserved_at' => time(),
                            'attempts' => $job->attempts + 1
                        ]);

                    return $job;
                });

                if (!$job) {
                    sleep(1);
                    continue;
                }

                $this->comment('Processing job' . $job->id);

                $payload = $job->payload;
                $unserializedJob = unserialize($payload);

                $unserializedJob->handle();

                DB::table('custom_jobs')
                    ->where('id', $job->id)
                    ->delete();

                $this->info('Job processed ' . $job->id);
            } catch (\Throwable $e) {
                $this->error("Job failed " . ($job->id ?? ''));

                if ($job) {
                    if ($job->attempts >= 3) {
                        DB::table("failed_custom_jobs")
                            ->insert([
                                'connection' => 'database',
                                'queue' => $job->queue,
                                'payload' => $job->payload,
                                'exception' => (string) $e,
                                'failed_at' => now()
                            ]);

                        DB::table("custom_jobs")
                            ->where('id', $job->id)
                            ->delete();

                        $this->info("Job moved to failed queue");
                    } else {
                        DB::table("custom_jobs")
                            ->where('id', $job->id)
                            ->update([
                                'reserved_at' => null,
                                'available_at' => time() + 3,
                            ]);

                        $this->info("Job released back to queue");
                    }
                }
            }
        }
    }
}
