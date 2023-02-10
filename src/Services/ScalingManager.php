<?php

namespace ASW\QueueThreads\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

class ScalingManager
{
    private const QUEUE_PREFIX = 'queues:';

    private Repository $config;
    private Connection $connection;

    public function __construct(
        Repository $config,
        RedisFactory $redisFactory,
    ) {
        $this->config = $config;
        $this->connection = $redisFactory->connection('queue');
    }

    /**
     * Returns count of jobs that are available right now.
     * Delayed and reserved jobs are excluded because there is no reason to create workers for them.
     * @param string $priorityFilter
     * @return int
     */
    public function getJobsAvailableNow(string $priorityFilter = ''): int
    {
        $totalCount = 0;
        foreach ($this->getQueues() as $queue) {
            //filtering queue names by priority
            if (str_ends_with($queue, $priorityFilter)) {
                $totalCount += $this->getQueueSize($queue);
            }
        }

        return $totalCount;
    }

    /**
     * Calculate how many threads to give for queues of each user, depending on queue sizes.
     * It's for supervisor threads within one container.
     */
    public function calcThreadsPerClient(): array
    {
        $jobsCountByClient = $this->getJobsCountByClient();
        $totalJobsCount = array_sum($jobsCountByClient);

        $threadsPerClient = [];
        $threadsAvailable = $this->config->get('queue.scaling.worker.threads_per_container');
        $threadsLeft = $threadsAvailable;

        /**
         *  1. Distribute 50% of threads evenly between all clients whose queues are not empty.
         *  In such a way we avoid situation when one client takes all threads.
         */
        $portion = (int)(($threadsAvailable / 2) / count($jobsCountByClient));
        foreach ($jobsCountByClient as $clientName => $jobsCount) {
            if ($jobsCount > 0) {
                $threadsPerClient[$clientName] = $portion;
                $threadsLeft -= $portion;
            } else {
                $threadsPerClient[$clientName] = 0;
            }
        }

        /**
         * 2. Distribute threads that left accordingly to queue sizes.
         * The more jobs client has - the more threads client will receive.
         * If there are no jobs - allocate threads between all clients evenly, so workers will start quickly.
         */
        foreach ($jobsCountByClient as $clientName => $jobsCount) {
            $share = ($totalJobsCount) ? ($jobsCount / $totalJobsCount) : (1 / count($jobsCountByClient));
            $threads = (int)($threadsLeft * $share);
            $threadsPerClient[$clientName] += $threads;
        }

        return $threadsPerClient;
    }

    /**
     * Estimate how many worker containers we need to process all available now jobs in time.
     * If it's not possible - will just give maximum allowed number.
     */
    public function calcWorkerContainersCount(int $jobsPriorityHigh, int $jobsPriorityNormal): int
    {
        $config = $this->config->get('queue.scaling.worker');

        // How much jobs can one container do per second
        $containerJobsPerSecond = (1 / $config['median_job_time']) * $config['threads_per_container'];

        //Idea is that workers needed to do all jobs in a queue in a desired limit of time
        $containersForHigh = ($jobsPriorityHigh / $containerJobsPerSecond) / $config['target_time_queue_high'];
        $containersForNormal = ($jobsPriorityNormal / $containerJobsPerSecond) / $config['target_time_queue_normal'];

        $containersTotal = ceil($containersForHigh + $containersForNormal);

        return $this->limitContainersByMinMax($this->limitContainersByRPM($containersTotal));
    }

    /**
     * Limits containers count by how many requests they will send per minute.
     * There is no sense to create more containers if they will ddos our proxy provider or wait without work.
     */
    private function limitContainersByRPM(int $desiredContainersCount): int
    {
        $config = $this->config->get('queue.scaling.worker');

        $threadJobsPerMinute = 60 / $config['median_job_time']; //Limit from contract is per minute, which is 60 sec.
        $containerJobsPerMinute = $threadJobsPerMinute * $config['threads_per_container'];
        $maxContainers = (int)ceil($config['max_request_per_minute'] / $containerJobsPerMinute);

        return min($maxContainers, $desiredContainersCount);
    }

    private function limitContainersByMinMax(int $desiredContainersCount): int
    {
        $limit = $this->config->get('queue.scaling.worker.max_containers_limit');

        if ($desiredContainersCount > $limit) {
            return $limit;
        }
        if ($desiredContainersCount < 1) {
            return 1; //Empty queue, need at least one worker anyway
        }

        return $desiredContainersCount;
    }

    private function getQueues(): array
    {
        return $this->config->get('queue.status_list.queues', []);
    }

    /**
     * Measures only jobs that available now, excludes delayed and reserved sub-queues!
     * @param string $queueName
     * @return int
     */
    private function getQueueSize(string $queueName): int
    {
        return $this->connection->llen(self::QUEUE_PREFIX.$queueName);
    }

    /**
     * Measures how many jobs each client has.
     * High/normal jobs are measured together.
     * Delayed and reserved jobs are excluded.
     * @return array
     */
    private function getJobsCountByClient(): array
    {
        $jobsCountByClient = [];
        foreach ($this->getQueues() as $queueName) {
            if (!str_contains($queueName, '_')) {
                continue; //queue doesn't belong to any user
            }
            $clientName = strtok($queueName, '_');
            $jobsCount = $this->getQueueSize($queueName);
            $jobsCountByClient[$clientName] = isset($jobsCountByClient[$clientName]) ? ($jobsCountByClient[$clientName] + $jobsCount) : $jobsCount;
        }

        return $jobsCountByClient;
    }
}
