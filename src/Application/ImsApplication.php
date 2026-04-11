<?php

namespace Hunjian\AliyunImsMixcut\Application;

use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\Timeline;
use Hunjian\AliyunImsMixcut\Repository\CampaignRunReportRepositoryInterface;
use Hunjian\AliyunImsMixcut\Repository\JobRecordRepositoryInterface;
use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Result\JobRecord;
use Hunjian\AliyunImsMixcut\Service\BatchProducingService;
use Hunjian\AliyunImsMixcut\Service\CampaignProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Storage\CsvJobRecordExporter;
use Hunjian\AliyunImsMixcut\Storage\JsonCampaignRunReportExporter;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;

/**
 * Class ImsApplication
 *
 * Lightweight application facade that wires runtime services, repositories and exporters.
 */
class ImsApplication
{
    /**
     * @var ImsConfig
     */
    protected $config;

    /**
     * @var ImsJobClient
     */
    protected $client;

    /**
     * @var MediaProducingService
     */
    protected $mediaProducingService;

    /**
     * @var BatchProducingService
     */
    protected $batchProducingService;

    /**
     * @var CampaignProducingService
     */
    protected $campaignProducingService;

    /**
     * @var JobRecordRepositoryInterface
     */
    protected $jobRecordRepository;

    /**
     * @var CampaignRunReportRepositoryInterface
     */
    protected $campaignRunReportRepository;

    /**
     * @var CsvJobRecordExporter
     */
    protected $jobRecordExporter;

    /**
     * @var JsonCampaignRunReportExporter
     */
    protected $campaignRunReportExporter;

    /**
     * @var StorageFileWriter
     */
    protected $storageFileWriter;

    /**
     * @var array
     */
    protected $paths = array();

    /**
     * Create application facade.
     *
     * @param ImsConfig                          $config
     * @param ImsJobClient                       $client
     * @param MediaProducingService              $mediaProducingService
     * @param BatchProducingService              $batchProducingService
     * @param CampaignProducingService           $campaignProducingService
     * @param JobRecordRepositoryInterface       $jobRecordRepository
     * @param CampaignRunReportRepositoryInterface $campaignRunReportRepository
     * @param CsvJobRecordExporter               $jobRecordExporter
     * @param JsonCampaignRunReportExporter      $campaignRunReportExporter
     * @param StorageFileWriter                  $storageFileWriter
     * @param array                              $paths
     */
    public function __construct(
        ImsConfig $config,
        ImsJobClient $client,
        MediaProducingService $mediaProducingService,
        BatchProducingService $batchProducingService,
        CampaignProducingService $campaignProducingService,
        JobRecordRepositoryInterface $jobRecordRepository,
        CampaignRunReportRepositoryInterface $campaignRunReportRepository,
        CsvJobRecordExporter $jobRecordExporter,
        JsonCampaignRunReportExporter $campaignRunReportExporter,
        StorageFileWriter $storageFileWriter,
        array $paths = array()
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->mediaProducingService = $mediaProducingService;
        $this->batchProducingService = $batchProducingService;
        $this->campaignProducingService = $campaignProducingService;
        $this->jobRecordRepository = $jobRecordRepository;
        $this->campaignRunReportRepository = $campaignRunReportRepository;
        $this->jobRecordExporter = $jobRecordExporter;
        $this->campaignRunReportExporter = $campaignRunReportExporter;
        $this->storageFileWriter = $storageFileWriter;
        $this->paths = $paths;
    }

    /**
     * Get runtime config.
     *
     * @return ImsConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get low-level job client.
     *
     * @return ImsJobClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get media producing service.
     *
     * @return MediaProducingService
     */
    public function getMediaProducingService()
    {
        return $this->mediaProducingService;
    }

    /**
     * Get batch producing service.
     *
     * @return BatchProducingService
     */
    public function getBatchProducingService()
    {
        return $this->batchProducingService;
    }

    /**
     * Get campaign producing service.
     *
     * @return CampaignProducingService
     */
    public function getCampaignProducingService()
    {
        return $this->campaignProducingService;
    }

    /**
     * Get job record repository.
     *
     * @return JobRecordRepositoryInterface
     */
    public function getJobRecordRepository()
    {
        return $this->jobRecordRepository;
    }

    /**
     * Get campaign run report repository.
     *
     * @return CampaignRunReportRepositoryInterface
     */
    public function getCampaignRunReportRepository()
    {
        return $this->campaignRunReportRepository;
    }

