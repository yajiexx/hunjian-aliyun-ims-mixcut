<?php

namespace Hunjian\AliyunImsMixcut\Repository;

use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Storage\CampaignRunReportRowMapper;
use PDO;

/**
 * Class PdoMySqlCampaignRunReportRepository
 *
 * MySQL/PDO repository example for campaign reports.
 */
class PdoMySqlCampaignRunReportRepository implements CampaignRunReportRepositoryInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var CampaignRunReportRowMapper
     */
    protected $mapper;

    /**
     * Create repository.
     *
     * @param PDO                               $pdo
     * @param string                            $table
     * @param CampaignRunReportRowMapper|null   $mapper
     */
    public function __construct(PDO $pdo, $table = 'ims_campaign_reports', CampaignRunReportRowMapper $mapper = null)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->mapper = $mapper ?: new CampaignRunReportRowMapper();
    }

    /**
     * Save one report.
     *
     * @param CampaignRunReport $report
     *
     * @return void
     */
    public function save(CampaignRunReport $report)
    {
        $row = $this->mapper->map($report);
        $sql = "INSERT INTO `{$this->table}` (`campaign_name`,`theme_name`,`total_jobs`,`finished_jobs`,`failed_jobs`,`pending_jobs`,`started_at`,`finished_at`,`metadata_json`,`summary_json`,`report_json`)
VALUES (:campaign_name,:theme_name,:total_jobs,:finished_jobs,:failed_jobs,:pending_jobs,:started_at,:finished_at,:metadata_json,:summary_json,:report_json)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($row);
    }

    /**
     * Save many reports.
     *
     * @param array $reports
     *
     * @return void
     */
    public function saveMany(array $reports)
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($reports as $report) {
                $this->save($report);
            }
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
