<?php

namespace Hunjian\AliyunImsMixcut\Storage;

use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Support\Json;

/**
 * Class CampaignRunReportRowMapper
 *
 * Maps one campaign report into a flat database row.
 */
class CampaignRunReportRowMapper
{
    /**
     * Map report into one row.
     *
     * @param CampaignRunReport $report
     *
     * @return array
     */
    public function map(CampaignRunReport $report)
    {
        $data = $report->toArray();
        $summary = isset($data['summary']) ? $data['summary'] : array();

        return array(
            'campaign_name' => isset($data['campaignName']) ? $data['campaignName'] : null,
            'theme_name' => isset($data['themeName']) ? $data['themeName'] : null,
            'total_jobs' => isset($summary['total']) ? $summary['total'] : 0,
            'finished_jobs' => isset($summary['finished']) ? $summary['finished'] : 0,
            'failed_jobs' => isset($summary['failed']) ? $summary['failed'] : 0,
            'pending_jobs' => isset($summary['pending']) ? $summary['pending'] : 0,
            'started_at' => isset($data['startedAt']) ? $data['startedAt'] : null,
            'finished_at' => isset($data['finishedAt']) ? $data['finishedAt'] : null,
            'metadata_json' => Json::encode(isset($data['metadata']) ? $data['metadata'] : array()),
            'summary_json' => Json::encode($summary),
            'report_json' => Json::encode($data),
        );
    }
}
