<?php

return [
    /**
     * Parameters of custom autoscaling of worker containers
     */
    'scaling' => [
        'worker' => [
            //10 seconds. It's a mediocre number from metrics. Depends mostly on response time of proxy provider.
            'median_job_time' => 6,

            //12 hours in seconds. We want all jobs to be done in this time.
            'target_time_queue_high' => 43200,

            //1 day in seconds. We want all jobs to be done in this time.
            'target_time_queue_normal' => 86400,

            /**
             * It's a restriction from contract with Zyte.
             * If we send more requests, they will slow down and may return 429.
             * Our experience shows that we actually can send much more without issues.
             * First number is the value from contract, second is coefficient from our experience
             */
            'max_request_per_minute' => (1712 * 2),

            //Maximum number of containers we might ask for
            'max_containers_limit' => env('WORKER_CONTAINERS_LIMIT', 10),

            //Maximum count of all processes in one container that runs 'artisan queue:work'. Limited by hardware.
            'threads_per_container' => env('THREADS_PER_CONTAINER', 80),
        ]
    ],
];
