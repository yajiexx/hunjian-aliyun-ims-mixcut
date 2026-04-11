<?php

namespace Hunjian\AliyunImsMixcut\Storage;

use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Support\Json;

/**
 * Class JsonCampaignRunReportExporter
 *
 * Exports full campaign reports as pretty JSON.
 */
class JsonCampaignRunReportExporter
{
    /**
     * Export report.
     *
     * @param CampaignRunReport $report
     *
     * @return string
     */
    public function export(CampaignRunReport $report)
    {
        return Json::encode($report->toArray(), true);
    }
}
