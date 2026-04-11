<?php

namespace Hunjian\AliyunImsMixcut\Result;

use Hunjian\AliyunImsMixcut\Model\BaseStructure;

/**
 * Class CampaignRunReport
 *
 * Persistable report for one whole campaign run.
 */
class CampaignRunReport extends BaseStructure
{
    /**
     * @var string
     */
    protected $campaignName;

    /**
     * @var string|null
     */
    protected $themeName;

    /**
     * @var array
     */
    protected $metadata = array();

    /**
     * @var array
     */
    protected $records = array();

    /**
     * @var string|null
     */
    protected $startedAt;

    /**
     * @var string|null
     */
    protected $finishedAt;

    /**
     * Start a new report.
     *
     * @param string      $campaignName
     * @param string|null $themeName
     * @param array       $metadata
     *
     * @return self
     */
    public static function start($campaignName, $themeName = null, array $metadata = array())
    {
        $report = new self();
        $report->campaignName = $campaignName;
        $report->themeName = $themeName;
        $report->metadata = $metadata;
        $report->startedAt = date('c');

        return $report;
    }

    /**
     * Restore report from persisted array.
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data)
    {
        $report = self::start(
            isset($data['campaignName']) ? $data['campaignName'] : null,
            isset($data['themeName']) ? $data['themeName'] : null,
            isset($data['metadata']) ? $data['metadata'] : array()
        );

        $report->startedAt = isset($data['startedAt']) ? $data['startedAt'] : null;
        $report->finishedAt = isset($data['finishedAt']) ? $data['finishedAt'] : null;

        if (!empty($data['records']) && is_array($data['records'])) {
            foreach ($data['records'] as $record) {
                $report->addRecord(JobRecord::fromArray($record));
            }
        }

        return $report;
    }

    /**
     * Add one job record.
     *
     * @param JobRecord $record
     *
     * @return $this
     */
    public function addRecord(JobRecord $record)
    {
        $this->records[] = $record;

        return $this;
    }

    /**
     * Get records.
     *
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * Get campaign name.
     *
     * @return string
     */
    public function getCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * Get theme name.
     *
     * @return string|null
     */
    public function getThemeName()
    {
        return $this->themeName;
    }

    /**
     * Mark report as finished.
     *
     * @return $this
     */
    public function markFinished()
    {
        $this->finishedAt = date('c');

        return $this;
    }

    /**
     * Build summary counters.
     *
     * @return array
     */
    public function getSummary()
    {
        $summary = array(
            'total' => count($this->records),
            'finished' => 0,
            'failed' => 0,
            'pending' => 0,
        );

        foreach ($this->records as $record) {
            if ($record->isFinished()) {
                $summary['finished']++;
            } elseif ($record->isFailed()) {
                $summary['failed']++;
            } else {
                $summary['pending']++;
            }
        }

        return $summary;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'campaignName' => $this->campaignName,
            'themeName' => $this->themeName,
            'metadata' => $this->metadata,
            'summary' => $this->getSummary(),
            'records' => $this->records,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
        ));
    }
}
