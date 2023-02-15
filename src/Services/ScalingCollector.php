<?php

namespace ASW\QueueThreads\Services;

use AirSlate\PrometheusExporter\Contracts\CollectorInterface;
use AirSlate\PrometheusExporter\Services\Registry\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Gauge;

/**
 * Gives desired info for custom autoscaling
 */
class ScalingCollector implements CollectorInterface
{
    private array $defaultLabels;
    private ScalingManager $scalingManager;

    private Gauge $metric;

    public function __construct(array $defaultLabels, ScalingManager $scalingManager)
    {
        $this->defaultLabels = $defaultLabels;
        $this->scalingManager = $scalingManager;
    }

    public function useStorage(): bool
    {
        return false;
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function registerMetrics(CollectorRegistry $registry): void
    {
        $this->metric = $registry->getOrRegisterGauge(
            $this->useStorage(),
            'scaling',
            'Data for Autoscaling: jobs available now, and requested containers count',
            array_merge(array_keys($this->defaultLabels), ['role', 'metric'])
        );
    }

    public function collectOnExport(): void
    {
        $jobsPriorityHigh = $this->scalingManager->getJobsAvailableNow('RequestConfigModel::PRIORITY_HIGH');
        $this->metric->set(
            $jobsPriorityHigh,
            array_merge(array_values($this->defaultLabels), ['worker', 'jobs_high_priority'])
        );

        $jobsPriorityNormal = $this->scalingManager->getJobsAvailableNow('RequestConfigModel::PRIORITY_NORMAL');
        $this->metric->set(
            $jobsPriorityNormal,
            array_merge(array_values($this->defaultLabels), ['worker', 'jobs_normal_priority'])
        );

        $this->metric->set(
            $this->scalingManager->calcWorkerContainersCount($jobsPriorityHigh, $jobsPriorityNormal),
            array_merge(array_values($this->defaultLabels), ['worker', 'requested_containers_count'])
        );
    }
}
