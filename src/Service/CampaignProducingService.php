<?php

namespace Hunjian\AliyunImsMixcut\Service;

use Hunjian\AliyunImsMixcut\Builder\CampaignTaskListBuilder;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Result\JobRecord;

/**
 * Class CampaignProducingService
 *
 * Builds tasks from CampaignPlan, submits them and returns a persistable report.
 */
class CampaignProducingService
{
    /**
     * @var MediaProducingService
     */
    protected $mediaProducingService;

    /**
     * Create service.
     *
     * @param MediaProducingService $mediaProducingService
     */
    public function __construct(MediaProducingService $mediaProducingService)
    {
        $this->mediaProducingService = $mediaProducingService;
    }

    /**
     * Submit a campaign and return initial report.
     *
     * @param CampaignPlan $campaign
     *
     * @return CampaignRunReport
     */
    public function submitCampaign(CampaignPlan $campaign)
    {
        $tasks = (new CampaignTaskListBuilder())
            ->fromCampaign($campaign)
            ->build();

        $report = CampaignRunReport::start(
            $campaign->getName(),
            $campaign->getTheme() ? $campaign->getTheme()->getName() : null,
            $campaign->getMetadata()
        );

        foreach ($tasks as $task) {
            $built = $task->build();
            $jobResult = $this->mediaProducingService->submitTimeline(
                $built['timeline'],
                $built['outputMediaConfig'],
                $task->getOptions()
            );

            $report->addRecord(JobRecord::fromTaskAndJobResult($task, $jobResult));
        }

        return $report;
    }

    /**
     * Wait all non-terminal records in a report.
     *
     * @param CampaignRunReport $report
     * @param int               $intervalSeconds
     * @param int               $timeoutSeconds
     *
     * @return CampaignRunReport
     */
    public function waitUntilFinished(CampaignRunReport $report, $intervalSeconds = 5, $timeoutSeconds = 600)
    {
        foreach ($report->getRecords() as $record) {
            if ($record->isFinished() || $record->isFailed() || !$record->getJobId()) {
                continue;
            }

            $produceResult = $this->mediaProducingService->waitUntilFinished(
                $record->getJobId(),
                $intervalSeconds,
                $timeoutSeconds
            );

            $record->applyProduceResult($produceResult);
        }

        return $report->markFinished();
    }

    /**
     * Submit a campaign and optionally wait for terminal states.
     *
     * @param CampaignPlan $campaign
     * @param bool         $waitUntilFinished
     * @param int          $intervalSeconds
     * @param int          $timeoutSeconds
     *
     * @return CampaignRunReport
     */
    public function runCampaign(CampaignPlan $campaign, $waitUntilFinished = false, $intervalSeconds = 5, $timeoutSeconds = 600)
    {
        $report = $this->submitCampaign($campaign);

        if ($waitUntilFinished) {
            return $this->waitUntilFinished($report, $intervalSeconds, $timeoutSeconds);
        }

        return $report;
    }
}