    /**
     * Get job record exporter.
     *
     * @return CsvJobRecordExporter
     */
    public function getJobRecordExporter()
    {
        return $this->jobRecordExporter;
    }

    /**
     * Get campaign report exporter.
     *
     * @return JsonCampaignRunReportExporter
     */
    public function getCampaignRunReportExporter()
    {
        return $this->campaignRunReportExporter;
    }

    /**
     * Get file writer.
     *
     * @return StorageFileWriter
     */
    public function getStorageFileWriter()
    {
        return $this->storageFileWriter;
    }

    /**
     * Get application paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Submit a timeline.
     *
     * @param Timeline          $timeline
     * @param OutputMediaConfig $outputMediaConfig
     * @param array             $options
     *
     * @return \Hunjian\AliyunImsMixcut\Result\JobResult
     */
    public function submitTimeline(Timeline $timeline, OutputMediaConfig $outputMediaConfig, array $options = array())
    {
        return $this->mediaProducingService->submitTimeline($timeline, $outputMediaConfig, $options);
    }

    /**
     * Submit one local template.
     *
     * @param TemplateInterface $template
     * @param array             $context
     * @param array             $options
     *
     * @return \Hunjian\AliyunImsMixcut\Result\JobResult
     */
    public function submitLocalTemplate(TemplateInterface $template, array $context = array(), array $options = array())
    {
        return $this->mediaProducingService->submitLocalTemplate($template, $context, $options);
    }

    /**
     * Submit batch items.
     *
     * @param array $batch
     *
     * @return array
     */
    public function submitBatch(array $batch)
    {
        return $this->batchProducingService->submitBatch($batch);
    }

    /**
     * Run one campaign.
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
        return $this->campaignProducingService->runCampaign(
            $campaign,
            $waitUntilFinished,
            $intervalSeconds,
            $timeoutSeconds
        );
    }

    /**
     * Run one campaign and persist both report and job records.
     *
     * @param CampaignPlan $campaign
     * @param bool         $waitUntilFinished
     * @param int          $intervalSeconds
     * @param int          $timeoutSeconds
     *
     * @return array
     */
    public function runCampaignAndStore(CampaignPlan $campaign, $waitUntilFinished = false, $intervalSeconds = 5, $timeoutSeconds = 600)
    {
        $report = $this->runCampaign($campaign, $waitUntilFinished, $intervalSeconds, $timeoutSeconds);
        $persisted = $this->persistCampaignRunReport($report);

        return array(
            'report' => $report,
            'reportPath' => $persisted['reportPath'],
            'recordPaths' => $persisted['recordPaths'],
        );
    }

    /**
     * Save one job record.
     *
     * @param JobRecord $record
     *
     * @return mixed
     */
    public function saveJobRecord(JobRecord $record)
    {
        return $this->jobRecordRepository->save($record);
    }

    /**
     * Save one campaign report.
     *
     * @param CampaignRunReport $report
     *
     * @return mixed
     */
    public function saveCampaignRunReport(CampaignRunReport $report)
    {
        return $this->campaignRunReportRepository->save($report);
    }

    /**
     * Save the report and all nested job records.
     *
     * @param CampaignRunReport $report
     *
     * @return array
     */
    public function persistCampaignRunReport(CampaignRunReport $report)
    {
        return array(
            'reportPath' => $this->saveCampaignRunReport($report),
            'recordPaths' => $this->jobRecordRepository->saveMany($report->getRecords()),
        );
    }

    /**
     * Export one campaign report to JSON.
     *
     * @param CampaignRunReport $report
     *
     * @return string
     */
    public function exportCampaignReportJson(CampaignRunReport $report)
    {
        return $this->campaignRunReportExporter->export($report);
    }

    /**
     * Export many job records to CSV.
     *
     * @param array $records
     *
     * @return string
     */
    public function exportJobRecordsCsv(array $records)
    {
        return $this->jobRecordExporter->export($records);
    }

    /**
     * Export one campaign report to a file.
     *
     * @param CampaignRunReport $report
     * @param string            $path
     *
     * @return string
     */
    public function exportCampaignReportJsonTo(CampaignRunReport $report, $path)
    {
        return $this->storageFileWriter->write($path, $this->exportCampaignReportJson($report));
    }

    /**
     * Export job records CSV to a file.
     *
     * @param array  $records
     * @param string $path
     *
     * @return string
     */
    public function exportJobRecordsCsvTo(array $records, $path)
    {
        return $this->storageFileWriter->write($path, $this->exportJobRecordsCsv($records));
    }
}
