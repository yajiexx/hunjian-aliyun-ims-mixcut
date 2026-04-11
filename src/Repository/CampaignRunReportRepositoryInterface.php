<?php

namespace Hunjian\AliyunImsMixcut\Repository;

use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;

/**
 * Interface CampaignRunReportRepositoryInterface
 *
 * Persistence boundary for campaign reports.
 */
interface CampaignRunReportRepositoryInterface
{
    /**
     * Save one report.
     *
     * @param CampaignRunReport $report
     *
     * @return mixed
     */
    public function save(CampaignRunReport $report);

    /**
     * Save many reports.
     *
     * @param array $reports
     *
     * @return mixed
     */
    public function saveMany(array $reports);
}
