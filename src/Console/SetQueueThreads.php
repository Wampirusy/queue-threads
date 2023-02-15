<?php

namespace ASW\QueueThreads\Console;

use ASW\QueueThreads\Services\ScalingManager;
use Illuminate\Console\Command;

class SetQueueThreads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set-queue-threads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate optimal count of threads for queues per client, and set it into supervisor.';

    public function handle(ScalingManager $scalingManager): void
    {
        $command = __DIR__.'/../../console/functions.sh';
        $threads = $scalingManager->calcThreadsPerWorkers();

        foreach ($threads as $workerFilename => $count) {
            $this->line("Set $workerFilename to $count threads...");
            echo shell_exec(
                "/bin/bash -c 'source $command && setQueueThreads $count $workerFilename' "
            );
        }

        $this->info('supervisorctl will update changed groups...');
        echo shell_exec('/usr/bin/supervisorctl update');
        $this->info('done!');
    }
}
